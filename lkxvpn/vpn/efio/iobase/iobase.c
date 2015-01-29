#include <iobase.h>

#include <net/if.h>	/* ifreq */
#include <sys/ioctl.h>	/* ioctl */
#include <sys/poll.h>
#include <sys/mman.h>	/* PROT_* */
#include <stdlib.h>
#include <stdio.h>
#include <inttypes.h>	/* PRI* macros */
#include <string.h>	/* strcmp */
#include <fcntl.h>	/* open */
#include <linux/ethtool.h>
#include <linux/sockios.h>


#include <sys/time.h>
#include <pthread.h>

#include <pfring.h>
//#include <pf_ring.h>
#include <netmap_user.h>
#include <netmap.h>

#ifndef likely
#define likely(x)	__builtin_expect(!!(x), 1)
#endif
#ifndef unlikely
#define unlikely(x)	__builtin_expect(!!(x), 0)
#endif


typedef struct _ef_netmap
{
	int 				fd;
	int					hs;			//hoststack
	int					host_open;
	pthread_t			host_thread;
	void				*mmap_addr;
	unsigned int		mmap_size;
	struct nmreq		nmr;
	struct netmap_if	*nifp;
	int 				rxrings;
	int 				txrings;
	int					rxavail;
	int					txavail;
	int					host_rxcur;
	int					host_txcur;
	int					host_rxavail;
	int					host_txavail;
	int 				cur_rxring;
	int 				cur_txring;
	int					lim_rxring;
	int					lim_txring;
}ef_netmap;

typedef pfring ef_pfring;

typedef int (*io_handle)(int n, io_slot *slot, int num);
typedef int (*poll_back)();

static int time_start = 0;
static unsigned long cur_time;
volatile unsigned long base_time;
static pthread_t time_thread;

static void *iobase_get_time()
{
	struct timeval now;
	while(time_start)
	{
		gettimeofday(&now, NULL);
		base_time = cur_time = now.tv_sec * 1000000 + now.tv_usec;
		usleep(0);
	}
}

unsigned long iobase_now()
{
	return cur_time;
}

void iobase_start_timethread()
{
	time_start = 1;
	pthread_create(&time_thread, NULL, iobase_get_time, NULL);
}

void iobase_stop_timethread()
{
	time_start = 0;
	pthread_join(time_thread, NULL);
}


/************************		netmap api		************************/

static void *ef_netmap_hoststack_thread(void *_fd)
{
	ef_netmap *fd = (ef_netmap *)_fd;

	while(fd->host_open)
	{
		struct netmap_ring *host_rxring = NETMAP_RXRING(fd->nifp, fd->rxrings);
		struct netmap_ring *host_txring = NETMAP_TXRING(fd->nifp, fd->txrings);


		if(!fd->host_rxavail)
		{
			host_rxring->cur = fd->host_rxcur;
			ioctl(fd->hs, NIOCRXSYNC, NULL);
			fd->host_rxavail = host_rxring->avail;
		}
		if(!fd->host_txavail)
		{
			host_txring->cur = fd->host_txcur;
			if(host_txring->cur != fd->host_txcur)
				ioctl(fd->hs, NIOCTXSYNC, NULL);
			fd->host_txavail = host_txring->avail;
		}
		/*
		if(host_rxring->cur != fd->host_rxcur)
		{
			host_rxring->cur = fd->host_rxcur;
			ioctl(fd->hs, NIOCRXSYNC, NULL);
		}
		*/
		if(host_txring->cur != fd->host_txcur)
		{
			host_txring->cur = fd->host_txcur;
			ioctl(fd->hs, NIOCTXSYNC, NULL);
		}
		usleep(0);
	}
}

void *ef_netmap_open(char *dev, int direction, int promisc)
{
	ef_netmap *fd = (ef_netmap *)malloc(sizeof(ef_netmap));
	struct nmreq *nmr = &(fd->nmr);
	void *mmap_addr;

	int sock;
	struct ifreq ifr;
	struct ethtool_value eval;

	memset(fd, 0, sizeof(ef_netmap));
	fd->fd = open("/dev/netmap", O_RDWR);

	bzero(nmr, sizeof(struct nmreq));
	strncpy(nmr->nr_name, dev, sizeof(nmr->nr_name));
	nmr->nr_version = NETMAP_API;
	if((ioctl(fd->fd, NIOCGINFO, nmr)) == -1)
	{
		fprintf(stderr, "Unable to register %s\n", dev);
		goto err;
	}
	mmap_addr = (struct netmap_d *) mmap(0, nmr->nr_memsize,
					    PROT_WRITE | PROT_READ,
					    MAP_SHARED, fd->fd, 0);
	if (mmap_addr == MAP_FAILED)
		goto err;
	fd->mmap_addr = mmap_addr;
	fd->mmap_size = nmr->nr_memsize;

	bzero(nmr, sizeof(struct nmreq));
	strncpy(nmr->nr_name, dev, sizeof(nmr->nr_name));
	nmr->nr_version = NETMAP_API;
	nmr->nr_ringid = 0;
	if(!(direction & IO_ENABLE_SEND))
		nmr->nr_ringid |= NETMAP_NO_TX_POLL;
	if((ioctl(fd->fd, NIOCREGIF, nmr)) == -1)
	{
		fprintf(stderr, "Unable to register %s\n", dev);
		goto err;
	}


	sock = socket(AF_INET, SOCK_DGRAM, 0);
	if (sock < 0)
	{
		fprintf(stderr, "Error: cannot get device control socket.\n");
		goto err;
	}
set_if_promisc:
	{
		bzero(&ifr, sizeof(ifr));
		strncpy(ifr.ifr_name, dev, sizeof(ifr.ifr_name));
		if(ioctl(sock, SIOCGIFFLAGS, &ifr) == -1)
		{
			close(sock);
			goto err;
		}
		if(promisc)
			ifr.ifr_flags |= IFF_PROMISC;
		else
			ifr.ifr_flags &= ~IFF_PROMISC;
		if(ioctl(sock, SIOCSIFFLAGS, &ifr) == -1)
		{
			close(sock);
			goto err;
		}
	}

set_dev_flag:
    #if 1
	{
		bzero(&ifr, sizeof(ifr));
		strncpy(ifr.ifr_name, dev, sizeof(ifr.ifr_name));
		ifr.ifr_data = (caddr_t)&eval;
	close_gso:
		bzero(&eval, sizeof(eval));
		eval.cmd = ETHTOOL_SGSO;
		ioctl(sock, SIOCETHTOOL, &ifr);
	close_gro:
		bzero(&eval, sizeof(eval));
		eval.cmd = ETHTOOL_SGRO;
		ioctl(sock, SIOCETHTOOL, &ifr);
	close_rxcsum:
		bzero(&eval, sizeof(eval));
		eval.cmd = ETHTOOL_SRXCSUM;
		ioctl(sock, SIOCETHTOOL, &ifr);
	close_txcsum:
		bzero(&eval, sizeof(eval));
		eval.cmd = ETHTOOL_STXCSUM;
		ioctl(sock, SIOCETHTOOL, &ifr);
	close_lro:
		bzero(&eval, sizeof(eval));
		eval.cmd = ETHTOOL_GFLAGS;
		ioctl(sock, SIOCETHTOOL, &ifr);
		eval.data &= ~ETH_FLAG_LRO;
		eval.cmd = ETHTOOL_SFLAGS;
		ioctl(sock, SIOCETHTOOL, &ifr);
	}
	#endif
	close(sock);

	fd->nifp = NETMAP_IF(mmap_addr, nmr->nr_offset);
	fd->rxrings = nmr->nr_rx_rings;
	fd->txrings = nmr->nr_tx_rings;
	fd->rxavail = 0;
	fd->txavail = 0;
	fd->cur_rxring = 0;
	fd->cur_txring = 0;
	fd->lim_rxring = fd->rxrings - 1;
	fd->lim_txring = fd->txrings - 1;

	if(direction & IO_ENABLE_HOST)
	{
		struct netmap_ring *rxring = NETMAP_RXRING(fd->nifp, fd->rxrings);
		struct netmap_ring *txring = NETMAP_TXRING(fd->nifp, fd->txrings);

		fd->host_open = 1;
		fd->host_rxcur = rxring->cur;
		fd->host_txcur = txring->cur;
		fd->host_rxavail = rxring->avail;
		fd->host_txavail = txring->avail;
		fd->hs = open("/dev/netmap", O_RDWR);
		bzero(nmr, sizeof(struct nmreq));
		strncpy(nmr->nr_name, dev, sizeof(nmr->nr_name));
		nmr->nr_version = NETMAP_API;
		nmr->nr_ringid = NETMAP_SW_RING;
		if((ioctl(fd->hs, NIOCREGIF, nmr)) == -1)
		{
			fprintf(stderr, "Unable to register %s host\n", dev);
			goto err;
		}
		pthread_create(&fd->host_thread, NULL, ef_netmap_hoststack_thread, (void *)fd);
	}

	//fprintf(stderr, "dev : %s , rx quees : %d , tx quees : %d\n", dev, nmr->nr_rx_rings, nmr->nr_tx_rings);
	sleep(5);

	return ((void *)fd);

err:
	fprintf(stderr, "netmap open dev error!\n");
	free(fd);
	return NULL;
}

int ef_netmap_close(void *_fd)
{
	ef_netmap *fd = (ef_netmap *)_fd;
	if(fd->host_open)
	{
		fd->host_open = 0;
		pthread_join(fd->host_thread, NULL);
		ioctl(fd->hs, NIOCUNREGIF, &(fd->nmr));
		close(fd->hs);
	}
	ioctl(fd->fd, NIOCUNREGIF, &(fd->nmr));
	munmap(fd->mmap_addr, fd->mmap_size);
	close(fd->fd);
	free(fd);
	return 0;
}

int ef_netmap_read(void *_fd, io_slot *slot, int num)
{
	int n = 0;
	ef_netmap *fd = (ef_netmap *)_fd;
	struct netmap_ring *rxring;

	char *p;
	int len;

do_recv:
	if(fd->host_open && fd->host_rxavail)
	{
		rxring = NETMAP_RXRING(fd->nifp, fd->rxrings);
		while((fd->host_rxavail > 0) && (n < num))
		{
			struct netmap_slot *nm_slot = &rxring->slot[fd->host_rxcur];
			p = NETMAP_BUF(rxring, nm_slot->buf_idx);
			len = nm_slot->len;
			memcpy(slot->buf, p, len);
			slot->len = len;
			slot->time = cur_time;
			slot->flag = IO_FROM_HOST;
			slot++; n++;
			fd->host_rxcur = NETMAP_RING_NEXT(rxring, fd->host_rxcur);
			fd->host_rxavail--;
		}
	}
	while((fd->rxavail) && (n < num))
	{
		rxring = NETMAP_RXRING(fd->nifp, fd->cur_rxring);
		if (rxring->avail == 0)
		{
			fd->cur_rxring = ((fd->cur_rxring+1) == fd->rxrings ? 0 : (fd->cur_rxring+1));
		}
		else
		{
			while((rxring->avail > 0) && (n < num))
			{
				struct netmap_slot *nm_slot = &rxring->slot[rxring->cur];
				p = NETMAP_BUF(rxring, nm_slot->buf_idx);
				len = nm_slot->len;
				memcpy(slot->buf, p, len);
				slot->len = len;
				slot->time = cur_time;
				slot->flag = 0;
				slot++; n++;
				rxring->cur = NETMAP_RING_NEXT(rxring, rxring->cur);
				rxring->avail--;
				fd->rxavail--;
			}
		}
	}

	return n;
}

int ef_netmap_read_nocopy(void *_fd, io_slot *slot, int num)
{
	int n = 0;
	ef_netmap *fd = (ef_netmap *)_fd;
	struct netmap_ring *rxring;

	char *p;
	int len;

do_recv:
	if(fd->host_open && fd->host_rxavail)
	{
		rxring = NETMAP_RXRING(fd->nifp, fd->rxrings);
		while((fd->host_rxavail > 0) && (n < num))
		{
			struct netmap_slot *nm_slot = &rxring->slot[fd->host_rxcur];
			p = NETMAP_BUF(rxring, nm_slot->buf_idx);
			len = nm_slot->len;
			slot->pbuf = p;
			slot->plen = len;
			slot->time = cur_time;
			slot->flag = IO_FROM_HOST;
			slot++; n++;
			fd->host_rxcur = NETMAP_RING_NEXT(rxring, fd->host_rxcur);
			fd->host_rxavail--;
		}
	}
	while((fd->rxavail) && (n < num))
	{
		rxring = NETMAP_RXRING(fd->nifp, fd->cur_rxring);
		if (rxring->avail == 0)
		{
			fd->cur_rxring = ((fd->cur_rxring+1) == fd->rxrings ? 0 : (fd->cur_rxring+1));
		}
		else
		{
			while((rxring->avail > 0) && (n < num))
			{
				struct netmap_slot *nm_slot = &rxring->slot[rxring->cur];
				p = NETMAP_BUF(rxring, nm_slot->buf_idx);
				len = nm_slot->len;
				slot->pbuf = p;
				slot->plen = len;
				slot->time = cur_time;
				slot->flag = 0;
				slot++; n++;
				rxring->cur = NETMAP_RING_NEXT(rxring, rxring->cur);
				rxring->avail--;
				fd->rxavail--;
			}
		}
	}

	return n;
}

int ef_netmap_send(void *_fd, io_slot *slot, int num)
{
	int n = 0;
	ef_netmap *fd = (ef_netmap *)_fd;
	struct netmap_ring *txring;
	static int hs[512] = {0};
	int	hs_tot = 0;
	io_slot *slot_bak = slot;

	char *p;

do_send:
	while((fd->txavail) && (n < num))
	{
		txring = NETMAP_TXRING(fd->nifp, fd->cur_txring);
		if (txring->avail == 0)
		{
			fd->cur_txring = ((fd->cur_txring+1) == fd->txrings ? 0 : (fd->cur_txring+1));
		}
		else
		{
			while((txring->avail > 0) && (n < num))
			{
				if(likely(slot->flag != IO_TO_HOST))
				{
					struct netmap_slot *nm_slot = &txring->slot[txring->cur];
					p = NETMAP_BUF(txring, nm_slot->buf_idx);
					memcpy(p, slot->buf, slot->len);
					nm_slot->len = slot->len;
					txring->cur = NETMAP_RING_NEXT(txring, txring->cur);
					txring->avail--;
					fd->txavail--;
					/*
					if(!txring->avail)
						nm_slot->flags |= NS_REPORT;
					else if(n == num)
						nm_slot->flags |= NS_REPORT;
					*/
				}
				else
					hs[hs_tot++] = n;
				slot++; n++;
			}
		}
	}
	if(hs_tot && fd->host_open && fd->host_txavail)
	{
		int h = 0;
		io_slot *hslot;
		slot = slot_bak;
		txring = NETMAP_TXRING(fd->nifp, fd->txrings);
		while((fd->host_txavail > 0) && (h < hs_tot))
		{
			struct netmap_slot *nm_slot = &txring->slot[fd->host_txcur];
			hslot = &slot[hs[h]];
			p = NETMAP_BUF(txring, nm_slot->buf_idx);
			memcpy(p, hslot->buf, hslot->len);
			nm_slot->len = hslot->len;
			fd->host_txcur = NETMAP_RING_NEXT(txring, fd->host_txcur);
			fd->host_txavail--;
			h++;
		}
	}

	return n;
}

static int ef_netmap_rxavail(ef_netmap *fd)
{
	int n;
	int avail = 0;
	struct netmap_ring *rxring;

	for(n = 0; n < fd->rxrings; n++)
	{
		rxring = NETMAP_RXRING(fd->nifp, n);
		avail += rxring->avail;
	}
	return avail;
}

static int ef_netmap_txavail(ef_netmap *fd)
{
	int n;
	int avail = 0;
	struct netmap_ring *txring;

	for(n = 0; n < fd->txrings; n++)
	{
		txring = NETMAP_TXRING(fd->nifp, n);
		avail += txring->avail;
	}
	return avail;
}

int ef_netmap_flush(void *_fd, int flush_type, int flush_wait)
{
	ef_netmap *fd = (ef_netmap *)_fd;
	struct pollfd fds[1];
	int ret = 0;

	memset(fds, 0, sizeof(fds));
	fds[0].fd = fd->fd;
	if(flush_type & IO_FLUSH_READ)
		fds[0].events |= (POLLIN);
	if(flush_type & IO_FLUSH_SEND)
		fds[0].events |= (POLLOUT);

	if (poll(fds, 1, flush_wait * 1000) > 0)
	{
		if ((flush_type & IO_FLUSH_READ) && (fds[0].revents & POLLIN))
		{
			ret |= IO_FLUSH_READ;
			fd->rxavail = ef_netmap_rxavail(fd);
			fd->lim_rxring = (fd->cur_rxring == 0) ? (fd->rxrings - 1) : (fd->cur_rxring - 1);
		}
		if ((flush_type & IO_FLUSH_SEND) && (fds[0].revents & POLLOUT))
		{
			ret |= IO_FLUSH_SEND;
			fd->txavail = ef_netmap_txavail(fd);
			fd->lim_txring = (fd->cur_txring == 0) ? (fd->txrings - 1) : (fd->cur_txring - 1);
		}
	}
	//fprintf(stderr, "rxavail:%d \t txavail:%d\n", fd->rxavail, fd->txavail);
	return ret;
}

static int ef_netmap_move(ef_netmap *fd_src, ef_netmap *fd_dst, io_slot *slot, int num)
{
	int swap;
	int ret;

	swap = (fd_src->rxavail < fd_dst->txavail) ? fd_src->rxavail : fd_dst->txavail;
	swap = (swap < num) ? swap : num;
	ret = swap;
	while((swap) && (fd_src->rxavail) && (fd_dst->txavail))
	{
		int n;
		struct netmap_ring *rxring = NETMAP_RXRING(fd_src->nifp, fd_src->cur_rxring);
		struct netmap_ring *txring = NETMAP_TXRING(fd_dst->nifp, fd_dst->cur_txring);
		if(rxring->avail == 0)
		{
			fd_src->cur_rxring = ((fd_src->cur_rxring+1) == fd_src->rxrings ? 0 : (fd_src->cur_rxring+1));
			continue;
		}
		if(txring->avail == 0)
		{
			fd_dst->cur_txring = ((fd_dst->cur_txring+1) == fd_dst->txrings ? 0 : (fd_dst->cur_txring+1));
			continue;
		}
		n = (rxring->avail < txring->avail) ? rxring->avail : txring->avail;
		n = (n < swap) ? n : swap;
		swap -= n;
		while((n) && (rxring->avail) && (txring->avail))
		{
			struct netmap_slot *rx_slot = &rxring->slot[rxring->cur];
			struct netmap_slot *tx_slot = &txring->slot[txring->cur];
			int idx;

			idx = rx_slot->buf_idx;
			rx_slot->buf_idx = tx_slot->buf_idx;
			tx_slot->buf_idx = idx;

			slot->pbuf = NETMAP_BUF(rxring, idx);
			tx_slot->len = rx_slot->len;
			slot->plen = rx_slot->len; //由于设计原因，旧bdg需要修改，待定
			slot->time = cur_time;
			slot++;

			rxring->cur = NETMAP_RING_NEXT(rxring, rxring->cur);
			txring->cur = NETMAP_RING_NEXT(txring, txring->cur);
			rxring->avail--;
			txring->avail--;
			fd_src->rxavail--;
			fd_dst->txavail--;
			n--;

		}
		swap += n;
	}
	return (ret - swap);
}


#define MBDG_SLOT_TOTAL	1024
static int bdg_status = 0;
static int mbdg_status = 0;
static io_slot mbdg_slot[MBDG_SLOT_TOTAL] = {0};
static int mbdg_slot_insert = 0, mbdg_slot_finish = 0;

int ef_netmap_bdg_stop()
{
	bdg_status = 0;
}

int ef_netmap_bdg_start(int n0, int n1, void *_fd0, void *_fd1, void *_handle)
{
	ef_netmap *fd0 = (ef_netmap *)_fd0;
	ef_netmap *fd1 = (ef_netmap *)_fd1;
	struct pollfd fds[2];
	int ret;
	io_slot slot[1024] = {0};
	io_handle handle = (io_handle)_handle;

	bdg_status = 1;
	memset(fds, 0, sizeof(fds));
	fds[0].fd = fd0->fd;
	fds[1].fd = fd1->fd;
	do
	{
		fds[0].events = fds[1].events = 0;
		fds[0].revents = fds[1].revents = 0;
		/*
		if (fd0->rxavail)
			fds[1].events |= POLLOUT;
		else
			fds[0].events |= POLLIN;
		if (fd1->rxavail)
			fds[0].events |= POLLOUT;
		else
			fds[1].events |= POLLIN;
		*/
		/*
		if(!(fd0->rxavail))
			fds[0].events |= POLLIN;
		if(!(fd0->txavail))
			fds[0].events |= POLLOUT;
		if(!(fd1->rxavail))
			fds[1].events |= POLLIN;
		if(!(fd1->txavail))
			fds[1].events |= POLLOUT;
		*/
		fds[0].events = fds[1].events = (POLLIN | POLLOUT);
		ret = poll(fds, 2, 2500);
		if (ret < 0)
		{
			continue;
		}
		fd0->rxavail = ef_netmap_rxavail(fd0);
		fd0->txavail = ef_netmap_txavail(fd0);
		fd1->rxavail = ef_netmap_rxavail(fd1);
		fd1->txavail = ef_netmap_txavail(fd1);
		while ((fd0->rxavail) && (fd1->txavail))
		{
			int swap = ef_netmap_move(fd0, fd1, slot, 1024);
			handle(n0, slot, swap);
		}
		fd0->rxavail = ef_netmap_rxavail(fd0);
		fd0->txavail = ef_netmap_txavail(fd0);
		fd1->rxavail = ef_netmap_rxavail(fd1);
		fd1->txavail = ef_netmap_txavail(fd1);
		while ((fd1->rxavail) && (fd0->txavail))
		{
			int swap = ef_netmap_move(fd1, fd0, slot, 1024);
			handle(n1, slot, swap);
		}
	}while(bdg_status);
}




static int ef_netmap_mmove(int *n, ef_netmap **fd, int num, io_handle handle,
								int move, int *nrev,
								io_slot *i_slot, struct netmap_slot **r_slot, unsigned int *t_slot[],
								unsigned int *h_slot[]
								)
{
	ef_netmap *fd_in, *fd_out;
	int rx_tot = 0, i, j;
	int tx_tot[32] = {0};
	int hs_tot[32] = {0};

	fd_in = fd[move];
	if(fd_in->host_open && fd_in->host_rxavail)
	{
		struct netmap_ring *rxring = NETMAP_RXRING(fd_in->nifp, fd_in->rxrings);
		while(fd_in->host_rxavail)
		{
			struct netmap_slot *rx_slot = &rxring->slot[fd_in->host_rxcur];
			int idx = rx_slot->buf_idx;

			i_slot[rx_tot].pbuf = NETMAP_BUF(rxring, idx);
			i_slot[rx_tot].plen = rx_slot->len;
			i_slot[rx_tot].in = n[move];
			i_slot[rx_tot].len = 0;
			i_slot[rx_tot].out = 0;
			i_slot[rx_tot].time = cur_time;
			i_slot[rx_tot].flag = IO_FROM_HOST;
			r_slot[rx_tot] = rx_slot;
			rx_tot++;

			fd_in->host_rxcur = NETMAP_RING_NEXT(rxring, fd_in->host_rxcur);
			fd_in->host_rxavail--;
		}
	}
	while(fd_in->rxavail)
	{
		struct netmap_ring *rxring = NETMAP_RXRING(fd_in->nifp, fd_in->cur_rxring);
		if(!rxring->avail)
		{
			fd_in->cur_rxring = ((fd_in->cur_rxring+1) == fd_in->rxrings ? 0 : (fd_in->cur_rxring+1));
			continue;
		}
		while(rxring->avail)
		{
			struct netmap_slot *rx_slot = &rxring->slot[rxring->cur];
			int idx = rx_slot->buf_idx;

			i_slot[rx_tot].pbuf = NETMAP_BUF(rxring, idx);
			i_slot[rx_tot].plen = rx_slot->len;
			i_slot[rx_tot].in = n[move];
			i_slot[rx_tot].len = 0;
			i_slot[rx_tot].out = 0;
			i_slot[rx_tot].time = cur_time;
			i_slot[rx_tot].flag = 0;
			r_slot[rx_tot] = rx_slot;
			rx_tot++;

			rxring->cur = NETMAP_RING_NEXT(rxring, rxring->cur);
			rxring->avail--;
			fd_in->rxavail--;
		}
	}
	handle(n[move], i_slot, rx_tot);
	for(i = 0; i < rx_tot; i++)
	{
		int out = nrev[i_slot[i].out];
		//fprintf(stderr, "slot->out:%d, out:%d\n", i_slot[i].out, out);
		if(out >= 0)
		{
			//t_slot[out][tx_tot[out]++] = r_slot[i];
			r_slot[i]->len = i_slot[i].plen;
			if(i_slot[i].flag == IO_TO_HOST)
			{
				h_slot[out][hs_tot[out]++] = i;
			}
			else
				t_slot[out][tx_tot[out]++] = i;
		}
	}
	for(i = 0; i < num; i++)
	{
		fd_out = fd[i];
		if(tx_tot[i])
		{
			j = 0;
			while(fd_out->txavail && (j < tx_tot[i]))
			{
				int swap = 0;
				struct netmap_ring *txring = NETMAP_TXRING(fd_out->nifp, fd_out->cur_txring);
				if(!txring->avail)
				{
					fd_out->cur_txring = ((fd_out->cur_txring+1) == fd_out->txrings ? 0 : (fd_out->cur_txring+1));
					continue;
				}
				swap = tx_tot[i] - j;
				//swap = swap > txring->avail ? txring->avail : swap;
				while(swap && txring->avail)
				{
					io_slot *slot = &i_slot[t_slot[i][j]];
					struct netmap_slot *sw_slot = r_slot[t_slot[i][j]];
					struct netmap_slot *tx_slot = &txring->slot[txring->cur];
					int idx;

					if(unlikely(slot->len))
					{
						char *p = NETMAP_BUF(txring, tx_slot->buf_idx);
						memcpy(p, slot->buf, slot->len);
						tx_slot->len = slot->len;
						slot->len = 0;
						txring->cur = NETMAP_RING_NEXT(txring, txring->cur);
						txring->avail--;
						fd_out->txavail--;
						continue;
					}
					idx = sw_slot->buf_idx;
					sw_slot->buf_idx = tx_slot->buf_idx;
					tx_slot->buf_idx = idx;
					tx_slot->len = sw_slot->len;
					j++;
					swap--;
					txring->cur = NETMAP_RING_NEXT(txring, txring->cur);
					txring->avail--;
					fd_out->txavail--;
				}
			}
		}

		if(hs_tot[i] && fd_out->host_open && fd_out->host_txavail)
		{
			int swap = 0;
			struct netmap_ring *txring = NETMAP_TXRING(fd_out->nifp, fd_out->txrings);
			while((swap < hs_tot[i]) && fd_out->host_txavail)
			{
				io_slot *slot = &i_slot[h_slot[i][swap]];
				struct netmap_slot *sw_slot = r_slot[h_slot[i][swap]];
				struct netmap_slot *tx_slot = &txring->slot[fd_out->host_txcur];
				int idx;

				if(unlikely(slot->len))
				{
					char *p = NETMAP_BUF(txring, tx_slot->buf_idx);
					memcpy(p, slot->buf, slot->len);
					tx_slot->len = slot->len;
					slot->len = 0;
					fd_out->host_txcur = NETMAP_RING_NEXT(txring, fd_out->host_txcur);
					fd_out->host_txavail--;
					continue;
				}
				idx = sw_slot->buf_idx;
				sw_slot->buf_idx = tx_slot->buf_idx;
				tx_slot->buf_idx = idx;
				tx_slot->len = sw_slot->len;
				swap++;
				fd_out->host_txcur = NETMAP_RING_NEXT(txring, fd_out->host_txcur);
				fd_out->host_txavail--;
			}
		}
	}
	return 0;
}

int ef_netmap_mbdg_insert(io_slot *slot, int num)
{
	int insert = 0;
	int insert_point;
	int finish_point;

	insert_point = mbdg_slot_insert;
	finish_point = mbdg_slot_finish;
	while( ((insert_point + 1) % MBDG_SLOT_TOTAL != finish_point) && (insert < num))
	{
		memcpy(mbdg_slot[insert_point].buf, slot->buf, slot->len);
		mbdg_slot[insert_point].len = slot->len;
		mbdg_slot[insert_point].out = slot->out;
		insert++;
		insert_point = (insert_point + 1) % MBDG_SLOT_TOTAL;
		slot++;
	}
	mbdg_slot_insert = insert_point;
	return insert;
}

int ef_netmap_mbdg_stop()
{
	mbdg_status = 0;
	return 0;
}

int ef_netmap_mbdg_start(int *n, void **_fd, int num, void *_handle, void *_pollback)
{
	ef_netmap **fd = (ef_netmap **)_fd;
	struct pollfd fds[32];
	poll_back pollbk = _pollback;
	int ret, i;
	int nrev[32] = {-1};
	int move[32] = {0};
	int move_sum = 0;

	//io_slot i_slot[512 * 24] = {0};
	//struct netmap_slot *r_slot[512 * 24] = {0};
	//struct netmap_slot *t_slot[32][512 * 24] = {0};

	io_slot *i_slot;
	struct netmap_slot **r_slot;
	unsigned int *t_slot[32];
	unsigned int *h_slot[32];


	i_slot = (io_slot *)malloc(1024 * 48 * sizeof(io_slot));
	r_slot = (struct netmap_slot **)malloc(1024 * 48 * sizeof(struct netmap_slot *));
	for(i = 0; i < 32; i++)
	{
		t_slot[i] = (unsigned int *)malloc(1024 * 48 * sizeof(int));
		h_slot[i] = (unsigned int *)malloc(1024 * sizeof(int));
	}
	mbdg_status = 1;
	memset(fds, 0, sizeof(fds));

	for(i = 0; i < num; i++)
	{
		nrev[n[i]] = i;
		fds[i].fd = fd[i]->fd;
		fds[i].events = (POLLIN | POLLOUT);
	}
	do
	{
		if(mbdg_slot_finish != mbdg_slot_insert)
		{
			int insert_point = mbdg_slot_insert;
			int finish_point = mbdg_slot_finish;
			while(finish_point != insert_point)
			{
				int out = mbdg_slot[finish_point].out;
				ef_netmap *out_fd = fd[nrev[out]];
				ef_netmap_send(out_fd, &mbdg_slot[finish_point], 1);
				finish_point = (finish_point + 1) % MBDG_SLOT_TOTAL;
			}
			mbdg_slot_finish = finish_point;
		}
		if(pollbk)
            pollbk();
		ret = poll(fds, num, 2500);
		if (ret < 0)
		{
			continue;
		}
		move_sum = 0;
		for(i = 0; i < num; i++)
		{
			fd[i]->rxavail = ef_netmap_rxavail(fd[i]);
			fd[i]->txavail = ef_netmap_txavail(fd[i]);
			if(fd[i]->rxavail || fd[i]->host_rxavail)
			{
				move[move_sum++] = i;
			}
		}
	#if 1
		for(i = 0; i < move_sum; i++)
		{
			ef_netmap_mmove(n, fd, num, (io_handle)_handle,
							move[i], nrev,
							i_slot, r_slot, t_slot, h_slot);
		}
	#endif
	}while(mbdg_status);

	free(i_slot);
	free(r_slot);
	for(i = 0; i < 32; i++)
	{
		free(t_slot[i]);
		free(h_slot[i]);
	}
	return 0;
}

#if 0

int ef_netmap_poll()
{
}

int ef_netmap_flush(void *_fd, int flush_type)
{
	ef_netmap *fd = (ef_netmap *)_fd;

	if(flush_type & IO_FLUSH_READ)
		ioctl(fd->fd, NIOCRXSYNC, NULL);
	if(flush_type & IO_FLUSH_SEND)
		ioctl(fd->fd, NIOCTXSYNC, NULL);
}
#endif

/************************					************************/





/************************		pfring api		************************/

void *ef_pfring_open(char *dev, int direction, int promisc)
{
	pfring *pd = NULL;
	unsigned int flag = 0;

	if(promisc)
		flag |= PF_RING_PROMISC;
	pd = pfring_open(dev, 1516, flag);// | PF_RING_LONG_HEADER);

	pfring_set_direction(pd, rx_and_tx_direction);
	if(!(direction & IO_ENABLE_READ))
		pfring_set_direction(pd, tx_only_direction);
	if(!(direction & IO_ENABLE_SEND))
		pfring_set_direction(pd, rx_only_direction);

	if(pfring_enable_ring(pd) != 0)
	{
		pd = NULL;
	}

	return ((void *)pd);
}

int ef_pfring_close(void *_fd)
{
	ef_pfring *fd = (ef_pfring *)_fd;
	pfring *pd = (pfring *)fd;

	pfring_close(pd);
	return 0;
}

int ef_pfring_read(void *_fd, io_slot *slot, int num)
{
	int n = 0;
	ef_pfring *fd = (ef_pfring *)_fd;
	pfring *pd = (pfring *)fd;
	char *buffer;
    struct pfring_pkthdr hdr;

	char *p;
	int len;
	int ret;

	do
	{
		ret = pfring_recv(pd, &buffer, 0, &hdr, 0);
		if(ret > 0)
		{
			p = buffer;
			len = hdr.caplen;
			memcpy(slot->buf, p, len);
			slot->len = len;
			slot++; n++;
		}
	}while((ret > 0) && (n < num));

	return n;
}

int ef_pfring_send(void *_fd, io_slot *slot, int num)
{
	int n = 0;
	ef_pfring *fd = (ef_pfring *)_fd;
	pfring *pd = (pfring *)fd;
	int ret;

	do
	{
		ret = pfring_send(pd, slot->buf, slot->len, 1);
		if(ret > 0)
		{
			slot++; n++;
		}
	}while((ret > 0) && (n < num));
	return n;
}

int ef_pfring_flush(void *_fd, int flush_type, int flush_wait)
{
	ef_pfring *fd = (ef_pfring *)_fd;
	pfring *pd = (pfring *)fd;
	int ret = 0;

	if(flush_type & IO_FLUSH_SEND)
		ret |= IO_FLUSH_SEND;
	if(flush_type & IO_FLUSH_READ)
	{
		if(pfring_poll(pd, flush_wait) & POLLIN)
			ret |= IO_FLUSH_READ;
	}

	return ret;
}


/************************					************************/


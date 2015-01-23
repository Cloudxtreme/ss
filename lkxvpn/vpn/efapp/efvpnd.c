#include <stdio.h>
#include <stdlib.h>
#include <signal.h>	/* signal */
#include <unistd.h>
#include <sys/shm.h>
#include <sys/time.h>	/* timersub */
#include <syslog.h>
#include <fcntl.h>
#include <pthread.h>
#include <sched.h>
#include <string.h>

#include <openssl/rsa.h>
#include <openssl/pem.h>
#include <openssl/err.h>
#include <openssl/evp.h>
#include <openssl/md5.h>
#include <openssl/sha.h>


#include <efio.h>
#include <efnet.h>
#include <efext.h>
#include "efvpn.h"
#include "conf.h"

static unsigned char *dev1, *dev2;
static int vpn_pid_fd = -1;
static int watch = -1;

static vpn_des vpnd = {0};
static vpn_des *uvpnd = NULL;
static vpn_des *kvpnd = NULL;
static vpn_des *pvpnd = NULL;
static int *pvpn_id = NULL;
static int *pvpn_key = NULL;

static void *slice_ee = NULL;
static void *slice_ff = NULL;

pthread_t			vpn_log_t;
pthread_t			vpn_control_t;
pthread_mutex_t		vpn_lock;

int vpn_license = 0;
int vpn_run = 0;
int vpn_cpu = -1;
int vpn_dir = EF_ENABLE_READ | EF_ENABLE_SEND;



static struct iphdr vpn_iph =
{
	.version = 0x4,
	.ihl = 0x5,
	.tos = 0,
	.tot_len = 0,
	.id = 0,
	.frag_off = 0x0040,
	.ttl = 0xff,
	.protocol = PKT_TYPE_UDP,
	.check = 0,
	.saddr = 0,
	.daddr = 0,
};
static unsigned short iph_id = 0;


/* control-C handler */
static void
sigint_h(int sig)
{
	(void)sig;	/* UNUSED */

	efio_mbdg_stop();
}


unsigned long long vpn_recv_pkt_num = 0;
unsigned long long vpn_send_pkt_num = 0;
unsigned long long bak_recv_pkt_num = 0;
unsigned long long bak_send_pkt_num = 0;



static int decode_pkg(unsigned char *pkg, int len)
{
	unsigned char dmac[32] = {0};
	unsigned char smac[32] = {0};
	int vlan = 0;
	unsigned char sip[32] = {0};
	unsigned char dip[32] = {0};

	mac_2_str(GET_DMAC(pkg), dmac);
	mac_2_str(GET_SMAC(pkg), smac);
	if(IF_VLAN(pkg))
		vlan = VLAN(pkg);
	if(IF_IP(pkg))
	{
		ip_2_str(GET_IP_SIP(pkg), sip);
		ip_2_str(GET_IP_DIP(pkg), dip);
	}
	else if(IF_ARP(pkg))
	{
		ip_2_str(GET_ARP_SIP(pkg), sip);
		ip_2_str(GET_ARP_DIP(pkg), dip);
	}
	fprintf(stderr, "%s --> %s (%d) | %s --> %s\n", smac, dmac, vlan, sip, dip);
}

static int print_pkg(unsigned char *pkg, int len, unsigned char *title)
{
	int j;
	if(title)
		fprintf(stderr, "%s\n", title);
	for(j = 0; j < len; j++)
	{
		fprintf(stderr, "0x%x,", pkg[j]);
		if(j && (j % 16 == 0))
			fprintf(stderr, "\n");
	}
	fprintf(stderr, "\n\n------------------------------------\n\n");
}

static int vpn_restore(ef_slot *cur_slot, int num)
{
	static unsigned long long vpn_link_pkg[0xffff] = {0};
	int i;
	void *pkg;
	unsigned short len;
	unsigned int flag;
	unsigned short link_id;


	vpn_recv_pkt_num += num;
	bak_send_pkt_num += num;
	//return 0;
	for(i = 0; i < num; i++)
	{
		pkg = cur_slot->pbuf;
		len = cur_slot->plen;

		if(unlikely(IF_ARP(pkg)))
		{
			if(IF_ARP_REQ(pkg))
			{
				unsigned int dip = GET_ARP_DIP(pkg);
				if(kvpnd->vpn_outbound_ip_bitmap[(dip >> 16) ^ (dip & 0xffff)])
				{
					int k;
					for(k = 0; k < kvpnd->vpn_outbound_link_tot; k++)
						if(dip == kvpnd->vpn_outbound_links[k].sip)
						{
							ef_slot arp_slot;
							unsigned int sip = GET_ARP_SIP(pkg);
							unsigned char *smac = GET_ARP_SMAC(pkg);
							arp_slot.len = arp_build_reply(dip, kvpnd->vpn_outbound_mac, sip, smac, arp_slot.buf);
							efio_send(kvpnd->vpn_outbound_fd, &arp_slot, 1);
							goto next;
						}
				}
			}
			else
			{
				unsigned char *mac = GET_ARP_SMAC(pkg);
				unsigned int sip = GET_ARP_SIP(pkg);
				unsigned int dip = GET_ARP_DIP(pkg);
				if(kvpnd->vpn_outbound_ip_bitmap[(dip >> 16) ^ (dip & 0xffff)])
				{
					int k;
					for(k = 0; k < kvpnd->vpn_outbound_link_tot; k++)
					{
						vpn_link *link = &kvpnd->vpn_outbound_links[k];
						if((sip == link->gate) && (dip == link->sip))
						{
							link->complete = 1;
							memcpy(link->dmac, mac, 6);
							memcpy(link->link_hdr, mac, 6);
						}
					}
				}
			}
		}

		if(unlikely(!IF_IP(pkg) || (len < VPN_DATA_LEN)))
		{
            if(cur_slot->flag == EF_FROM_HOST)
            {
                cur_slot->out = kvpnd->vpn_outbound_fd;
            }
            else
            {
                cur_slot->flag = EF_TO_HOST;
                cur_slot->out = kvpnd->vpn_outbound_fd;
            }
            goto next;
		}

		flag = *(unsigned int *)(pkg + VPN_POSITION_FLAG);
		//if((GET_IP_SIP(pkg) == str_2_ip("192.168.200.1")) && (GET_IP_DIP(pkg) == str_2_ip("192.168.200.2")))
		//{
		//	decode_pkg(pkg, len);
		//	fprintf(stderr, "flag : 0x%x\n", flag);
		//}
		if(likely(flag == VPN_FLAG_NORMAL))
		{
            if(!vpn_license)
                continue;
			link_id = *(unsigned short *)(pkg + VPN_POSITION_LINK_ID);
			vpn_link_pkg[link_id]++;
			memcpy(pkg, pkg + len - VPN_DATA_LEN, VPN_DATA_LEN);
			cur_slot->plen -= VPN_DATA_LEN;
			cur_slot->out = kvpnd->vpn_backend_fd;
			//fprintf(stderr, "restore1:(%u)  ", cur_slot->plen);
           // decode_pkg(pkg, cur_slot->plen);
		}
		else
		{
			switch(flag)
			{
				case VPN_FLAG_SLICE_EE:
				case VPN_FLAG_SLICE_FF:
				{
					unsigned int pkg_id = *(unsigned int *)(pkg + VPN_POSITION_PKG_ID);
					void *ee_piece, *ff_piece;
					void *ee_pkg, *ff_pkg;
					unsigned int *ee_id, *ff_id;
					unsigned long *ee_time, *ff_time;
					unsigned short *ee_len, *ff_len;

                    if(!vpn_license)
                        continue;
					ee_piece = slice_ee + (pkg_id % SLICE_EE_SLOT_NUM) * SLICE_EE_SLOT_LEN;
					ff_piece = slice_ff + (pkg_id % SLICE_FF_SLOT_NUM) * SLICE_FF_SLOT_LEN;
					ee_time = (unsigned long *)ee_piece;
					ff_time = (unsigned long *)ff_piece;
					ee_len = (unsigned short *)(ee_piece + sizeof(long));
					ff_len = (unsigned short *)(ff_piece + sizeof(long));
					ee_pkg = ee_piece + sizeof(long) + sizeof(short);
					ff_pkg = ff_piece + sizeof(long) + sizeof(short);
					ee_id = (unsigned int *)(ee_pkg + VPN_POSITION_PKG_ID);
					ff_id = (unsigned int *)(ff_pkg + VPN_POSITION_PKG_ID);

					link_id = *(unsigned short *)(pkg + VPN_POSITION_LINK_ID);
					vpn_link_pkg[link_id]++;
					//fprintf(stderr, "restore21:(%u)  ", len);
                    //decode_pkg(pkg, len);
					if(flag == VPN_FLAG_SLICE_EE)
					{
						if(*ff_id == pkg_id)
						{
							memcpy(pkg, ff_pkg + VPN_DATA_LEN_EXT, VPN_DATA_LEN_EXT);
							if((*ff_len) > (VPN_DATA_LEN_EXT + VPN_DATA_LEN_EXT))
							{
                                memcpy(pkg + len, ff_pkg + VPN_DATA_LEN_EXT + VPN_DATA_LEN_EXT, (*ff_len) - VPN_DATA_LEN_EXT - VPN_DATA_LEN_EXT);
                                cur_slot->plen += ((*ff_len) - VPN_DATA_LEN_EXT - VPN_DATA_LEN_EXT);
                            }
							*ff_id = 0;
							cur_slot->out = kvpnd->vpn_backend_fd;
							//fprintf(stderr, "restore22:(%u)  ", cur_slot->plen);
                            //decode_pkg(pkg, cur_slot->plen);
						}
						else if((*ee_id == 0) || (cur_slot->time - *ee_time > SLICE_TIMEOUT))
						{
							*ee_time = cur_slot->time;
							*ee_len = len;
							memcpy(ee_pkg, pkg, len);
							bak_send_pkt_num--;
						}
					}
					else
					{
						if(*ee_id == pkg_id)
						{
							memcpy(ee_pkg, pkg + VPN_DATA_LEN_EXT, VPN_DATA_LEN_EXT);
							if(len > (VPN_DATA_LEN_EXT + VPN_DATA_LEN_EXT))
							{
                                memcpy(ee_pkg + (*ee_len), pkg + VPN_DATA_LEN_EXT + VPN_DATA_LEN_EXT, len - VPN_DATA_LEN_EXT - VPN_DATA_LEN_EXT);
							}
							if(unlikely(IF_VLAN(ee_pkg)))
								cur_slot->plen = ntohs(P_IPP(ee_pkg)->tot_len) + 18;
							else
								cur_slot->plen = ntohs(P_IPP(ee_pkg)->tot_len) + 14;
							memcpy(pkg, ee_pkg, cur_slot->plen);
							*ee_id = 0;
							cur_slot->out = kvpnd->vpn_backend_fd;
							//fprintf(stderr, "restore22:(%u)  ", cur_slot->plen);
                            //decode_pkg(pkg, cur_slot->plen);
						}
						else if((*ff_id == 0) || (cur_slot->time - *ff_time > SLICE_TIMEOUT))
						{
							*ff_time = cur_slot->time;
							*ff_len = len;
							memcpy(ff_pkg, pkg, len);
							bak_send_pkt_num--;
						}
					}
				}
					break;
				case VPN_FLAG_REPORT_ASK:
				{
					int i, conn = 1;
					link_report *report;
					unsigned char mac[6];
					unsigned int ip;
					unsigned short port;
					struct iphdr *iph;
					struct udphdr *uph;

                    //if(!vpn_license)
                        //continue;
					memcpy(mac, pkg, 6);
					memcpy(pkg, pkg + 6, 6);
					memcpy(pkg + 6, mac, 6);
					iph = P_IPP(pkg);
					uph = P_UDPP(pkg);
					ip = iph->saddr;
					iph->saddr = iph->daddr;
					iph->daddr = ip;
					iph->check = 0;
					iph->check = checksum(iph, sizeof(struct iphdr));
					port = uph->source;
					uph->source = uph->dest;
					uph->dest = port;


					for(i = 0; i < kvpnd->vpn_outbound_link_tot; i++)
					{
						vpn_link *link = &kvpnd->vpn_outbound_links[i];
						if((link->sip == iph->saddr) && (link->dip == iph->daddr))
						{
							conn = 2;
							break;
						}
					}

					link_id = *(unsigned short *)(pkg + VPN_POSITION_LINK_ID);
					report = (link_report *)(pkg + VPN_POSITION_LINK_REPORT);
					if(!report->sendpkg)
						vpn_link_pkg[link_id] = 0;
					else if(!vpn_link_pkg[link_id])
						vpn_link_pkg[link_id] = report->sendpkg;
					report->conn = conn;
					report->reachpkg = vpn_link_pkg[link_id];
					*(unsigned int *)(pkg + VPN_POSITION_FLAG) = VPN_FLAG_REPORT_REPLY;
					cur_slot->out = kvpnd->vpn_outbound_fd;
				}
					break;
				case VPN_FLAG_REPORT_REPLY:
				{
					link_report *report;
					int i;

                    //if(!vpn_license)
                        //continue;
					link_id = *(unsigned short *)(pkg + VPN_POSITION_LINK_ID);
					report = (link_report *)(pkg + VPN_POSITION_LINK_REPORT);

					for(i = 0; i < kvpnd->vpn_outbound_link_tot; i++)
					{
						vpn_link *link = &kvpnd->vpn_outbound_links[i];
						if(link->id == link_id)
						{
							link->conn = report->conn;
							link->reply = cur_slot->time;
							link->delay = cur_slot->time - report->time;
							if(report->reachpkg >= report->sendpkg)
								link->lost = 0;
							else
								link->lost = (report->sendpkg - report->reachpkg) * 100 / report->sendpkg;
							break;
						}
					}
				}
					break;
				default:
					bak_send_pkt_num--;
					if(kvpnd->vpn_host)
					{
						if(cur_slot->flag == EF_FROM_HOST)
						{
							cur_slot->out = kvpnd->vpn_outbound_fd;
							goto next;
						}
						else
						{
							cur_slot->flag = EF_TO_HOST;
							cur_slot->out = kvpnd->vpn_outbound_fd;
						}
						/*
						if(GET_IP_DIP(cur_slot->pbuf) == kvpnd->vpn_outbound_ip)
						{
							cur_slot->flag = EF_TO_HOST;
							cur_slot->out = kvpnd->vpn_outbound_fd;
							goto next;
						}
						if(GET_ARP_DIP(cur_slot->pbuf) == kvpnd->vpn_outbound_ip)
						{
							cur_slot->flag = EF_TO_HOST;
							cur_slot->out = kvpnd->vpn_outbound_fd;
							goto next;
						}
						*/
					}
			}

		}

	next:

		cur_slot++;

	}

	return num;
}

static int vpn_rewrite(ef_slot *cur_slot, int num)
{
	void *pkg;
	unsigned short len;
	void *slice;

	unsigned int link_cur;
	unsigned int link_tot;
	vpn_link *link;

	struct iphdr *iph;
	struct udphdr *uph;
	unsigned long iph_sum = 0;
	void *vpn_data_src, *vpn_data_dst;
	int vpn_data_len;
	unsigned int pkg_id = 0;
	int i, j;

	bak_recv_pkt_num += num;
	vpn_send_pkt_num += num;
	//return 0;
	for(i = 0; i < num; i++)
	{
		pkg = cur_slot->pbuf;
		len = cur_slot->plen;

        #if 0
		if(kvpnd->vpn_host)
		{
			if(cur_slot->flag == EF_FROM_HOST)
			{
				cur_slot->out = kvpnd->vpn_backend_fd;
				cur_slot++;
				continue;
			}
			if(GET_IP_DIP(pkg) == kvpnd->vpn_backend_ip)
			{
				cur_slot->flag = EF_TO_HOST;
				cur_slot->out = kvpnd->vpn_backend_fd;
				cur_slot++;
				continue;
			}
			if(GET_ARP_DIP(pkg) == kvpnd->vpn_backend_ip)
			{
				cur_slot->flag = EF_TO_HOST;
				cur_slot->out = kvpnd->vpn_backend_fd;
				cur_slot++;
				continue;
			}
			if(IF_BROADCAST(pkg))
			{
				ef_slot host_slot;
				memcpy(host_slot.buf, pkg, len);
				host_slot.len = len;
				host_slot.flag = EF_TO_HOST;
				efio_send(kvpnd->vpn_backend_fd, &host_slot, 1);
			}
		}
		#endif

        {
            //fprintf(stderr, "rewrite:(%u)  ", len);
            //decode_pkg(pkg, len);
        }

        if(!vpn_license)
            continue;
		if(unlikely(!kvpnd->vpn_outbound_alive_link_tot))
			continue;
		j = 0;
		do
		{
			link_cur = kvpnd->vpn_outbound_alive_link_cur;
			link_tot = kvpnd->vpn_outbound_alive_link_tot;
			link = &kvpnd->vpn_outbound_links[kvpnd->vpn_outbound_alive_links[link_cur]];
			kvpnd->vpn_outbound_alive_link_cur = (link_cur + 1 == link_tot) ? 0 : (link_cur + 1);
			j++;
		}while(!link->conn && j < kvpnd->vpn_outbound_alive_link_tot);

		if(!link->conn)
			return i;

		if(unlikely(len + VPN_DATA_LEN > MAX_PKG_LEN))
		{
			link->pkg += 2;
			link->flow += (len + VPN_DATA_LEN_EXT + VPN_DATA_LEN_EXT);
			slice = cur_slot->buf;
			pkg_id = random();
			vpn_data_src = pkg;
			vpn_data_dst = slice + VPN_DATA_LEN_EXT;
			vpn_data_len = VPN_DATA_LEN_EXT;
			if(len > MAX_PKG_LEN)
			{
                //fprintf(stderr, "s1\n");
                cur_slot->plen = MAX_PKG_LEN;
            }
		}
		else
		{
			link->pkg++;
			link->flow += (len + VPN_DATA_LEN);
			slice = NULL;
			vpn_data_src = pkg;
			vpn_data_dst = pkg + len;
			vpn_data_len = VPN_DATA_LEN;
			len += VPN_DATA_LEN;
			cur_slot->plen = len;
		}

		memcpy(vpn_data_dst, vpn_data_src, vpn_data_len);
		memcpy(pkg, link->link_hdr, VPN_DATA_LEN);
		iph = (struct iphdr *)(pkg + link->iph_offset);
		uph = (struct udphdr *)(pkg + link->iph_offset + sizeof(struct iphdr));
		iph->tot_len = len - ((void *)iph - pkg);
		if(len > MAX_PKG_LEN)
		{
            //fprintf(stderr, "s2\n");
            memcpy(vpn_data_dst + vpn_data_len, pkg + MAX_PKG_LEN, len - MAX_PKG_LEN);
            iph->tot_len = MAX_PKG_LEN - ((void *)iph - pkg);
        }
        uph->len = iph->tot_len - sizeof(struct iphdr);
        uph->len = htons(uph->len);
		iph->tot_len = htons(iph->tot_len);
		iph->id = htons(iph_id);
		iph_id = (iph_id + 1 == 0xffff) ? 0 : (iph_id + 1);
		iph_sum = link->iph_sum + iph->id + iph->tot_len;
		iph_sum = (iph_sum >> 16) + (iph_sum & 0xffff);
		iph_sum += (iph_sum >> 16);
		iph->check = (unsigned short)(~iph_sum);

		if(unlikely(slice))
		{
            cur_slot->len = VPN_DATA_LEN_EXT + VPN_DATA_LEN_EXT;
			*(unsigned int *)(pkg + VPN_POSITION_FLAG) = VPN_FLAG_SLICE_EE;
			*(unsigned int *)(pkg + VPN_POSITION_PKG_ID) = pkg_id;
			memcpy(slice, pkg, VPN_DATA_LEN_EXT);
			iph = P_IPP(slice);
			uph = P_UDPP(slice);
			iph->tot_len = VPN_DATA_LEN_EXT + VPN_DATA_LEN_EXT - ((void *)iph - slice);
			if(len > MAX_PKG_LEN)
			{
                //fprintf(stderr, "s3\n");
                iph->tot_len += (len - MAX_PKG_LEN);
                cur_slot->len += (len - MAX_PKG_LEN);
            }
            uph->len = iph->tot_len - sizeof(struct iphdr);
            uph->len = htons(uph->len);
			iph->tot_len = htons(iph->tot_len);
			iph->id = htons(iph_id);
			iph_id = (iph_id + 1 == 0xffff) ? 0 : (iph_id + 1);
			iph->check = 0;
			iph->check = checksum(iph, sizeof(struct iphdr));
			*(unsigned int *)(slice + VPN_POSITION_FLAG) = VPN_FLAG_SLICE_FF;
			//efio_send(kvpnd->vpn_outbound_fd, &slice_slot, 1);

			vpn_send_pkt_num++;
		}

		cur_slot->out = kvpnd->vpn_outbound_fd;
		cur_slot++;

		//decode_pkg(pkg, len);
	}

finish:
	return num;
}

int vpn_handle(int fd, ef_slot *slot, int num)
{
	pthread_mutex_lock(&vpn_lock);

	if(fd == kvpnd->vpn_backend_fd)
	{
		//封包从vpn出去

		//if(likely(kvpnd->vpn_outbound_alive_link_tot))
		vpn_rewrite(slot, num);

	}
	else
	{
		//解包从vpn回来

		vpn_restore(slot, num);

	}

	pthread_mutex_unlock(&vpn_lock);
	return num;
}

static void * vpn_log()
{
	int log_fd;
	int log_sock;
	int log_stat;

	struct _link_record
	{
		unsigned int link_id;
		unsigned char link_info[64];
		unsigned int stat_ptr, stat_position, stat_tot, stat_cur;
		struct _link_record *prev, *next;
	};
#define MAX_STAT_P_LINK	900
	struct _stat_record
	{
		unsigned long long	time;
		unsigned long long	delay;
		unsigned long long 	pps;
		unsigned long long 	speed;
		unsigned long long	lost;
	};

    struct _link_record link_total = {0, "all,all,all,all", 0, sizeof(int)*5+VPN_MAX_LINK*sizeof(struct _link_record), 0, 0, 0, 0};
    struct _stat_record stat_total = {0};
	struct _link_record *link_record = NULL;
	unsigned char stat_bitmap[VPN_MAX_LINK] = {0};
	unsigned int link_record_position = sizeof(int)*5;
	unsigned int stat_record_position = sizeof(int)*5+VPN_MAX_LINK*sizeof(struct _link_record);
	unsigned int link_record_len = sizeof(struct _link_record);
	unsigned int stat_record_len = sizeof(struct _stat_record);

	unsigned char link_stat[1024];
	unsigned char syslog_msg[1024];
	struct sockaddr_in log_addr;
	char *mon_str[] = {"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"};

	unsigned long long vpn_recv_count = 0, vpn_recv_prev = 0, vpn_send_count = 0, vpn_send_prev = 0;
	unsigned long long bak_recv_count = 0, bak_recv_prev = 0, bak_send_count = 0, bak_send_prev = 0;
	struct timeval toc;
	int report_interval = 1000;	/* report interval */


	if ((log_sock = socket(AF_INET, SOCK_DGRAM, 0)) < 0)
	{
    	fprintf(stderr, "log socket create error!\n");
	}

	gettimeofday(&toc, NULL);
	while(vpn_run)
	{
		struct timeval now, delta;
		unsigned long long pps, vpn_recv_pps, vpn_send_pps, bak_recv_pps, bak_send_pps;

		delta.tv_sec = report_interval/1000;
		delta.tv_usec = (report_interval%1000)*1000;
		select(0, NULL, NULL, NULL, &delta);
		gettimeofday(&now, NULL);
		timersub(&now, &toc, &toc);
		vpn_recv_count = vpn_recv_pkt_num;
		vpn_send_count = vpn_send_pkt_num;
		bak_recv_count = bak_recv_pkt_num;
		bak_send_count = bak_send_pkt_num;
		pps = toc.tv_sec* 1000000 + toc.tv_usec;
		if (pps < 10000)
			continue;
		vpn_recv_pps = (vpn_recv_count - vpn_recv_prev)*1000000 / pps;
		vpn_send_pps = (vpn_send_count - vpn_send_prev)*1000000 / pps;
		bak_recv_pps = (bak_recv_count - bak_recv_prev)*1000000 / pps;
		bak_send_pps = (bak_send_count - bak_send_prev)*1000000 / pps;

		if(1 || kvpnd->check)
		{
			time_t tt;
			struct tm *tp;
			int i, j;
			int use_links = 0;

			time(&tt);
			tp = localtime(&tt);
			log_addr.sin_family = AF_INET;
			log_addr.sin_port = htons(kvpnd->log_port);
			log_addr.sin_addr.s_addr = kvpnd->log_ip;

			log_fd = open("/dev/shm/efvpn", O_RDWR | O_CREAT, S_IRWXU | S_IRWXG | S_IRWXO);
			log_stat = open("/dev/shm/stat.dat", O_RDWR | O_CREAT, S_IRWXU | S_IRWXG | S_IRWXO);
			if(log_fd != -1)
			{
				unsigned char tmp[8];
				flock(log_fd, LOCK_EX);
				sprintf(tmp, "%u\n\0", kvpnd->vpn_outbound_link_tot);
				write(log_fd, tmp, strlen(tmp));
			}
			if(log_stat != -1)
			{
                unsigned int link_tot = kvpnd->vpn_outbound_link_tot + 1;
				flock(log_stat, LOCK_EX);
				write(log_stat, &link_tot, sizeof(int));
				write(log_stat, &link_record_position, sizeof(int));
				write(log_stat, &stat_record_position, sizeof(int));
				write(log_stat, &link_record_len, sizeof(int));
				write(log_stat, &stat_record_len, sizeof(int));
				write(log_stat, &link_total, sizeof(struct _link_record));
			}


			if(link_record)
			{
				struct _link_record *cur, *del;
				cur = link_record;
				while(cur)
				{
					if(!kvpnd->vpn_outbound_id_bitmap[cur->link_id])
					{
						del = cur;
						if(cur->prev)
							cur->prev->next = cur->next;
						if(cur->next)
							cur->next->prev = cur->prev;
						if(cur == link_record)
							link_record = cur->next;
						stat_bitmap[del->stat_ptr] = 0;
						cur = cur->next;
						if(link_record)
							link_record->prev = NULL;
						free(del);
					}
					else
						cur = cur->next;
				}
			}

            memset(&stat_total, 0, sizeof(struct _stat_record));
			for(i = 0; i < kvpnd->vpn_outbound_link_tot; i++)
			{
				vpn_link *link = &kvpnd->vpn_outbound_links[i];
				struct _link_record *prev, *cur = link_record;
				struct _stat_record stat_record = {0};
				unsigned long record_cur_position, stat_cur_position;
				unsigned char gate[32];
				unsigned char mac[32];
				unsigned char sip[32];
				unsigned char dip[32];

				ip_2_str(link->gate, gate);
				mac_2_str(link->dmac, mac);
				ip_2_str(link->sip, sip);
				ip_2_str(link->dip, dip);

				while(cur && (cur->link_id != link->id))
				{
					prev = cur;
					cur = cur->next;
				}
				if(!cur)
				{
					cur = (struct _link_record *)malloc(link_record_len);
					memset(cur, 0, sizeof(*cur));
					cur->link_id = link->id;
					sprintf(cur->link_info, "%s,%d,%s,%s", gate, link->vlan, sip, dip);
					for(j = 0; j < VPN_MAX_LINK; j++)
					{
						if(!stat_bitmap[j])
						{
							cur->stat_ptr = j + 1;
							cur->stat_position = stat_record_position + cur->stat_ptr * MAX_STAT_P_LINK * stat_record_len;
							stat_bitmap[j] = 1;
							break;
						}
					}
					if(!link_record)
						link_record = cur;
					else
					{
						prev->next = cur;
						cur->prev = prev;
					}
				}
				stat_record.time = (unsigned long)tt;
				stat_record.delay = link->delay;
				stat_record.pps = link->pkg - link->lpkg;
				stat_record.speed = link->flow - link->lflow;
				stat_record.lost = link->lost;
				stat_total.time = (unsigned long)tt;
				if(link->enable && link->conn)
				{
                    use_links++;
				    stat_total.delay += stat_record.delay;
                    stat_total.pps += stat_record.pps;
                    stat_total.speed += stat_record.speed;
                    stat_total.lost += stat_record.lost;
				}
				//fprintf(stderr, "%s, %llu, %llu, %llu, %llu, %llu, %llu\n", sip, stat_record.pps, stat_record.speed, link->lpkg, link->lflow, link->pkg, link->flow);
				link->lpkg += stat_record.pps;
				link->lflow += stat_record.speed;
				stat_cur_position = cur->stat_position + cur->stat_cur * stat_record_len;
				cur->stat_tot = (cur->stat_tot < MAX_STAT_P_LINK) ? (cur->stat_tot + 1) : cur->stat_tot;
				if(log_stat != -1)
				{
					record_cur_position = lseek(log_stat, 0, SEEK_CUR);
					lseek(log_stat, stat_cur_position, SEEK_SET);
					write(log_stat, &stat_record, stat_record_len);
					lseek(log_stat, record_cur_position, SEEK_SET);
					write(log_stat, cur, sizeof(*cur));
				}
				cur->stat_cur = (cur->stat_cur + 1) % MAX_STAT_P_LINK;

				sprintf(link_stat, "%d,%s,%s,%d,%s,%s,%u,%u,%.3f,%llu,%llu,%u\n\0",
						i, gate, mac, link->vlan, sip, dip,
						link->enable, link->conn, (float)(link->delay)/1000, link->pkg, link->flow, link->lost);
				sprintf(syslog_msg, "<%u>%s %d localhost kernel: eflyVPN: link : (%d)|%s(%s) , %d| --- |%s --> %s|    %s    %s    %.3f ms    send:%llu pkg    flow:%llu byte    lost:%u%%\0",
						kvpnd->log_level, mon_str[tp->tm_mon], tp->tm_mday,
						i, gate, mac, link->vlan, sip, dip,
						link->conn ? "connect" : "disconnect", link->enable? "enable" : "disable",
						(float)(link->delay)/1000, link->pkg, link->flow, link->lost);

				if(log_fd != -1)
					write(log_fd, link_stat, strlen(link_stat));
				if((kvpnd->log) && (log_sock >= 0))
					sendto(log_sock, syslog_msg, strlen(syslog_msg), 0, (struct sockaddr *)&log_addr, sizeof(log_addr));
			}
			if(1)
			{
			    unsigned long record_position, stat_position;
			    if(!use_links) use_links = 1;
			    stat_total.delay /= use_links;
			    stat_total.lost /= use_links;
			    stat_position = link_total.stat_position + link_total.stat_cur * stat_record_len;
				link_total.stat_tot = (link_total.stat_tot < MAX_STAT_P_LINK) ? (link_total.stat_tot + 1) : link_total.stat_tot;
			    if(log_stat != -1)
				{
				    record_position = link_record_position;
					lseek(log_stat, stat_position, SEEK_SET);
					write(log_stat, &stat_total, stat_record_len);
					lseek(log_stat, record_position, SEEK_SET);
					write(log_stat, &link_total, sizeof(struct _link_record));
				}
				link_total.stat_cur = (link_total.stat_cur + 1) % MAX_STAT_P_LINK;
			}
		close_fd:
			if(log_stat != -1)
			{
				flock(log_stat, LOCK_UN);
				close(log_stat);
			}
			if(log_fd != -1)
			{
				flock(log_fd, LOCK_UN);
				close(log_fd);
			}
		}


		//fprintf(stderr, "vpn_recv_pps:%llu\tvpn_send_pps:%llu\t\tbak_recv_pps:%llu\tbak_send_pps:%llu\n",
		//	vpn_recv_pps, vpn_send_pps, bak_recv_pps, bak_send_pps);
		vpn_recv_prev = vpn_recv_count;
		vpn_send_prev = vpn_send_count;
		bak_recv_prev = bak_recv_count;
		bak_send_prev = bak_send_count;
		toc = now;
	}

	if(log_sock >= 0)
		close(log_sock);
	while(link_record)
	{
		struct _link_record *tmp = link_record;
		link_record = link_record->next;
		free(tmp);
	}

	return NULL;
}

static int vpn_check_license()
{
    FILE *license = fopen("/etc/efvpn/license", "r");
    FILE *key = fopen("/etc/efvpn/key", "r");
    RSA *rsa = NULL;
    int rsa_len;

    char license_buf[1024] = {0};
    char key_buf[1024] = {0};
    unsigned char md5[16];
    unsigned char key_md51[33] = "2ddd205ecde91edf1c2483b569281ccf";
    unsigned char key_md52[33] = {0};
    int len, i, is_valid_signature = 0;

    if(!license || !key)
    {
        fprintf(stderr, "no license or key!\n");
        goto fail;
    }

    len = 0;
    for(;;)
    {
        int r = fread(key_buf + len, 1, 1024, key);
        if(r <= 0) break;
        len += r;
    }

    MD5(key_buf, len, md5);
    for (i = 0; i < 16; i++)
    {
        sprintf(key_md52 + i * 2, "%2.2x", md5[i]);
    }

    #ifdef VPN_KEY_MD5
        if(strncasecmp(key_md52, VPN_KEY_MD5, 32))
        {
            fprintf(stderr, "key is not right!\n");
            goto fail;
        }
    #else
        if(strncasecmp(key_md52, key_md51, 32))
        {
            fprintf(stderr, "key is not right!\n");
            goto fail;
        }
    #endif // VPN_KEY_MD5

    len = 0;
    for(;;)
    {
        int r = fread(license_buf + len, 1, 1024, license);
        if(r <= 0) break;
        len += r;
    }

    fseek(key, 0, SEEK_SET);
    if ((rsa = PEM_read_RSA_PUBKEY(key, NULL, NULL, NULL)) == NULL)
    {
        ERR_print_errors_fp(stdout);
        goto fail;
    }
    else
    {
        unsigned char mac[64] = {0};
        unsigned char md51[64] = {0}, md52[64] = {0};
        SHA_CTX s;
        int i, size;
        char c[512];
        unsigned char hash[20];

        mac_2_str(kvpnd->vpn_backend_mac, mac);
        size = strlen(mac);
        mac[size++] = ' ';
        mac_2_str(kvpnd->vpn_outbound_mac, &mac[size]);
        size = strlen(mac);
        MD5(mac, size, md51);
        for (i = 0; i < 16; i++)
        {
            sprintf(md52 + i * 2, "%2.2x", md51[i]);
        }

        SHA1_Init(&s);
        SHA1_Update(&s, md52, 32);
        SHA1_Final(hash, &s);
        is_valid_signature = RSA_verify(NID_sha1, hash/*xbuf*/, 20/*strlen((char*)xbuf)*/, license_buf, len, rsa);
        //fprintf(stderr, "verify = %d\n", is_valid_signature);
        if(is_valid_signature != 1)
            is_valid_signature = 0;
        RSA_free(rsa);
    }

fail:
    if(key)
        fclose(key);
    if(license)
        fclose(license);
    return is_valid_signature;
}

static int vpn_control()
{
	int i, pkt_tot;
	ef_slot slot_report[VPN_MAX_LINK] = {0};
	ef_slot slot_gate[VPN_MAX_LINK] = {0};
	unsigned long now;

	now = efio_now();
	//watch = open("/dev/watchdog", O_WRONLY);
	for(i = 0; i < kvpnd->vpn_outbound_link_tot; i++)
	{
		vpn_link *link = &kvpnd->vpn_outbound_links[i];
		void *pkg = slot_report[i].buf;
		struct iphdr *iph = (struct iphdr *)(pkg + link->iph_offset);
		struct udphdr *uph = (struct udphdr *)(pkg + link->iph_offset + sizeof(struct iphdr));

		memcpy(pkg, link->link_hdr, VPN_DATA_LEN);
		*(unsigned int *)(pkg + VPN_POSITION_FLAG) = VPN_FLAG_REPORT_ASK;
		iph->tot_len = VPN_POSITION_LINK_REPORT + sizeof(link_report) - link->iph_offset;
		uph->len = iph->tot_len - sizeof(struct iphdr);
		uph->len = htons(uph->len);
		iph->tot_len = htons(iph->tot_len);
		iph->check = 0;
		iph->check = checksum(iph, sizeof(struct iphdr));
		link->ask = link->reply = now;
	}
	while(vpn_run)
	{
		now = efio_now();
		/*
		if (watch != -1)
        {
            write(watch, "V", 1);
        }
        */
		if(uvpnd->mod)
		{
			vpn_des *tmp;
			pthread_mutex_lock(&vpn_lock);
			kvpnd->use = 0;
			tmp = kvpnd;
			kvpnd = uvpnd;
			uvpnd = tmp;
			kvpnd->use = 1;

			for(i = 0; i < kvpnd->vpn_outbound_link_tot; i++)
			{
				vpn_link *link = &kvpnd->vpn_outbound_links[i];
				void *pkg = slot_report[i].buf;
				struct iphdr *iph = (struct iphdr *)(pkg + link->iph_offset);

				memcpy(pkg, link->link_hdr, VPN_DATA_LEN);
				*(unsigned int *)(pkg + VPN_POSITION_FLAG) = VPN_FLAG_REPORT_ASK;
				iph->tot_len = VPN_POSITION_LINK_REPORT + sizeof(link_report) - link->iph_offset;
				iph->tot_len = htons(iph->tot_len);
				iph->check = 0;
				iph->check = checksum(iph, sizeof(struct iphdr));
				link->ask = link->reply = now;
			}
			pthread_mutex_unlock(&vpn_lock);
		}

		if(1 || kvpnd->check)
		{
			link_report report;
			for(i = 0; i < kvpnd->vpn_outbound_link_tot; i++)
			{
				vpn_link *link = &kvpnd->vpn_outbound_links[i];
				if((link->runtime % 60 == 0) || (!link->complete))
				{
					slot_gate[i].len = arp_build_req(link->sip, link->smac, link->gate, slot_gate[i].buf);
					slot_gate[i].out = kvpnd->vpn_outbound_fd;
					//efio_send(kvpnd->vpn_outbound_fd, &slot_gate[i], 1);
					efio_mbdg_insert(&slot_gate[i], 1);
				}

				if(link->complete)
				{
					void *pkg = slot_report[i].buf;
					if(link->conn)
					{
						long timeout = 0;
						if(link->ask > link->reply)
							timeout = link->ask - link->reply;
						if(timeout > VPN_LINK_TIMEOUT)
							link->conn = 0;
					}
					link->ask = now;
					report.time = now;
					report.sendpkg = link->pkg;
					report.reachpkg = 0;
					memcpy(pkg, link->dmac, 6);
					memcpy(pkg + VPN_POSITION_LINK_REPORT, &report, sizeof(link_report));
					slot_report[i].len = VPN_POSITION_LINK_REPORT + sizeof(link_report);
					slot_report[i].out = kvpnd->vpn_outbound_fd;
					//efio_send(kvpnd->vpn_outbound_fd, &slot_report[i], 1);
					efio_mbdg_insert(&slot_report[i], 1);
				}

				link->runtime++;
			}
			//efio_send(kvpnd->vpn_outbound_fd, slot_report, kvpnd->vpn_outbound_link_tot);
		}

		if(kvpnd->vpn_host)
		{
			pthread_mutex_lock(&vpn_lock);
			kvpnd->vpn_backend_ip = get_dev_ip(kvpnd->vpn_backend_dev);
			kvpnd->vpn_outbound_ip = get_dev_ip(kvpnd->vpn_outbound_dev);
			pthread_mutex_unlock(&vpn_lock);
		}
		if(!vpn_license)
		{
            vpn_license = vpn_check_license();
        }
        else
        {
        }
		usleep(500000);
	}
	/*
	if (watch != -1)
        close(watch);
    */
control_over:
	return 0;
}

static int vpn_start()
{
	unsigned long mask = 1;
	int fd1, fd2;
	int i, j;

	if(vpn_cpu != -1)
	{
		mask = mask << vpn_cpu;
		sched_setaffinity(0, sizeof(mask), &mask);
	}

	kvpnd->use = 1;
	fd1 = kvpnd->vpn_backend_fd;
	fd2 = kvpnd->vpn_outbound_fd;

	vpn_run = 1;
	pthread_mutex_init(&vpn_lock, NULL);
	pthread_create(&vpn_control_t, NULL, vpn_control, NULL);
	pthread_create(&vpn_log_t, NULL, vpn_log, NULL);

	//signal(SIGINT, sigint_h);
	//signal(SIGTERM, sigint_h);
	//signal(SIGKILL, sigint_h);
	efio_mbdg_start(vpn_handle, 2, fd1, fd2);

	vpn_run = 0;
	pthread_join(vpn_log_t, NULL);
	pthread_join(vpn_control_t, NULL);

	if(pthread_mutex_trylock(&vpn_lock) != 0)
		pthread_mutex_unlock(&vpn_lock);
	pthread_mutex_destroy(&vpn_lock);

	return 0;
}

static int link_init(vpn_link *link, unsigned short id, unsigned char enable,
					unsigned int gate, unsigned char *smac, short vlan,
					unsigned int sip, unsigned int dip)
{
	struct iphdr *iph;
	struct udphdr *uph;
	unsigned short *data;
	int size;

	memset(link, 0, sizeof(vpn_link));
	link->id = id;
	link->enable = enable;
	link->gate = gate;
	memcpy(link->smac, smac, 6);
	link->vlan = vlan;
	link->sip = sip;
	link->dip = dip;

	memcpy(link->link_hdr + 6, link->smac, 6);
	if(link->vlan >= 0)
	{
		ETHTYPE(link->link_hdr) = PKT_TYPE_VL;
		VLAN(link->link_hdr) = htons(link->vlan);
		VTHTYPE(link->link_hdr) = PKT_TYPE_IP;
		link->iph_offset = 18;
		iph = (struct iphdr *)(link->link_hdr + 18);
	}
	else
	{
		ETHTYPE(link->link_hdr) = PKT_TYPE_IP;
		link->iph_offset = 14;
		iph = (struct iphdr *)(link->link_hdr + 14);
	}
	iph->version = 0x4;
	iph->ihl = 0x5;
	iph->frag_off = 0x0040;
	iph->ttl = 0xff;
	iph->protocol = PKT_TYPE_UDP;
	iph->saddr = link->sip;
	iph->daddr = link->dip;
	data = (unsigned short *)iph;
	size = sizeof(struct iphdr);
	while (size > 1)
	{
		link->iph_sum += *data++;
		size -= sizeof(unsigned short);
	}
	if (size)
	{
		link->iph_sum += *(unsigned char*)data;
	}
	uph = (struct udphdr *)((char *)iph + sizeof(struct iphdr));
	uph->source = htons(VPN_HDR_UDP_SPORT);
	uph->dest = htons(VPN_HDR_UDP_DPORT);
	uph->check = 0;
	*(unsigned int *)(link->link_hdr + VPN_POSITION_FLAG) = VPN_FLAG_NORMAL;
	*(unsigned short *)(link->link_hdr + VPN_POSITION_LINK_ID) = id;
	return 0;
}

static int vpn_config(const char *conf_file)
{
	int ret = 0;
	int i = 0, j = 0, k = 0;
	CONF_DES *cd;
	char val[128];

	conf_init(&cd);
	if(!conf_read_file(cd, conf_file, " ", 0))
		goto end;
	//conf_print_all(cd);

	if(conf_ifkey(cd, "vpn_link"))
	{
		char gate[32];
		char vlan[32];
		char sip[32];
		char dip[32];
		char enable[32];
		unsigned short link_id;

		i = 1;
		do
		{
			vpn_link *link = &kvpnd->vpn_outbound_links[kvpnd->vpn_outbound_link_tot];

			if(!conf_getval(cd, "vpn_link", gate, 32, i++))
				break;
			if(!conf_getval(cd, "vpn_link", vlan, 32, i++))
				break;
			if(!conf_getval(cd, "vpn_link", sip, 32, i++))
				break;
			if(!conf_getval(cd, "vpn_link", dip, 32, i++))
				break;
			if(!conf_getval(cd, "vpn_link", enable, 32, i++))
				break;

			do
			{
				link_id = random() % 0xffff;
			}while(kvpnd->vpn_outbound_id_bitmap[link_id]);

			link_init(link, link_id, atoi(enable), str_2_ip(gate), kvpnd->vpn_outbound_mac, atoi(vlan), str_2_ip(sip), str_2_ip(dip));

			if(link->enable)
				kvpnd->vpn_outbound_alive_links[kvpnd->vpn_outbound_alive_link_tot++] = kvpnd->vpn_outbound_link_tot;
			kvpnd->vpn_outbound_id_bitmap[link_id] = 1;
			kvpnd->vpn_outbound_ip_bitmap[(link->sip >> 16) ^ (link->sip & 0xffff)]++;

			kvpnd->vpn_outbound_link_tot++;
		}while(1);
	}
	ret = 1;
end:
	conf_uninit(&cd);
	return ret;
}

static int vpn_write_pid()
{
	int ret;
	pid_t pid = getpid();
	char buf[32] = {0};
	struct flock lock;

	vpn_pid_fd = open("/var/run/efvpnd.pid", O_RDWR | O_CREAT, S_IRWXU | S_IRWXG | S_IRWXO);
	if(-1 == vpn_pid_fd)
	{
		fprintf(stderr, "create pid file error!\n");
		return -1;
	}

	lock.l_type = F_WRLCK;
	lock.l_whence = SEEK_SET;
	lock.l_start = 1;
	lock.l_len = 1;
	ret = fcntl(vpn_pid_fd, F_SETLK, &lock);
	if(ret < 0)
	{
		close(vpn_pid_fd);
		return 0;
	}
	else
	{
		sprintf(buf, "%d\n", (int)pid);
		if(write(vpn_pid_fd, buf, strlen(buf)) == strlen(buf))
			return 1;
		else
			close(vpn_pid_fd);
	}
	return 0;
}

static int vpn_close_pid()
{
    if(vpn_pid_fd != -1)
        close(vpn_pid_fd);
}

static int vpn_get_des()
{
	pvpn_key = ftok("/etc/efvpn", (int)'a');
	pvpn_id = shmget(pvpn_key, sizeof(vpn_des) * 2, IPC_CREAT | 0777);
	pvpnd = (vpn_des *)shmat(pvpn_id, NULL, 0);
	if((int)pvpnd == -1)
	{
		shmctl(pvpn_id, IPC_RMID, NULL);
		return 0;
	}
	memset(pvpnd, 0, sizeof(vpn_des) * 2);
	kvpnd = pvpnd;
	uvpnd = pvpnd + 1;
	return 1;
}

static int vpn_rel_des()
{
    if(pvpnd)
    {
        shmdt(pvpnd);
        shmctl(pvpn_id, IPC_RMID, NULL);
    }
}

static int vpn_init()
{
	if(vpn_write_pid() != 1)
		return -1;
	if(!vpn_get_des())
		return -1;

	signal(SIGINT, sigint_h);
	signal(SIGTERM, sigint_h);
	signal(SIGKILL, sigint_h);
	//fprintf(stderr, "vpn init wait 10s...\n");
	strncpy(kvpnd->vpn_backend_dev, dev1, sizeof(kvpnd->vpn_backend_dev));
	strncpy(kvpnd->vpn_outbound_dev, dev2, sizeof(kvpnd->vpn_outbound_dev));
	if(vpn_dir & EF_ENABLE_HOST)
		kvpnd->vpn_host = 1;
	kvpnd->vpn_backend_fd = efio_init(kvpnd->vpn_backend_dev, EF_CAPTURE_NETMAP, vpn_dir, 1);
	kvpnd->vpn_outbound_fd = efio_init(kvpnd->vpn_outbound_dev, EF_CAPTURE_NETMAP, vpn_dir, 0);
	if((kvpnd->vpn_backend_fd == -1) || (kvpnd->vpn_outbound_fd == -1))
		return -1;
	get_dev_mac(dev1, kvpnd->vpn_backend_mac);
	get_dev_mac(dev2, kvpnd->vpn_outbound_mac);
	vpn_config("/etc/efvpn/conf");
	memcpy(uvpnd, kvpnd, sizeof(vpn_des));

	slice_ee = malloc(SLICE_EE_SLOT_LEN * SLICE_EE_SLOT_NUM);
	slice_ff = malloc(SLICE_FF_SLOT_LEN * SLICE_FF_SLOT_NUM);

	return 0;
}

static int vpn_tini()
{
	efio_tini(kvpnd->vpn_backend_fd);
	efio_tini(kvpnd->vpn_outbound_fd);

    if(slice_ee)
        free(slice_ee);
    if(slice_ff)
        free(slice_ff);

	vpn_rel_des();
	vpn_close_pid();
}

static void usage()
{
	fprintf(stderr, "Run  Vpn : efvpnd -b p1p1 -v p1p2 [-c num] -k (p1p1 is internal dev, p1p2 is vpn dev, -c run at num of cpu, -k support kernel tcp/ip stack)\n");
	fprintf(stderr, "Stop Vpn : efvpnd -s\n");
}

int main(int argc, char *argv[])
{
	int ch;
	int stop = 0;

	while ( (ch = getopt(argc, argv, "b:v:c:hT:sT:kT")) != -1)
	{

		switch(ch)
		{
			case 'b':
				dev1 = optarg;
				break;
			case 'v':
				dev2 = optarg;
				break;
			case 's':
				stop = 1;
				break;
			case 'c':
				vpn_cpu = atoi(optarg);
				break;
			case 'h':
				usage();
				return 0;
				break;
			case 'k':
				vpn_dir |= EF_ENABLE_HOST;
				break;
			default:
				fprintf(stderr, "error option!\n");
				return -1;
		}
	}

	if(stop)
	{
		int ret = vpn_write_pid();

		if(ret == 0)
		{
			int rd = open("/var/run/efvpnd.pid", O_RDONLY);
			if(rd)
			{
				unsigned char pid[32];
				char cmd[128];
				if(read(rd, pid, sizeof(pid)))
				{
					sprintf(cmd, "/bin/kill %s", pid);
					system(cmd);
				}
				close(rd);
			}

		}
		else if(ret == 1)
			vpn_close_pid();

		return 0;
	}

	if(!dev1 || !dev2)
	{
		fprintf(stderr, "Error option!\n");
		return -1;
	}

	if(daemon(1, 1) >= 0)
	{
		if(-1 != vpn_init())
			vpn_start();
		else
			fprintf(stderr, "vpn run error!\n");
        vpn_tini();
	}
	else
		fprintf(stderr, "cannot create run vpn process!\n");
	return 0;
}

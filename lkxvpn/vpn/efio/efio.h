#ifndef EFIO
#define EFIO

#ifndef MAX_SLOT_LEN
#define MAX_SLOT_LEN		1518
#endif


#define EF_CAPTURE_NETMAP	1
#define EF_CAPTURE_PFRING	2

#define EF_ENABLE_READ		(1 << 1)
#define EF_ENABLE_SEND		(1 << 2)
#define EF_ENABLE_HOST		(1 << 3)

#define EF_FLUSH_READ		(1 << 1)
#define EF_FLUSH_SEND		(1 << 2)

#define EF_FROM_HOST		1
#define EF_TO_HOST			2

#pragma pack(push, 8)
typedef struct _ef_slot
{
	unsigned char	buf[MAX_SLOT_LEN];
	unsigned short 	len;
	unsigned char	*pbuf;
	unsigned short	plen;
	unsigned char	in;
	unsigned char	out;
	unsigned long	time;
	int				flag;
}ef_slot;
#pragma pack(pop)


int efio_read(int fd, ef_slot *slot, int num);

int efio_send(int fd, ef_slot *slot, int num);

#if 0
int efio_poll();

int efio_flush();
#endif

int efio_flush(int fd, int flush_type, int flush_wait);

int efio_bdg_start(int fd1, int fd2, void *handle);

int efio_bdg_stop(int fd1, int fd2);

int efio_mbdg_insert(ef_slot *slot, int num);

int efio_mbdg_start(void *_handle, int num, ...);

int efio_mbdg_stop();

int efio_init(char *dev, int use, int direction, int promisc);

int efio_tini(int fd);

unsigned long efio_now();

void efio_link();

int efio_getdev_byfd(int n, unsigned char *dev, int len);

int efio_getfd_bydev(unsigned char *dev);


#endif

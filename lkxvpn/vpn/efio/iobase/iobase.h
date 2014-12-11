#ifndef IOBASE
#define IOBASE

#ifndef MAX_SLOT_LEN
#define MAX_SLOT_LEN		1518
#endif

#define IO_ENABLE_READ		(1 << 1)
#define IO_ENABLE_SEND		(1 << 2)
#define IO_ENABLE_HOST		(1 << 3)

#define IO_FLUSH_READ		(1 << 1)
#define IO_FLUSH_SEND		(1 << 2)

#define IO_FROM_HOST		1
#define IO_TO_HOST			2

#pragma pack(push, 8)
typedef struct _io_slot
{
	unsigned char	buf[MAX_SLOT_LEN];
	unsigned short 	len;
	unsigned char	*pbuf;
	unsigned short	plen;
	unsigned char	in;
	unsigned char	out;
	unsigned long	time;
	int				flag;
}io_slot;
#pragma pack(pop)


/********		netmap api		 **********/
unsigned long iobase_now();

/********						 **********/


/********		netmap api		 **********/
void *ef_netmap_open(char *dev, int direction, int promisc);

int ef_netmap_close(void *_fd);

int ef_netmap_read(void *_fd, io_slot *slot, int num);

int ef_netmap_read_nocopy(void *_fd, io_slot *slot, int num);

int ef_netmap_send(void *_fd, io_slot *slot, int num);

int ef_netmap_flush(void *_fd, int flush_type, int flush_wait);

int ef_netmap_bdg_start(int n0, int n1, void *_fd0, void *_fd1, void *_handle);
int ef_netmap_bdg_stop();
int ef_netmap_mbdg_start(int *n, void **_fd, int num, void *_handle);
int ef_netmap_mbdg_stop();

/********						 **********/



/********		pfring api		 **********/
void *ef_pfring_open(char *dev, int direction, int promisc);

int ef_pfring_close(void *_fd);

int ef_pfring_read(void *_fd, io_slot *slot, int num);

int ef_pfring_send(void *_fd, io_slot *slot, int num);

int ef_pfring_flush(void *_fd, int flush_type, int flush_wait);

/********						 **********/

#endif

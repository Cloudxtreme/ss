#include <efio.h>
#include <iobase.h>
#include <stdio.h>
#include <stdlib.h>
#include <stdarg.h>

#define MAX_FD	32

typedef struct _ef_fd
{
	int			id;
	int			use;
	char		dev[32];
	char		dev_len;
	int			direction;
	int			promisc;
	int			bdg;
	void		*bdg_handle;
	void		*fd;
	void*		(*open)		    (char *dev, int direction, int promisc);
	int			(*close)	    (void *fd);
	int			(*read)		    (void *fd, io_slot *slot, int num);
	int			(*read_nocopy)	(void *fd, io_slot *slot, int num);
	int			(*send)		    (void *fd, io_slot *slot, int num);
	int			(*flush)	    (void *fd, int flush_type, int flush_wait);
}ef_fd;

typedef int (*ef_handle)(int fd, ef_slot *slot, int num);

static ef_fd *g_fd[MAX_FD] = {NULL};
static int fd_total = 0;


/* ------------------------------------------------------------- */
static ef_fd *get_fd(int n)
{
	if((n <= 0) || (n >= MAX_FD))
		return NULL;
	return g_fd[n];
}

static int efio_open(ef_fd *fd)
{
	int ret = 0;

	fd->fd = fd->open(fd->dev, fd->direction, fd->promisc);
	if(!(fd->fd))
		ret = -1;
	return ret;
}

static int efio_close(ef_fd *fd)
{
	int ret = 0;

	ret = fd->close(fd->fd);
	return ret;
}

int efio_init(char *dev, int use, int direction, int promisc)
{
	int n;
	ef_fd *fd = (ef_fd *)malloc(sizeof(ef_fd));

	if((!dev) || (!strlen(dev)))
		goto err;

#if 0
	for(n = 0; n < MAX_FD; n++)
	{
		if((g_fd[n]) && (!strcmp(g_fd[n]->dev, dev)))
			goto err;
	}
#endif

	memset(fd, 0, sizeof(ef_fd));
	fd->use = use;
	fd->direction = direction;
	fd->promisc = promisc;
	strncpy(fd->dev, dev, sizeof(fd->dev));
	fd->dev_len = strlen(dev);

	switch(use)
	{
		case EF_CAPTURE_NETMAP:
			fd->open = ef_netmap_open;
			fd->close = ef_netmap_close;
			fd->read = ef_netmap_read;
			fd->read_nocopy = ef_netmap_read_nocopy;
			fd->send = ef_netmap_send;
			fd->flush = ef_netmap_flush;
			break;

		case EF_CAPTURE_PFRING:
			fd->open = ef_pfring_open;
			fd->close = ef_pfring_close;
			fd->read = ef_pfring_read;
			fd->send = ef_pfring_send;
			fd->flush = ef_pfring_flush;
			break;

		default:
			goto err;
	}

	if(-1 == efio_open(fd))
		goto err;

	for(n = 1; n < MAX_FD && g_fd[n]; n++) ;
	if(n < MAX_FD)
		g_fd[n] = fd;
	else
		goto err;
	fd->id = n;
	if(!fd_total)
		iobase_start_timethread();
	fd_total++;
	return n;

err:
	free(fd);
	return -1;
}

int efio_tini(int n)
{
	ef_fd *fd;

	if(!(fd = get_fd(n)))
		goto err;

	efio_close(fd);
	free(fd);
	g_fd[n] = NULL;
	fd_total--;
	if(!fd_total)
		iobase_stop_timethread();
	return 0;

err:
	return -1;
}

unsigned long efio_now()
{
	return iobase_now();
}

void efio_link()
{
    return;
}

int efio_getdev_byfd(int n, unsigned char *dev, int len)
{
	ef_fd *fd;

	if(!dev)
		return -1;
	if(!(fd = get_fd(n)))
		return 0;
	if(len > fd->dev_len)
		len = fd->dev_len;
	memcpy(dev, fd->dev, len);
	return 1;
}

int efio_getfd_bydev(unsigned char *dev)
{
	int i;

	for(i = 1; i < MAX_FD; i++)
	{
		if(g_fd[i] && !memcmp(g_fd[i]->dev, dev, g_fd[i]->dev_len))
			return i;
	}
	return 0;
}

int efio_read(int n, ef_slot *slot, int num, int copy)
{
	ef_fd *fd;

	if(!(fd = get_fd(n)))
	{
		return -1;
    }
	if(slot == NULL)
	{
		return -1;
    }
	if(!(fd->direction & EF_ENABLE_READ))
		return 0;
    if(copy)
        return fd->read(fd->fd, (io_slot *)slot, num);
    else
        return fd->read_nocopy(fd->fd, (io_slot *)slot, num);
}

int efio_send(int n, ef_slot *slot, int num)
{
	ef_fd *fd;

	if(!(fd = get_fd(n)))
		return -1;
	if(slot == NULL)
		return -1;
	if(!(fd->direction & EF_ENABLE_SEND))
		return 0;
	return fd->send(fd->fd, (io_slot *)slot, num);
}

int efio_flush(int n, int flush_type, int flush_wait)
{
	ef_fd *fd;

	if(!(fd = get_fd(n)))
		return -1;
	if(flush_wait < 0)
		return -1;
	if(fd->bdg)
		return 0;
	if(!(flush_type & EF_FLUSH_READ) && !(flush_type & EF_FLUSH_SEND))
		return 0;
	if(!(fd->direction & EF_ENABLE_READ) && (flush_type & EF_FLUSH_READ))
		return 0;
	if(!(fd->direction & EF_ENABLE_SEND) && (flush_type & EF_FLUSH_SEND))
		return 0;
	return fd->flush(fd->fd, flush_type, flush_wait);
}

static int efio_bdg_cbk(int n, io_slot *slot, int num)
{
	ef_fd *fd = get_fd(n);
	ef_handle handle = (ef_handle)fd->bdg_handle;


	handle(n, (ef_slot *)slot, num);
}

int efio_bdg_stop(int n1, int n2)
{
	ef_fd *fd1;
	ef_fd *fd2;

	if(!(fd1 = get_fd(n1)) || !(fd2 = get_fd(n2)))
		return -1;
	if((fd1->use == EF_CAPTURE_NETMAP) && (fd2->use == EF_CAPTURE_NETMAP))
		ef_netmap_bdg_stop();
	else
	{
	}
	return 0;
}

int efio_bdg_start(int n1, int n2, void *_handle)
{
	ef_handle handle=(ef_handle)_handle;
	ef_slot slot[1024] = {0};
	ef_fd *fd1;
	ef_fd *fd2;

	if(!(fd1 = get_fd(n1)) || !(fd2 = get_fd(n2)))
		return -1;
	fd1->bdg = 1;
	fd2->bdg = 1;
	fd1->bdg_handle = _handle;
	fd2->bdg_handle = _handle;
	if((fd1->use == EF_CAPTURE_NETMAP) && (fd2->use == EF_CAPTURE_NETMAP))
	{
		//ef_netmap_bdg(n1, n2, fd1->fd, fd2->fd, _handle);
		ef_netmap_bdg_start(n1, n2, fd1->fd, fd2->fd, efio_bdg_cbk);
	}
	else
	{
	}
}

int efio_mbdg_stop()
{
	ef_netmap_mbdg_stop();
	return 0;
}

int efio_mbdg_insert(ef_slot *slot, int num)
{
	return ef_netmap_mbdg_insert((io_slot *)slot, num);
}

int efio_mbdg_start(void *_handle, int num, ...)
{
	va_list arg_ptr;
	int n[MAX_FD] = {0};
	ef_fd *fd[MAX_FD] = {0};
	void *io_fd[MAX_FD] = {0};
	int i;

	va_start(arg_ptr, num);
	for(i = 0; i < num; i++)
	{
		n[i] = va_arg(arg_ptr, int);
	}
	va_end(arg_ptr);
	// 需要加判断fd是否为同一设备的句柄，mbdg暂不允许这种情况，如需要在同一设备的收发队列之间交换数据，只需要传入一个句柄
	for(i = 0; i < num; i++)
	{
		if(!(fd[i] = get_fd(n[i])))
			return -1;
		if(fd[i]->use != EF_CAPTURE_NETMAP)
			return -1;
		fd[i]->bdg = 1;
		fd[i]->bdg_handle = _handle;
		io_fd[i] = fd[i]->fd;
	}
	ef_netmap_mbdg_start(n, io_fd, num, efio_bdg_cbk);
}

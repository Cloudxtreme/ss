#include "net_select.h"
#include "list.h"
#include "tcp_socket.h"
#include <assert.h>

typedef struct _net_poll_select_t
{
	fd_set	r_fd_set, w_fd_set, e_fd_set;
	fd_set	_r_fd_set, _w_fd_set, _e_fd_set;
	struct timeval	select_timeout;

}net_poll_select_t;


int init(struct _net_poll_t *net_poll, int max_fds)
{
	net_poll_select_t *net_select = (net_poll_select_t*)net_poll->data;

	net_poll->register_fds.cnt = 0;
	net_poll->register_fds.fd_info = malloc(sizeof(fd_info_t) * max_fds);
	memset(net_poll->register_fds.fd_info, 0, sizeof(fd_info_t) * max_fds);

	net_poll->event_fds.cnt = 0;
	net_poll->event_fds.fd_info = malloc(sizeof(fd_info_t) * max_fds);
	memset(net_poll->event_fds.fd_info, 0, sizeof(fd_info_t) * max_fds);

	net_poll->max_fds = max_fds;

	FD_ZERO(&net_select->r_fd_set);
	FD_ZERO(&net_select->w_fd_set);
	FD_ZERO(&net_select->e_fd_set);

	return 0;
}

int release(struct _net_poll_t *net_poll)
{
	net_poll_select_t *net_select = (net_poll_select_t*)net_poll->data;
	free(net_poll->register_fds.fd_info);
	free(net_poll->event_fds.fd_info);
	return 0;
}

int add(struct _net_poll_t *net_poll, int fd, int event, void *ctx, void *data)
{
	net_poll_select_t *net_select = (net_poll_select_t*)net_poll->data;
	fdset_t *fdset =  &net_poll->register_fds;

	if( ! fdset->fd_info[fd].fd ) { fdset->cnt++;}

	assert( ctx != NULL );

	fdset->fd_info[fd].fd = fd;
	fdset->fd_info[fd].event = event;
	fdset->fd_info[fd].ctx = ctx;
	fdset->fd_info[fd].data = data;

	FD_CLR(fd, &net_select->r_fd_set);
	FD_CLR(fd, &net_select->w_fd_set);

	if(	event & NETPOLL_EVENT_READ ) { FD_SET(fd, &net_select->r_fd_set); }
	if(	event & NETPOLL_EVENT_WRITE ) { FD_SET(fd, &net_select->w_fd_set); }

	return 0;
}

int del(struct _net_poll_t *net_poll, int fd)
{
	net_poll_select_t *net_select = (net_poll_select_t*)net_poll->data;
	fdset_t *fdset =  &net_poll->register_fds;

	if( fdset->fd_info[fd].fd ) { fdset->cnt--; }

	fdset->fd_info[fd].fd = 0;
	fdset->fd_info[fd].event = NETPOLL_EVENT_NULL;
	fdset->fd_info[fd].ctx = NULL;
	fdset->fd_info[fd].data = NULL;

	FD_CLR(fd, &net_select->r_fd_set);
	FD_CLR(fd, &net_select->w_fd_set);

	return 0;
}

int poll(struct _net_poll_t *net_poll, struct timeval *timeout)
{
	net_poll_select_t *net_select = (net_poll_select_t*)net_poll->data;
	int ret, i, idx;
	fd_info_t *fdinfo = NULL;
	
	//init fdsets
	net_select->select_timeout = *timeout;
	memcpy(&net_select->_r_fd_set, &net_select->r_fd_set, sizeof(fd_set));
	memcpy(&net_select->_w_fd_set, &net_select->w_fd_set, sizeof(fd_set));
	memcpy(&net_select->_e_fd_set, &net_select->e_fd_set, sizeof(fd_set));

	//poll
	ret = select(FD_SETSIZE,
				&net_select->_r_fd_set, 
				&net_select->_w_fd_set, 
				&net_select->_e_fd_set, 
				&net_select->select_timeout);
	if( ret <= 0 ) { return ret; }

	idx = 0;
	net_poll->event_fds.cnt = 0;
	for( i = 0; i < net_poll->max_fds; i++ ) {
		
		fdinfo = &net_poll->event_fds.fd_info[idx];
		memset(fdinfo, 0, sizeof(fd_info_t));
		
		if( net_poll->register_fds.fd_info[i].fd ) {
			if( FD_ISSET(net_poll->register_fds.fd_info[i].fd, &net_select->_r_fd_set) ) {
				fdinfo->fd = net_poll->register_fds.fd_info[i].fd;
				fdinfo->event = NETPOLL_EVENT_READ;
				fdinfo->ctx = net_poll->register_fds.fd_info[i].ctx;
				fdinfo->data = net_poll->register_fds.fd_info[i].data;
			}
			if( FD_ISSET(net_poll->register_fds.fd_info[i].fd, &net_select->_w_fd_set) ) {
				fdinfo->fd = net_poll->register_fds.fd_info[i].fd;
				fdinfo->event |= NETPOLL_EVENT_WRITE;
				fdinfo->ctx = net_poll->register_fds.fd_info[i].ctx;
				fdinfo->data = net_poll->register_fds.fd_info[i].data;
			}
			if( fdinfo->fd ) { 
				net_poll->event_fds.cnt++;
				idx++; 
			}
		}
	}

	return ret;
}

int get_fdset(struct _net_poll_t *net_poll, int fd, int *event, void **ctx, void **data)
{
	int i;
	fdset_t *fdset = &net_poll->event_fds;
	for( i = 0; i < fdset->cnt; i++ ) {
		if( fd == fdset->fd_info[i].fd ) {
			*event = fdset->fd_info[i].event;
			if( ctx) { *ctx = fdset->fd_info[i].ctx; }
			if( data ) { *data = fdset->fd_info[i].data; }
			return fd;
		}
	}
	return 0;
}

int size(struct _net_poll_t *net_poll)
{
	net_poll_select_t *net_select = (net_poll_select_t*)net_poll->data;
	fdset_t *fdset =  &net_poll->register_fds;
	return fdset->cnt;
}

net_poll_t* create_net_select()
{
	net_poll_t *net_poll = malloc(sizeof(net_poll_t));
	net_poll->init = init;
	net_poll->release = release;
	net_poll->add = add;
	net_poll->del = del;
	net_poll->poll = poll;
	net_poll->get_fdset = get_fdset;
	net_poll->size = size;

	net_poll->max_fds = 1024;
	net_poll->ctx = NULL;
	net_poll->data = malloc(sizeof(net_poll_select_t));

	return net_poll;
}


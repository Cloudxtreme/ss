#pragma once
#include "misc.h"

struct _fd_info_t
{
	int fd;
	int event;
	void *ctx;
	void *data;

};

struct _fdset_t
{
	int cnt;
	fd_info_t *fd_info;

};

struct _net_poll_t
{
	int (*init)(struct _net_poll_t *net_poll, int max_fds);
	int (*release)(struct _net_poll_t *net_poll);
	int (*add)(struct _net_poll_t *net_poll, int fd, int event, void *ctx, void *data);
	int (*del)(struct _net_poll_t *net_poll, int fd);
	int (*poll)(struct _net_poll_t *net_poll, struct timeval *timeout);
	int (*get_fdset)(struct _net_poll_t *net_poll, int fd, int *event, void **ctx, void **data);
	int (*size)(struct _net_poll_t *net_poll);

	//记录注册fd数组
	fdset_t register_fds;

	//记录有读写事件的fd数组
	fdset_t event_fds;

	int max_fds;
	void *ctx;
	void *data;

};


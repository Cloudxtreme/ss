#pragma once
#include "tcp_socket.h"
#include "net_poll.h"

typedef struct _net_dev_t
{
	tcp_socket_t tcp_listen_socket;
	struct sockaddr_in addr;

	//notify listener
	int (*net_dev_accept_cb)(struct _net_dev_t* net_dev);

}net_dev_t;

net_dev_t* create_net_dev();
int net_dev_bind(net_dev_t *net_dev, const char *addr, int port);
int net_dev_listen(net_dev_t *net_dev, int backlog);


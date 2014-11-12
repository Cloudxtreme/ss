#include "net_dev.h"
#include "net_select.h"

int net_dev_accept_cb(struct _net_dev_t* net_dev)
{
	return 0;
}

int tcp_socket_accept_cb(net_poll_t *net_poll, socket_t *socket)
{
	tcp_socket_t *tcp_socket = (tcp_socket_t*)socket;
	net_dev_t *net_dev = (net_dev_t*)tcp_socket->socket.ctx;
	if( net_dev->net_dev_accept_cb ) {
		net_dev->net_dev_accept_cb(net_dev);
	}
	return 0;
}

net_dev_t* create_net_dev()
{
	net_dev_t *net_dev = malloc(sizeof(net_dev_t));
	tcp_socket_create(&net_dev->tcp_listen_socket, 0);
	net_dev->tcp_listen_socket.socket.event_read_net_poll_cb = tcp_socket_accept_cb;
	net_dev->tcp_listen_socket.socket.event_write_net_poll_cb = NULL;
	net_dev->tcp_listen_socket.tcp_conn = NULL;
	net_dev->tcp_listen_socket.socket.ctx = net_dev;
	net_dev->net_dev_accept_cb = NULL;
	return net_dev;
}

int net_dev_bind(net_dev_t *net_dev, const char *addr, int port)
{
	return tcp_socket_bind(&net_dev->tcp_listen_socket, addr, port);
}

int net_dev_listen(net_dev_t *net_dev, int backlog)
{
	return tcp_socket_listen(&net_dev->tcp_listen_socket, backlog);
}


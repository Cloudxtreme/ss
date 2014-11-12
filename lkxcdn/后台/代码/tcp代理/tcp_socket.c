#include "tcp_socket.h"
#include "misc.h"

int tcp_socket_create(tcp_socket_t *tcp_socket, int fd)
{
	if( fd > 0 ) {
		tcp_socket->socket.fd = fd;
	} else {
		tcp_socket->socket.fd = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
	}
	misc_set_fd_noblock(tcp_socket->socket.fd);
	tcp_socket->socket.type = SOCKET_TYPE_TCP;
	tcp_socket->socket.event_read_net_poll_cb = NULL;
	tcp_socket->socket.event_write_net_poll_cb = NULL;
	tcp_socket->socket.ctx = NULL;
	tcp_socket->socket.data = NULL;

	return 0;
}

int tcp_socket_create_socket()
{
	return socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
}

int tcp_socket_bind(tcp_socket_t *tcp_socket, const char *addr, int port)
{
	struct sockaddr_in staddr;
	memset(&staddr, 0, sizeof(struct sockaddr_in));
	staddr.sin_family = AF_INET;
	staddr.sin_addr.s_addr = inet_addr(addr);
	staddr.sin_port = htons(port);

	return bind(tcp_socket->socket.fd, (struct sockaddr*)&staddr, sizeof(staddr));
}

int tcp_socket_listen(tcp_socket_t *tcp_socket, int backlog)
{
	return listen(tcp_socket->socket.fd, backlog);
}

int tcp_socket_conn(tcp_socket_t *tcp_socket, struct sockaddr_in *addr)
{
	return connect(tcp_socket->socket.fd, (struct sockaddr*)addr, sizeof(struct sockaddr_in));
}

int tcp_socket_accept(tcp_socket_t *tcp_socket, struct sockaddr_in *addr)
{
	int size = sizeof(struct sockaddr_in);
	return accept(tcp_socket->socket.fd, (struct sockaddr*)addr, &size);
}

int tcp_socket_recv(tcp_socket_t *tcp_socket, char *buff, int size)
{
	int ret = recv(tcp_socket->socket.fd, buff, size, 0);
	//printf("tcp_socket_recv fd=[%d] ret=%d size=%d \n", tcp_socket->socket.fd, ret, size);
	if( ret == SOCKET_ERROR ) {
		//printf("tcp_socket_recv fd=[%d] %d \n", tcp_socket->socket.fd, misc_get_error());
	}
	return ret;
}

int tcp_socket_send(tcp_socket_t *tcp_socket, char *buff, int size)
{
	int ret = send(tcp_socket->socket.fd, buff, size, 0);
	//printf("tcp_socket_send %s \n", buff);
	//printf("tcp_socket_send fd=[%d] ret=%d size=%d \n", tcp_socket->socket.fd, ret, size);
	if( ret == SOCKET_ERROR ) {
		//printf("tcp_socket_send fd=[%d] %d \n", tcp_socket->socket.fd, misc_get_error());
	}
	return ret;
}

int tcp_socket_close(tcp_socket_t *tcp_socket)
{
	return misc_close_socket(tcp_socket->socket.fd);
}



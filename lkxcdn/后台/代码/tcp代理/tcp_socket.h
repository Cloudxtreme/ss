#pragma once
#include "misc.h"
#include "typedef.h"

struct _tcp_socket_t
{
	socket_t socket;
	struct _tcp_conn_t *tcp_conn;

};

int tcp_socket_create(tcp_socket_t *tcp_socket, int fd);
int tcp_socket_create_socket();
int tcp_socket_bind(tcp_socket_t *tcp_socket, const char *addr, int port);
int tcp_socket_listen(tcp_socket_t *tcp_socket, int backlog);
int tcp_socket_conn(tcp_socket_t *tcp_socket, struct sockaddr_in *addr);
int tcp_socket_accept(tcp_socket_t *tcp_socket, struct sockaddr_in *addr);
int tcp_socket_recv(tcp_socket_t *tcp_socket, char *buff, int size);
int tcp_socket_send(tcp_socket_t *tcp_socket, char *buff, int size);
int tcp_socket_close(tcp_socket_t *tcp_socket);


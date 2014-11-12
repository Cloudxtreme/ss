#pragma once
#include "misc.h"
#include "typedef.h"

typedef struct _udp_socket_t
{
	socket_t socket;
}udp_socket_t;

int udp_socket_create(udp_socket_t *udp_socket);
int udp_socket_bind(udp_socket_t *udp_socket, const char *addr, int port);
int udp_socket_recvfrom(udp_socket_t *udp_socket, char *buff, int size, struct sockaddr_in *addr);
int udp_socket_sendto(udp_socket_t *udp_socket, char *buff, int size, struct sockaddr_in *addr);
int udp_socket_close(udp_socket_t *udp_socket);


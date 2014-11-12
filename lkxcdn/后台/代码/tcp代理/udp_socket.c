#include "udp_socket.h"
#include "misc.h"

int udp_socket_create(udp_socket_t *udp_socket)
{
	udp_socket->socket.fd = socket(AF_INET, SOCK_DGRAM, IPPROTO_UDP);
	udp_socket->socket.type = SOCKET_TYPE_UDP;
	return 0;
}

int udp_socket_bind(udp_socket_t *udp_socket, const char *addr, int port)
{
	struct sockaddr_in staddr;
	memset(&staddr, 0, sizeof(struct sockaddr_in));
	staddr.sin_family = AF_INET;
	staddr.sin_addr.s_addr = inet_addr(addr);
	staddr.sin_port = htons(port);

	return bind(udp_socket->socket.fd, (struct sockaddr*)&staddr, sizeof(staddr));
}

int udp_socket_recvfrom(udp_socket_t *udp_socket, char *buff, int size, struct sockaddr_in *addr)
{
	int addr_size = sizeof(struct sockaddr_in);
	return recvfrom(udp_socket->socket.fd, buff, size, 0, (struct sockaddr*)addr, &addr_size);
}

int udp_socket_sendto(udp_socket_t *udp_socket, char *buff, int size, struct sockaddr_in *addr)
{
	return sendto(udp_socket->socket.fd, buff, size, 0, (struct sockaddr*)addr, sizeof(struct sockaddr));
}

int udp_socket_close(udp_socket_t *udp_socket)
{
	return misc_close_socket(udp_socket->socket.fd);
}





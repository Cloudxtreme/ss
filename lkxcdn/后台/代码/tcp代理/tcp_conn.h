#pragma once
#include "tcp_socket.h"

struct _tcp_conn_t
{
	tcp_socket_t tcp_socket;

	enum tcp_CONN_STATUS { 
		TCP_CONN_STATUS_NULL = 0, 
		TCP_CONN_STATUS_CONNECTING = 1, 
		TCP_CONN_STATUS_CONNECTED = 2, 
		TCP_CONN_STATUS_RESTART_CONNECTING = 4,
		TCP_CONN_STATUS_CLOSE = 8 
	} status;

	void *ctx;
	void *data;

	//call back
	int (*event_read_cb)(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error);
	int (*event_write_cb)(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error);

	int (*send)(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, char *buff, int size);

	struct sockaddr_in addr;

	//recv
	int rx_size;
	int rx_head;
	int rx_tail;
	char *rx_buff;

	//send
	int tx_size;
	int tx_head;
	int tx_tail;
	char *tx_buff;

};

tcp_conn_t* create_tcp_conn(int fd);
void destroy_tcp_conn(struct _tcp_conn_t *tcp_conn);
int tcp_conn_send(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, char *buff, int size);
void tcp_conn_reset_xbuff(struct _tcp_conn_t *tcp_conn);

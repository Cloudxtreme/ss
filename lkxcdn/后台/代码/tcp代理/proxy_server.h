#pragma once
/*
#include <WinSock2.h>

#define MAX_FD_CNT	1000
#define MAX_BUFF_SIZE 10*1024


struct conn_t
{
	int fd;

	int client_idx;
	int backend_idx;
	char sz_backend_host[100];
	sockaddr_in backend_addr;

	enum FD_EVENT { NETPOLL_EVENT_NULL = 0, NETPOLL_NETPOLL_EVENT_READ = 1, NETPOLL_EVENT_WRITE = 2, } event;
	enum CONN_TYPE { TYPE_NULL = 0, TYPE_CLIENT = 1, TYPE_BACKEND = 2, } type;

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

int proxy_server_init();
int proxy_server_start();
int proxy_server_stop();

*/
#include "proxy_server.h"
#include "misc.h"
#include <assert.h>
#include <stdio.h>
/*

int listen_fd;
struct conn_t conns[MAX_FD_CNT];
static fd_set	r_fd_set, w_fd_set, e_fd_set;
static timeval	select_timeout;

int _add_new_conn(int fd, int type);
int _del_new_conn(int fd, int idx);
int _fd_read(int fd, int idx);
int _client_read(int fd, int idx);
int _backend_read(int fd, int idx);
int _fd_write(int fd, int idx);

int proxy_server_init()
{
	int i;
	for( i = 0; i < MAX_FD_CNT; i++ ) {
		conns[i].fd = 0;
		conns[i].client_idx = -1;
		conns[i].backend_idx = -1;
		conns[i].sz_backend_host[0] = 0;
		memset(&conns[i].backend_addr, 0, sizeof(sockaddr_in));
		conns[i].event = conn_t::NETPOLL_EVENT_NULL;
		conns[i].type = conn_t::TYPE_NULL;
		conns[i].rx_size = MAX_BUFF_SIZE;
		conns[i].rx_head = conns[i].rx_tail = 0;
		conns[i].rx_buff = (char*)malloc(sizeof(char) * MAX_BUFF_SIZE);
		conns[i].tx_size = MAX_BUFF_SIZE;
		conns[i].tx_head = conns[i].tx_tail = 0;
		conns[i].tx_buff = (char*)malloc(sizeof(char) * MAX_BUFF_SIZE);
	}

	listen_fd = socket(AF_INET, SOCK_STREAM, IPPROTO_tcp);

	sockaddr_in addr;
	memset(&addr, 0, sizeof(addr));
	addr.sin_family = AF_INET;
	addr.sin_addr.s_addr = ::inet_addr("0.0.0.0");
	addr.sin_port = htons(80);

	int ret = bind(listen_fd, (sockaddr*)&addr, sizeof(addr));
	if( ret ) { return ret; }

	ret =  listen(listen_fd, 100);
	if( ret ) { return ret; }

	return 0;
}

int proxy_server_start()
{
	int i, ret;

	while(1) {

		//FD_ZERO(&r_fd_set);
		//FD_ZERO(&w_fd_set);
		//FD_ZERO(&e_fd_set);

		select_timeout.tv_sec = 1;//1s
		select_timeout.tv_usec = 0;

		FD_SET(listen_fd, &r_fd_set);

		for( i = 0; i < MAX_FD_CNT; i++ ) {
			if( conns[i].fd ) {
				if( conns[i].event & conn_t::NETPOLL_EVENT_READ ) { FD_SET(conns[i].fd, &r_fd_set); }
				if( conns[i].event & conn_t::NETPOLL_EVENT_WRITE ) { FD_SET(conns[i].fd, &w_fd_set); }
			}
		}

		ret = select(0, &r_fd_set, &w_fd_set, &e_fd_set, &select_timeout);
		if( ret <= 0 ) { continue; }

		if( FD_ISSET(listen_fd, &r_fd_set) ) {
			sockaddr_in addr;
			memset(&addr, 0, sizeof(addr));
			int addr_size = sizeof(addr);
			int new_fd = accept(listen_fd, (sockaddr*)&addr, &addr_size);
			_add_new_conn(new_fd, conn_t::TYPE_CLIENT);
		}

		for( i = 0; i < MAX_FD_CNT; i++ ) {
			if( FD_ISSET(conns[i].fd, &r_fd_set) ) {
				_fd_read(conns[i].fd, i);
			}
		}

		for( i = 0; i < MAX_FD_CNT; i++ ) {
			if( FD_ISSET(conns[i].fd, &w_fd_set) ) {
				_fd_write(conns[i].fd, i);
			}
		}
	}

	return 0;
}

int proxy_server_stop()
{
	return 0;
}

void _reset_conn(conn_t *conn)
{
	conn->fd = 0;
	conn->client_idx =  -1;
	conn->backend_idx = -1;
	conn->event = conn_t::NETPOLL_EVENT_NULL;
	conn->type = conn_t::TYPE_NULL;
	conn->rx_size = MAX_BUFF_SIZE;
	conn->rx_head = conn->rx_tail = 0;
	conn->tx_size = MAX_BUFF_SIZE;
	conn->tx_head = conn->tx_tail = 0;
}

int _add_new_conn(int fd, int type)
{
	int i;
	for( i = 0; i < MAX_FD_CNT; i++ ) {
		if( ! conns[i].fd ) {
			conns[i].fd = fd;
			conns[i].event = conn_t::NETPOLL_NETPOLL_EVENT_READ;
			conns[i].type = (conn_t::CONN_TYPE)type;
			return i;
		}
	}
	return -1;
}

int _del_new_conn(int idx)
{
	closesocket(conns[idx].fd);
	_reset_conn(&conns[idx]);
	return 0;
}

int _fd_read(int fd, int idx)
{
	conn_t *conn = &conns[idx];
	if( conn->type == conn_t::TYPE_CLIENT ) {
		_client_read(fd, idx);
	} else {
		_backend_read(fd, idx);
	}
	return 0;
}

int _client_read(int fd, int idx)
{
	int ret;
	conn_t *conn = &conns[idx];

	if( conn->rx_size - conn->rx_tail - 1 <= 0 ) { return 0; }

	ret = recv(fd, conn->rx_buff + conn->rx_tail, conn->rx_size - conn->rx_tail - 1, 0);
	if( ret <= 0 ) {
		if( conn->backend_idx >= 0 ) { _del_new_conn(conn->backend_idx); }
		_del_new_conn(idx); 
	}
	conn->rx_tail += ret;

	if( get_full_http_head(conn->rx_buff + conn->rx_head, 
							conn->rx_tail - conn->rx_head) ) {
		if( get_http_host(conn->rx_buff + conn->rx_head,
						conn->sz_backend_host,
						conn->rx_tail - conn->rx_head) ) {
			assert(0);
		}
		//create backend conn
		int backend_fd = socket(AF_INET, SOCK_STREAM, IPPROTO_tcp);
		int backend_idx = _add_new_conn(backend_fd, conn_t::TYPE_BACKEND);
		if( backend_idx <=0 ) { assert(0); }
		conn->backend_idx = backend_idx;

		conn_t *backend_conn = &conns[backend_idx];
		backend_conn->client_idx = idx;
		strcpy(backend_conn->sz_backend_host, conn->sz_backend_host);

		struct hostent *remote_host = gethostbyname(conn->sz_backend_host);
		backend_conn->backend_addr.sin_addr.s_addr = *(u_long *) remote_host->h_addr_list[0];
		backend_conn->backend_addr.sin_port = htons(80);
		backend_conn->backend_addr.sin_family = AF_INET;
		printf("%s => %s\n", backend_conn->sz_backend_host, inet_ntoa(*(in_addr*)&backend_conn->backend_addr.sin_addr));
		memcpy(&conn->backend_addr, &backend_conn, sizeof(sockaddr));

		ret = connect(backend_fd, (sockaddr*)&backend_conn->backend_addr, sizeof(sockaddr));
		if( ret ) {
			_del_new_conn(backend_idx);
			_del_new_conn(idx);
			return 0;
		}

		ret = send(backend_fd, conn->rx_buff + conn->rx_head, conn->rx_tail - conn->rx_head, 0);
		if( ret <= 0 ) {
			_del_new_conn(backend_idx);
			_del_new_conn(idx);
			return 0;
		}

		conn->rx_head += ret;
		if( conn->rx_head == conn->rx_tail ) { 
			conn->rx_head = conn->rx_tail = 0; 
		}
	}

	return 0;
}

int _backend_read(int fd, int idx)
{
	int ret;
	conn_t *conn = &conns[idx];

	if( conn->rx_size - conn->rx_tail - 1 <= 0 ) { return 0; }

	ret = recv(fd, conn->rx_buff + conn->rx_tail, conn->rx_size - conn->rx_tail - 1, 0);
	if( ret <= 0 ) {
		if( conn->client_idx >= 0 ) { _del_new_conn(conn->client_idx); }
		_del_new_conn(idx); 
	}
	conn->rx_tail += ret;

	conn_t *client_conn = &conns[conn->client_idx];

	ret = send(client_conn->fd, conn->rx_buff + conn->rx_head, conn->rx_tail - conn->rx_head, 0);
	if( ret <= 0 ) {
		_del_new_conn(conn->client_idx);
		_del_new_conn(idx);
		return 0;
	}

	conn->rx_head += ret;
	if( conn->rx_head == conn->rx_tail ) { 
		conn->rx_head = conn->rx_tail = 0; 
	}

	return 0;
}

int _fd_write(int fd, int idx)
{
	return 0;
}


*/
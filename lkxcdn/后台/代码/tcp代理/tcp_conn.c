#include "tcp_conn.h"
#include "misc.h"
#include "net_select.h"
#include <assert.h>

#define MAX_RX_BUFF_SIZE 10*1024
#define MAX_TX_BUFF_SIZE 10*1024

int tcp_socket_read_cb(struct _net_poll_t *net_poll, socket_t *socket)
{
	int ret, error;
	tcp_socket_t *tcp_socket = (tcp_socket_t*)socket;
	tcp_conn_t *tcp_conn = tcp_socket->tcp_conn;

	if( tcp_conn->rx_size - tcp_conn->rx_tail - 1 <= 0 ) { return 0; }

	ret = tcp_socket_recv(tcp_socket, 
						tcp_conn->rx_buff + tcp_conn->rx_tail, 
						tcp_conn->rx_size - tcp_conn->rx_tail - 1);
	if( ret > 0 ) {
		tcp_conn->rx_tail += ret;
		tcp_conn->rx_buff[tcp_conn->rx_tail] = '\0';
	}

	error = ret > 0 ? 0 : 1;
	if( tcp_conn->event_read_cb ) {
		tcp_conn->event_read_cb(net_poll, tcp_conn, ret, error);
	}
	return ret;
}

int tcp_socket_write_cb(struct _net_poll_t *net_poll, socket_t *socket)
{
	int ret = 0, error = 0;
	tcp_socket_t *tcp_socket = (tcp_socket_t*)socket;
	tcp_conn_t *tcp_conn = tcp_socket->tcp_conn;
	int size = tcp_conn->tx_tail - tcp_conn->tx_head;

	if( tcp_conn->status == TCP_CONN_STATUS_CONNECTING ||
		tcp_conn->status == TCP_CONN_STATUS_RESTART_CONNECTING ) {
		if( tcp_conn->event_write_cb ) {
			tcp_conn->event_write_cb(net_poll, tcp_conn, ret, error);
		}
		return 0;
	}
	
	ret = tcp_socket_send(tcp_socket, 
							tcp_conn->tx_buff + tcp_conn->tx_head, 
							size);
	if( ret >= 0 ) {
		if( ret == size ) {
			//all send
			tcp_conn->tx_head = tcp_conn->tx_tail = 0;
			net_poll->add(net_poll, 
						tcp_socket->socket.fd, 
						NETPOLL_EVENT_READ,
						tcp_socket,
						NULL);
		} else {
			//pending tx data
			tcp_conn->tx_head += ret;
			net_poll->add(net_poll, 
				tcp_socket->socket.fd, 
				NETPOLL_EVENT_READ|NETPOLL_EVENT_WRITE,
				tcp_socket,
				NULL);
		}
	} else if( ret == SOCKET_ERROR && misc_would_block() ) {
		//pending
		ret = 0;
		net_poll->add(net_poll, 
			tcp_socket->socket.fd, 
			NETPOLL_EVENT_READ|NETPOLL_EVENT_WRITE,
			tcp_socket,
			NULL);

	} else {
		error = 1;
		//conn error
	}

	if( tcp_conn->event_write_cb ) {
		tcp_conn->event_write_cb(net_poll, tcp_conn, ret, error);
	}

	return ret;
}

int tcp_conn_send(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, char *buff, int size)
{
	int ret = 0, error = 0;
	tcp_socket_t *tcp_socket = &tcp_conn->tcp_socket;
	
	if( tcp_conn->tx_size - tcp_conn->tx_tail <= size ) {
		//send buff not enough
		return -1;
	}

	if( tcp_conn->tx_head < tcp_conn->tx_tail ) {
		//pending tx data
		memcpy(tcp_conn->tx_buff + tcp_conn->tx_tail, buff, size);
		tcp_conn->tx_tail += size;
		return 0;
	} 

	ret = tcp_socket_send(tcp_socket, buff, size);
	if( ret > 0 ) {
		if( ret == size ) {
			//good full send succ!
			net_poll->add(net_poll, 
						tcp_socket->socket.fd, 
						NETPOLL_EVENT_READ,
						tcp_socket,
						NULL);
		} else {
			//pending tx data
			memcpy(tcp_conn->rx_buff + tcp_conn->tx_tail, buff + ret, size - ret);
			tcp_conn->tx_tail += ret;
			net_poll->add(net_poll, 
						tcp_socket->socket.fd,
						NETPOLL_EVENT_READ|NETPOLL_EVENT_WRITE,
						tcp_socket,
						NULL);
		}
		return ret;
	}
	
	if( ret == SOCKET_ERROR && misc_would_block() ) {
		//pending
		net_poll->add(net_poll, 
					tcp_socket->socket.fd,
					NETPOLL_EVENT_READ|NETPOLL_EVENT_WRITE,
					tcp_socket,
					NULL);

		//copy tx data
		memcpy(tcp_conn->tx_buff + tcp_conn->tx_tail, buff, size);
		tcp_conn->tx_tail += size;
		return 0;
	}

	return ret;
}

tcp_conn_t* create_tcp_conn(int fd)
{
	tcp_conn_t *tcp_conn = (tcp_conn_t*)malloc(sizeof(tcp_conn_t));

	tcp_socket_create(&tcp_conn->tcp_socket, fd);
	tcp_conn->tcp_socket.socket.event_read_net_poll_cb = tcp_socket_read_cb;
	tcp_conn->tcp_socket.socket.event_write_net_poll_cb = tcp_socket_write_cb;

	tcp_conn->tcp_socket.tcp_conn = tcp_conn;

	tcp_conn->status = TCP_CONN_STATUS_NULL;
	tcp_conn->ctx = tcp_conn->data = NULL;

	tcp_conn->event_read_cb = tcp_conn->event_write_cb = NULL;
	tcp_conn->send = tcp_conn_send;

	memset(&tcp_conn->addr, 0, (sizeof(struct sockaddr_in)));

	tcp_conn->rx_size = MAX_RX_BUFF_SIZE;
	tcp_conn->rx_head = tcp_conn->rx_tail = 0;
	tcp_conn->rx_buff = malloc(sizeof(char)*tcp_conn->rx_size);

	tcp_conn->tx_size = MAX_TX_BUFF_SIZE;
	tcp_conn->tx_head = tcp_conn->tx_tail = 0;
	tcp_conn->tx_buff = malloc(sizeof(char)*tcp_conn->tx_size);

	return tcp_conn;
}

void destroy_tcp_conn(struct _tcp_conn_t *tcp_conn)
{
	tcp_socket_close(&tcp_conn->tcp_socket);
	free(tcp_conn->rx_buff);
	free(tcp_conn->tx_buff);
	free(tcp_conn);
}

void tcp_conn_reset_xbuff(struct _tcp_conn_t *tcp_conn)
{
	tcp_conn->rx_head = tcp_conn->rx_tail = 0;
	tcp_conn->tx_head = tcp_conn->tx_tail = 0;
}

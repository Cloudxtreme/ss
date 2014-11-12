#include "http_proxy.h"
#include "misc.h"
#include "net_select.h"
#include "hash.h"
#include <assert.h>

#define MAX_BACKLOG_CONN	100
#define MAX_SESSION_MASTER		4

http_proxy_t *g_http_proxy = NULL;

unsigned int http_proxy_host_url_hash(const char *host, const char *url)
{
	int i = 0, size;
	unsigned int url_hash = 0;

	size = strlen(host);
	for( i = 0; i< size; i++ ) {
		url_hash += host[i];
	}
	size = strlen(url);
	for( i = 0; i< size; i++ ) {
		if( url[i] == '?' ) { break; }
		url_hash += url[i];
	}
	return url_hash;
}

int http_proxy_tcp_conn_read_cb(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error)
{
	http_conn_t *http_conn = (http_conn_t*)tcp_conn->ctx;
	hash_table_t *client_http_header = http_conn->http_header;
	hash_node_t *node;
	char *host = NULL, *url = NULL, *keepalive = NULL;
	unsigned int url_hash;
	int session_master_id, i;
	int line_len, pos1, pos2, size;
	char *buff;

	if( ! error ) {

		buff = tcp_conn->rx_buff + tcp_conn->rx_head;
		size = tcp_conn->rx_tail - tcp_conn->rx_head;

		if( get_full_http_head(buff, size) ) {

			if( get_http_header(buff, size, client_http_header) ) {

				//printf("http_proxy_tcp_conn_read_cb fd=[%d] ret=[%d] error=[%d] \n",
				//		tcp_conn->tcp_socket.socket.fd, ret, error);

				/*
				HASH_TABLE_FOR_EACH_BEGIN(node, client_http_header)
				printf("%s => %s \n", (char*)node->key, (char*)node->value);
				HASH_TABLE_FOR_EACH_END
				*/
				host = hash_table_find(client_http_header, HTTP_HEADER_HOST);
				url = hash_table_find(client_http_header, "url");
				printf("[%s]\n", url);

				line_len = my_strpos(buff, size, HTTP_HEADER_LINE_END);
				pos1 = my_nocase_strpos(buff, line_len, HTTP_HEADER);
				if( pos1 > 0 ) {
					pos2 = my_nocase_strpos(buff + pos1 + strlen(HTTP_HEADER), 
											line_len - pos1 - strlen(HTTP_HEADER), 
											HTTP_HEADER_SLASH);
				}
				if( pos1 > 0 && pos2 > 0 ) {
					memcpy(buff + strlen(HTTP_HEADER) + pos2, buff, pos1);
					tcp_conn->rx_head += (strlen(HTTP_HEADER) + pos2);
				} else {
					//assert(0);
				}
				//printf("[%s]\n", buff);
				
				http_conn->is_keep_alive = 0;
				keepalive = hash_table_find(client_http_header, HTTP_HEADER_CONN);
				if( keepalive ) {
					http_conn->is_keep_alive = ! strcmp(keepalive, HTTP_HEADER_CONN_KEEP_ALIVE);
				}

				if( host && url ) {

					url_hash = http_proxy_host_url_hash(host, url);

					session_master_id = url_hash % MAX_SESSION_MASTER;

					//printf("http_proxy_tcp_conn_read_cb fd=[%d] url_hash=[%X] session_master_id=[%d] \n",
					//	tcp_conn->tcp_socket.socket.fd, url_hash, session_master_id);

					for( i = 0; i < MAX_BACKLOG_CONN; i++ ) {
						if( ! g_http_proxy->http_conns[session_master_id * MAX_BACKLOG_CONN + i] ) {
							net_poll->del(net_poll, tcp_conn->tcp_socket.socket.fd);
							g_http_proxy->http_conns[session_master_id * MAX_BACKLOG_CONN + i] = http_conn;
							return 0;
						}
					}
					//printf("http_proxy_tcp_conn_read_cb fd=[%d] max conn! \n",
					//		tcp_conn->tcp_socket.socket.fd);
				} else {
					//no host
					//assert(0);
					printf("http_proxy_tcp_conn_read_cb fd=[%d] no Host! \n", 
							tcp_conn->tcp_socket.socket.fd);
				}
			} else {
				//parse http header error
				assert(0);
			}
		} else {
			//incomplete http header
			assert(0);
		}
	}

	//error or ...
	net_poll->del(net_poll, tcp_conn->tcp_socket.socket.fd);

	destroy_http_conn(http_conn);

	return 0;
}

int http_proxy_accept_cb(struct _net_dev_t* net_dev)
{
	tcp_socket_t *tcp_socket_listen = &net_dev->tcp_listen_socket;
	net_poll_t *net_poll = g_http_proxy->net_poll;

	struct sockaddr_in addr;
	int fd;
	http_conn_t *http_conn = NULL;
	tcp_conn_t *tcp_conn = NULL;

	memset(&addr, 0, sizeof(struct sockaddr_in));
	fd = tcp_socket_accept(tcp_socket_listen, &addr);
	if( fd > 0 ) {
		misc_set_fd_noblock(fd);
		tcp_conn = create_tcp_conn(fd);
		tcp_conn->status = TCP_CONN_STATUS_CONNECTED;
		tcp_conn->addr = addr;
		tcp_conn->event_read_cb = http_proxy_tcp_conn_read_cb;
		net_poll->add(net_poll, 
					fd,
					NETPOLL_EVENT_READ,
					&tcp_conn->tcp_socket,
					NULL);

		http_conn = http_conn_create(tcp_conn);
		//printf("http_proxy_accept_cb fd=[%d] \n", fd);
	}
	return 0;
}

void http_proxy_handle_fd_event(http_proxy_t *http_proxy, net_poll_t *net_poll)
{
	fd_info_t *fdinfo;
	socket_t *socket;
	int fd, i;
	for( i = 0; i < net_poll->event_fds.cnt; i++ ) {
		fdinfo = &net_poll->event_fds.fd_info[i];
		fd = fdinfo->fd;
		if( net_poll->register_fds.fd_info[fd].fd &&
			fdinfo->ctx ) {
			socket = (socket_t*)fdinfo->ctx;
			if( fdinfo->event & NETPOLL_EVENT_READ &&
				socket->event_read_net_poll_cb ) {
				socket->event_read_net_poll_cb(net_poll, socket);
			}
		}
		//check register fd , fd maybe del in event_read_net_poll_cb
		fdinfo = &net_poll->event_fds.fd_info[i];
		if( net_poll->register_fds.fd_info[fd].fd && 
			fdinfo->ctx ) {
			socket = (socket_t*)fdinfo->ctx;
			if( fdinfo->event & NETPOLL_EVENT_WRITE &&
				socket->event_write_net_poll_cb ) {
					socket->event_write_net_poll_cb(net_poll, socket);
			}
		}
	}
}

void http_proxy_main_run(void *data)
{
	struct timeval timeout = {1, 0};
	int ret;

	while( ! g_http_proxy->want_exit ) {

		ret = g_http_proxy->net_poll->poll(g_http_proxy->net_poll, &timeout);
		if( ret >  0 ) {
			http_proxy_handle_fd_event(g_http_proxy, g_http_proxy->net_poll);
		}
	}

	g_http_proxy->is_run = 0;
}

http_conn_t* http_proxy_get_http_conn(http_proxy_t *http_proxy, int session_master_id)
{
	int i = 0;
	http_conn_t *http_conn = NULL;
	for( i = 0; i < MAX_BACKLOG_CONN; i++ ) {
		if( http_proxy->http_conns[session_master_id * MAX_BACKLOG_CONN + i] ) {
			http_conn = http_proxy->http_conns[session_master_id * MAX_BACKLOG_CONN + i];
			http_proxy->http_conns[session_master_id * MAX_BACKLOG_CONN + i] = NULL;
			return http_conn;
		}
	}
	return NULL;
}

http_proxy_t* create_http_proxy()
{
	int i = 0;

	if( g_http_proxy ) { return g_http_proxy; }
	
	g_http_proxy = (http_proxy_t *)malloc(sizeof(http_proxy_t));
	
	g_http_proxy->want_exit = 0;
	g_http_proxy->is_run = 0;

	g_http_proxy->net_dev = create_net_dev();
	g_http_proxy->net_dev->net_dev_accept_cb = http_proxy_accept_cb;

	g_http_proxy->net_poll = create_net_select();

	g_http_proxy->task_main = create_task();
	g_http_proxy->task_main->run = http_proxy_main_run;

	g_http_proxy->http_conns = malloc(sizeof(http_conn_t*)* MAX_BACKLOG_CONN * MAX_SESSION_MASTER);
	memset(g_http_proxy->http_conns, 0, sizeof(http_conn_t*)* MAX_BACKLOG_CONN * MAX_SESSION_MASTER);

	g_http_proxy->session_masters = malloc(sizeof(session_master_t*) * MAX_SESSION_MASTER);
	for( i = 0; i < MAX_SESSION_MASTER; i++ ) {
		g_http_proxy->session_masters[i] = create_session_master(i);
	}

	g_http_proxy->task_workers = malloc(sizeof(task_t*) * MAX_SESSION_MASTER);
	for( i = 0; i < MAX_SESSION_MASTER; i++ ) {
		g_http_proxy->task_workers[i] = create_task();
		g_http_proxy->task_workers[i]->data = g_http_proxy->session_masters[i];
		g_http_proxy->task_workers[i]->run = session_master_main_run;
	}

	return g_http_proxy;
}

int http_proxy_init(http_proxy_t *http_proxy)
{
	net_poll_t *net_poll = http_proxy->net_poll;
	tcp_socket_t *tcp_listen_socket = &http_proxy->net_dev->tcp_listen_socket;

	net_dev_bind(http_proxy->net_dev, "0.0.0.0", 8080);
	net_dev_listen(http_proxy->net_dev, 100);

	net_poll->init(net_poll, 1024);
	net_poll->add(net_poll, 
				tcp_listen_socket->socket.fd, 
				NETPOLL_EVENT_READ,
				tcp_listen_socket, 
				NULL);

	return 0;
}

int http_proxy_start(http_proxy_t *http_proxy)
{
	int i = 0;
	if( ! http_proxy->want_exit && ! http_proxy->is_run ) {

		http_proxy->is_run = 1;

		//start worker
		for( i = 0; i < MAX_SESSION_MASTER; i++ ) {
			task_start(http_proxy->task_workers[i], 0);
		}

		//start main 
		task_start(http_proxy->task_main, 1);

	}
	return 0;
}

int http_proxy_stop(http_proxy_t *http_proxy)
{
	int i = 0;
	http_proxy->want_exit = 1;
	while( http_proxy->is_run ) {
		misc_msleep(500);
	}
	for( i = 0; i < MAX_SESSION_MASTER; i++ ) {
		task_stop(http_proxy->task_workers[i]);
		task_join(http_proxy->task_workers[i]);
	}
	return 0;
}




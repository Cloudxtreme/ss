#include "session.h"
#include "tcp_conn.h"
#include "session_master.h"
#include "misc.h"
#include "dns.h"
#include <assert.h>

int session_client_read_cb(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error);
int session_client_write_cb(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error);

int session_backend_read_cb(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error);
int session_backend_write_cb(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error);

int session_dns_resolver_getip(struct _session_t *session, const char *host, unsigned int *backend_ip);
void session_dns_resolver_getip_cb(struct _dns_ctx_t *dns_ctx, int ret, int error);

int session_restart_backend(struct _session_t *session);

int session_start_backend(struct _session_t *session, unsigned int backend_ip);

int session_client_read_cb(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error)
{
	http_conn_t *http_conn = (http_conn_t*)tcp_conn->ctx;
	session_t *session =  http_conn->session;
	session_master_t *session_master = session->session_master;
	http_conn_t *client_conn =  session->client_conn;
	http_conn_t *backend_conn = session->backend_conn;
	int send_ret;
	char *keepalive = NULL;
	int line_len, pos1, pos2, size;
	char *host, *url, *buff;

	if( ! error ) {

		buff = tcp_conn->rx_buff + tcp_conn->rx_head;
		size = tcp_conn->rx_tail - tcp_conn->rx_head;

		if( get_full_http_head(buff, size) ) {
			//获取到完整的HTTP头才重新分析
			hash_table_clear(client_conn->http_header);
			if( get_http_header(buff, size, client_conn->http_header) ) {
				
				host = hash_table_find(client_conn->http_header, HTTP_HEADER_HOST);
				url = hash_table_find(client_conn->http_header, "url");
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
					client_conn->tcp_conn->rx_head += (strlen(HTTP_HEADER) + pos2);
					size = client_conn->tcp_conn->rx_tail - client_conn->tcp_conn->rx_head;
				}

				http_conn->is_keep_alive = 0;
				keepalive = hash_table_find(client_conn->http_header, HTTP_HEADER_CONN);
				if( keepalive ) {
					client_conn->is_keep_alive = ! strcmp(keepalive, HTTP_HEADER_CONN_KEEP_ALIVE);
				}
			}
		}

		send_ret = tcp_conn_send(session_master->net_poll,
								backend_conn->tcp_conn,
								buff, size);

		if( send_ret >= 0 ) {
			client_conn->tcp_conn->rx_head = client_conn->tcp_conn->rx_tail = 0;
			return 0;
		} else {
			session_restart_backend(session);
			return 0;
		}
	}

	//error
GOTO_ERROR:
	session_master_del_session(session_master, session);
	destroy_session(session);

	return 0;
}

int session_client_write_cb(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error)
{
	http_conn_t *http_conn = (http_conn_t*)tcp_conn->ctx;
	session_t *session =  http_conn->session;
	session_master_t *session_master = session->session_master;
	http_conn_t *client_conn =  session->client_conn;
	http_conn_t *backend_conn = session->backend_conn;

	if( ! error ) {
		return 0;
	}

	//error
	session_master_del_session(session_master, session);
	destroy_session(session);
	return 0;
}

void my_hook(http_conn_t *client_conn, http_conn_t *backend_conn)
{
	const char *smatch = "time<=0";
	int pos = my_strpos(backend_conn->tcp_conn->rx_buff + backend_conn->tcp_conn->rx_head, 
					backend_conn->tcp_conn->rx_tail - backend_conn->tcp_conn->rx_head,
					smatch);

	if( pos > 0 ) {
		char *p = backend_conn->tcp_conn->rx_buff + backend_conn->tcp_conn->rx_head + pos;
		p[4] = '>';
	}
}


int session_backend_read_cb(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error)
{
	http_conn_t *http_conn = (http_conn_t*)tcp_conn->ctx;
	session_t *session =  http_conn->session;
	session_master_t *session_master = session->session_master;
	http_conn_t *client_conn =  session->client_conn;
	http_conn_t *backend_conn = session->backend_conn;
	int send_ret;
	char *keepalive = NULL;

	if( ! error ) {

//		my_hook(client_conn, backend_conn);

		//这里需要再次重新获取http头
		/*
		hash_table_clear(backend_conn->http_header);
		if( get_full_http_head(backend_conn->tcp_conn->rx_buff + backend_conn->tcp_conn->rx_head, 
							backend_conn->tcp_conn->rx_tail - backend_conn->tcp_conn->rx_head) ) {
			if( get_http_header(backend_conn->tcp_conn->rx_buff + backend_conn->tcp_conn->rx_head, 
							backend_conn->tcp_conn->rx_tail - backend_conn->tcp_conn->rx_head,
							backend_conn->http_header) ) {
				keepalive = hash_table_find(backend_conn->http_header, HTTP_HEADER_CONN);
				if( keepalive ) {
					backend_conn->is_keep_alive = strcmp(keepalive, HTTP_HEADER_CONN_CLOSE);
				}
			}
		}*/
		//printf("\n[%s]\n", backend_conn->tcp_conn->rx_buff + backend_conn->tcp_conn->rx_head);
		
		send_ret = tcp_conn_send(session_master->net_poll,
								client_conn->tcp_conn,
								backend_conn->tcp_conn->rx_buff + backend_conn->tcp_conn->rx_head, 
								backend_conn->tcp_conn->rx_tail - backend_conn->tcp_conn->rx_head);

		if( send_ret >= 0 && client_conn->is_keep_alive ) {
			backend_conn->tcp_conn->rx_head = backend_conn->tcp_conn->rx_tail = 0;
			return 0;
		} else {
			//如果是客户端的连接异常或者指定Connection: close，则可以终止整个session
			session_master_del_session(session_master, session);
			destroy_session(session);
		}
	} else {
		//如果是服务器的连接异常了，则重新建立连接即可
		session_restart_backend(session);
	}

	return 0;
}

int session_backend_write_cb(struct _net_poll_t *net_poll, struct _tcp_conn_t *tcp_conn, int ret, int error)
{
	http_conn_t *http_conn = (http_conn_t*)tcp_conn->ctx;
	session_t *session =  http_conn->session;
	session_master_t *session_master = session->session_master;
	http_conn_t *client_conn =  session->client_conn;
	http_conn_t *backend_conn = session->backend_conn;
	int send_ret;

	if( ! error ) {
		if( tcp_conn->status == TCP_CONN_STATUS_CONNECTING ||
			tcp_conn->status == TCP_CONN_STATUS_RESTART_CONNECTING ) {

			if( tcp_conn->status == TCP_CONN_STATUS_RESTART_CONNECTING ) {
				send_ret = 0;
			}

			tcp_conn->status = TCP_CONN_STATUS_CONNECTED;
			tcp_conn_reset_xbuff(tcp_conn);

			//printf("\n[%s]\n", client_conn->tcp_conn->rx_buff + client_conn->tcp_conn->rx_head);

			//now can send client request
			send_ret = tcp_conn_send(net_poll, 
									backend_conn->tcp_conn, 
									client_conn->tcp_conn->rx_buff + client_conn->tcp_conn->rx_head, 

									client_conn->tcp_conn->rx_tail - client_conn->tcp_conn->rx_head);

			if( send_ret >= 0 ) {
				client_conn->tcp_conn->rx_head = client_conn->tcp_conn->rx_tail = 0;
				return 0;
			} else {
				//如果是客户端的连接异常了则可以终止整个session
				session_master_del_session(session_master, session);
				destroy_session(session);
			}
		} else if( tcp_conn->status == TCP_CONN_STATUS_CONNECTED ) {

		} else {
			assert(0);
		}
	} else {
		//如果是服务器的连接异常了，则重新建立连接即可
		session_restart_backend(session);
	}

	return 0;
}

int session_dns_resolver_getip(struct _session_t *session, const char *host, unsigned int *backend_ip)
{
#if 1
	return simple_dns_resolver_getip(host, backend_ip);
#else
	dns_resolver_t *dns_resolver = session->session_master->dns_resolver;
	dns_ctx_t *dns_ctx = &session->dns_ctx;
	int dns_ret;
	assert( dns_ctx->status != DNS_QUERY );
	
	//暂时不做长度检查
	strcpy(dns_ctx->host, host);
	dns_ret = dns_resolver_getip(dns_resolver, dns_ctx);
	if( dns_ret == DNS_SUCC ) {
		//cache
		*backend_ip = dns_ctx->host_info.ips[0];
	}
	return dns_ret;
#endif
}

void session_dns_resolver_getip_cb(struct _dns_ctx_t *dns_ctx, int ret, int error)
{
	session_t *session = (session_t*)dns_ctx->ctx;
	session_master_t *session_master = session->session_master;
	unsigned int backend_ip = 0;

	if( ! error ) {
		backend_ip = dns_ctx->host_info.ips[0];
		session_start_backend(session, backend_ip);
		return;
	}

	//error
	session_master_del_session(session_master, session);
	destroy_session(session);

	return;
}

int session_restart_backend(struct _session_t *session)
{
	http_conn_t *backend_conn = session->backend_conn;
	tcp_socket_t *tcp_socket = &backend_conn->tcp_conn->tcp_socket;
	net_poll_t *net_poll = session->session_master->net_poll;
	int ret, oldfd;

	backend_conn->tcp_conn->status = TCP_CONN_STATUS_RESTART_CONNECTING;
	oldfd = tcp_socket->socket.fd;
	net_poll->del(net_poll, tcp_socket->socket.fd);
	tcp_socket_close(tcp_socket);
	tcp_socket->socket.fd = tcp_socket_create_socket();

	//printf("session_restart_backend oldbackend_fd=[%d] backend_fd=[%d] \n", 
	//		oldfd, backend_conn->tcp_conn->tcp_socket.socket.fd);

	net_poll->add(net_poll, 
				tcp_socket->socket.fd,
				NETPOLL_EVENT_WRITE,
				tcp_socket,
				NULL);

	ret = tcp_socket_conn(&backend_conn->tcp_conn->tcp_socket, &backend_conn->tcp_conn->addr);
	if( ! ret ) { return 0; }
	if( misc_would_block() ) {
		return 0;
	} else {
//		assert(0);
	}
	return 0;
}

int session_start_backend(struct _session_t *session, unsigned int backend_ip)
{
	session_master_t *session_master = session->session_master;
	hash_table_t *client_http_header = session->client_conn->http_header;
	char *http_backend_port;
	int backend_port = 80;
	tcp_conn_t *backend_tcp_conn = NULL;
	http_conn_t *backend_conn = NULL;
	net_poll_t *net_poll = session->session_master->net_poll;
	int ret;

	backend_tcp_conn = create_tcp_conn(0);
	backend_conn = http_conn_create(backend_tcp_conn);
	session->backend_conn = backend_conn;

	backend_conn->tcp_conn->status = TCP_CONN_STATUS_CONNECTING;

	backend_conn->tcp_conn->event_read_cb = session_backend_read_cb;
	backend_conn->tcp_conn->event_write_cb = session_backend_write_cb;
	backend_conn->session = session;

	backend_conn->tcp_conn->addr.sin_family = AF_INET;
	backend_conn->tcp_conn->addr.sin_addr.s_addr = backend_ip;
	http_backend_port = hash_table_find(client_http_header, HTTP_HEADER_HOST_PORT);
	if( http_backend_port ) {
		backend_port = misc_atoi(http_backend_port);
		if( backend_port <= 0 || backend_port >= 0xFFFF ) {
			printf("session_start_backend http_backend_port %s %d error! \n ", 
				http_backend_port, backend_port);
			goto GOTO_ERROR;
		}
	} 
	backend_conn->tcp_conn->addr.sin_port = htons(backend_port);
	
	net_poll->add(net_poll, 
				backend_conn->tcp_conn->tcp_socket.socket.fd,
				NETPOLL_EVENT_WRITE,
				&backend_conn->tcp_conn->tcp_socket,
				NULL);

	ret = tcp_socket_conn(&backend_conn->tcp_conn->tcp_socket, &backend_conn->tcp_conn->addr);
	if( ! ret ) { return 0; }
	if( misc_would_block() ) {
		return 0;
	} else {
		//assert(0);
	}

GOTO_ERROR:
	//error
	session_master_del_session(session_master, session);
	destroy_session(session);

	return 0;
}

int session_start(struct _session_t *session)
{
	session_master_t *session_master = session->session_master;
	net_poll_t *net_poll = session->session_master->net_poll;
	char *host = NULL;
	unsigned int backend_ip = 0;
	int dns_ret;

	host = hash_table_find(session->client_conn->http_header, "Host");
	if( host ) {
		dns_ret = session_dns_resolver_getip(session, host, &backend_ip);
		if( dns_ret == DNS_SUCC ) {
			session_start_backend(session, backend_ip);
			return 0;
		} else if( dns_ret == DNS_WAIT ) {
			//wait for dns result
			return 0;
		} else {
			//error
		}
	}
	
	//error
	session_master_del_session(session_master, session);
	destroy_session(session);
	return 0;
}

session_t* create_session(struct _session_master_t *session_master, http_conn_t *http_conn)
{
	session_t *session = malloc(sizeof(session_t));
	
	session->session_master = session_master;
	session->client_conn = session->backend_conn = NULL;

	session->client_conn = http_conn;
	session->client_conn->tcp_conn->event_read_cb = session_client_read_cb;
	session->client_conn->tcp_conn->event_write_cb = session_client_write_cb;
	session->client_conn->session = session;

	memset(&session->dns_ctx, 0, sizeof(dns_ctx_t));
	session->dns_ctx.ctx = session;
	session->dns_ctx.dns_resolver_getip_cb = session_dns_resolver_getip_cb;

	return session;
}

void destroy_session(struct _session_t *session)
{
	dns_resolver_t *dns_resolver = session->session_master->dns_resolver;
	//hash_node_t *node;

	if( session->client_conn ) {
		destroy_http_conn(session->client_conn);
		session->client_conn = NULL;
	}
	if( session->backend_conn ) {
		destroy_http_conn(session->backend_conn);
		session->backend_conn = NULL;
	}

	dns_resolver_del_dns_ctx(dns_resolver, &session->dns_ctx);

	free(session);
}




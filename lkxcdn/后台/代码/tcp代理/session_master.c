#include "session_master.h"
#include "http_proxy.h"
#include "misc.h"

void session_master_handle_fd_event(session_master_t *session_master, net_poll_t *net_poll)
{
	fd_info_t *fdinfo;
	socket_t *socket;
	int fd, i;
	for( i = 0; i < net_poll->event_fds.cnt; i++ ) {

		//先处理写事件吧
		fdinfo = &net_poll->event_fds.fd_info[i];
		fd = fdinfo->fd;
		if( net_poll->register_fds.fd_info[fd].fd &&
			fdinfo->ctx ) {
				socket = (socket_t*)fdinfo->ctx;
				if( fdinfo->event & NETPOLL_EVENT_WRITE &&
					socket->event_write_net_poll_cb ) {
						socket->event_write_net_poll_cb(net_poll, socket);
				}
		}

		//然后再处理读事件
		fdinfo = &net_poll->event_fds.fd_info[i];
		if( net_poll->register_fds.fd_info[fd].fd &&
			fdinfo->ctx ) {
				socket = (socket_t*)fdinfo->ctx;
				if( fdinfo->event & NETPOLL_EVENT_READ &&
					socket->event_read_net_poll_cb ) {
						socket->event_read_net_poll_cb(net_poll, socket);
				}
		}
	}
}

void session_master_main_run(void *data)
{
	session_master_t *session_master = (session_master_t*)data;
	net_poll_t *net_poll = session_master->net_poll;
	struct timeval timeout = {1, 0};
	int ret;
	http_conn_t *http_conn;

	while( (http_conn = http_proxy_get_http_conn(g_http_proxy, session_master->session_master_id)) ) {
		session_t *session = create_session(session_master, http_conn);
		tcp_socket_t *tcp_socket = &http_conn->tcp_conn->tcp_socket;
		list_node_add_tail(&session_master->session_list, session);
		net_poll->add(net_poll, 
					tcp_socket->socket.fd,
					NETPOLL_EVENT_READ,
					tcp_socket,
					NULL);
		session_start(session);
	}

	if( net_poll->size(net_poll) > 0 ) {
		ret = net_poll->poll(net_poll, &timeout);
		if( ret > 0 ) {
			session_master_handle_fd_event(session_master, net_poll);
		}
	} else {
		misc_msleep(100);
	}
}

session_master_t* create_session_master(int session_master_id)
{
	session_master_t *session_master = malloc(sizeof(session_master_t));
	net_poll_t *net_poll;

	session_master->session_master_id = session_master_id;
	
	session_master->dns_resolver = create_dns_resolver();

	list_init(&session_master->session_list);

	session_master->net_poll = create_net_select();
	net_poll = session_master->net_poll;
	net_poll->init(session_master->net_poll, 1024);

	net_poll->add(net_poll,
				session_master->dns_resolver->udp_socket.socket.fd,
				NETPOLL_EVENT_READ,
				&session_master->dns_resolver->udp_socket,
				NULL);

	return session_master;
}

void session_master_del_session(struct _session_master_t *session_master, session_t *session)
{
	net_poll_t *net_poll = session_master->net_poll;
	int client_fd = 0, backend_fd = 0;

	list_node_del(&session_master->session_list, session);

	if( session->client_conn ) {
		client_fd = session->client_conn->tcp_conn->tcp_socket.socket.fd;
		net_poll->del(net_poll, session->client_conn->tcp_conn->tcp_socket.socket.fd);
	}
	if( session->backend_conn ) {
		backend_fd = session->backend_conn->tcp_conn->tcp_socket.socket.fd;
		net_poll->del(net_poll, session->backend_conn->tcp_conn->tcp_socket.socket.fd);
	}

	//printf("net_poll->del client_fd=[%d] backend_fd=[%d] \n", client_fd, backend_fd);
}


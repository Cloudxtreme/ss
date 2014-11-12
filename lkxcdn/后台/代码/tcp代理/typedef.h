#pragma once

#ifndef NULL
#ifdef __cplusplus
#define NULL    0
#else
#define NULL    ((void *)0)
#endif
#endif

#ifdef WIN32
#else
#define SOCKET_ERROR	(-1)
#endif

#define SOCKET_TYPE_NULL	0
#define SOCKET_TYPE_UDP	1
#define SOCKET_TYPE_TCP	2

#define NETPOLL_EVENT_NULL	0
#define NETPOLL_EVENT_READ	1
#define NETPOLL_EVENT_WRITE	2
#define NETPOLL_EVENT_CLOSE	4

#define DNS_NULL	0
#define DNS_QUERY	1
#define DNS_ANSWER	2

#define DNS_SUCC	0
#define DNS_WAIT	1
#define DNS_ERROR	-1


#define DEFAULT_LINE_LEN	(1*1024)
#define MIN_LINE_LEN	(100)
#define MAX_LINE_LEN	(2*1024)

#define CHAR_COLON						':'

#define HTTP_HEADER						"http://"
#define HTTP_HEADER_LINE_SPACE			" "
#define HTTP_HEADER_LINE_COLON			":"
#define HTTP_HEADER_LINE_END			"\r\n"
#define HTTP_HEADER_SLASH				"/"
#define HTTP_HEADER_HOST				"Host"
#define HTTP_HEADER_CONN				"Connection"
#define HTTP_HEADER_CONN_KEEP_ALIVE		"keep-alive"
#define HTTP_HEADER_HOST_PORT			"HostPort"
#define HTTP_DEFAULT_PORT				"80"

typedef struct _fd_info_t fd_info_t;
typedef struct _fdset_t fdset_t;
typedef struct _net_poll_t net_poll_t;
typedef struct _tcp_socket_t tcp_socket_t;
typedef struct _tcp_conn_t tcp_conn_t;
typedef struct _http_conn_t http_conn_t;
typedef struct _http_proxy_t http_proxy_t;
typedef struct _session_master_t session_master_t;
typedef struct _session_t session_t;

typedef struct _socket_t
{
	int fd;
	int type;

	int (*event_read_net_poll_cb)(struct _net_poll_t *net_poll, struct _socket_t *socket);
	int (*event_write_net_poll_cb)(struct _net_poll_t *net_poll, struct _socket_t *socket);

	void *ctx;
	void *data;

}socket_t;



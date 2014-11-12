#pragma once
#include "tcp_conn.h"

struct _http_conn_t
{
	tcp_conn_t *tcp_conn;

	struct _session_t *session;

	hash_table_t *http_header;
	int is_keep_alive;

};

http_conn_t* http_conn_create(tcp_conn_t *tcp_conn);
void destroy_http_conn(http_conn_t *http_conn);


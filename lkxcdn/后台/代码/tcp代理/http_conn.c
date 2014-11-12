#include "http_conn.h"

http_conn_t* http_conn_create(tcp_conn_t *tcp_conn)
{
	http_conn_t *http_conn = (http_conn_t*)malloc(sizeof(http_conn_t));
	http_conn->tcp_conn = NULL;
	if( tcp_conn ) {
		http_conn->tcp_conn = tcp_conn;
		tcp_conn->ctx = http_conn;
	}
	http_conn->session = NULL;
	http_conn->http_header = create_hash_table(100);
	http_conn->is_keep_alive = 0;
	return http_conn;
}

void destroy_http_conn(http_conn_t *http_conn)
{
	if( http_conn->tcp_conn ) {
		destroy_tcp_conn(http_conn->tcp_conn);
		http_conn->tcp_conn = NULL;
	}
	if( http_conn->http_header ) {
		destroy_hash_table(http_conn->http_header);
		http_conn->http_header = NULL;
	}
	http_conn->session = NULL;
	free(http_conn);
}




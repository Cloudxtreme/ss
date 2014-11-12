#pragma once
#include "http_conn.h"
#include "hash.h"
#include "dns.h"

struct _session_t
{
	struct _session_master_t *session_master;

	//client part
	http_conn_t *client_conn;

	//backend part
	http_conn_t *backend_conn;

	struct _dns_ctx_t dns_ctx;

};

session_t* create_session(struct _session_master_t *session_master, http_conn_t *http_conn);
void destroy_session(struct _session_t *session);
int session_start(struct _session_t *session);



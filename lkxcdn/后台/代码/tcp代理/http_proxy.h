#pragma once
#include "net_dev.h"
#include "net_poll.h"
#include "task.h"
#include "session_master.h"
#include "http_conn.h"
#include "list.h"

struct _http_proxy_t
{
	volatile int want_exit, is_run;

	net_dev_t *net_dev;
	net_poll_t *net_poll;
	task_t *task_main;

	http_conn_t **http_conns;

	session_master_t **session_masters;
	task_t **task_workers;

};

http_conn_t* http_proxy_get_http_conn(http_proxy_t *http_proxy, int session_master_id);

http_proxy_t* create_http_proxy();
int http_proxy_init(http_proxy_t *http_proxy);
int http_proxy_start(http_proxy_t *http_proxy);
int http_proxy_stop(http_proxy_t *http_proxy);

extern http_proxy_t *g_http_proxy;


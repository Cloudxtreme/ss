#pragma once
#include "session.h"
#include "net_poll.h"
#include "list.h"
#include "dns.h"

struct _session_master_t
{
	int session_master_id;

	dns_resolver_t *dns_resolver;

	list_t session_list;

	net_poll_t *net_poll;

};

void session_master_main_run(void *data);
session_master_t* create_session_master(int session_master_id);
void session_master_del_session(struct _session_master_t *session_master, session_t *session);


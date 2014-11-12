#pragma once
#include "udp_socket.h"
#include "hash.h"

#define MAX_HOST_LEN	100
#define MAX_DNS_IP_CNT	4

typedef struct _dns_host_info_t
{
	int ips[MAX_DNS_IP_CNT];

}dns_host_info_t;

typedef struct _dns_ctx_t
{
	int dnsid;
	int status;
	char host[MAX_HOST_LEN];
	dns_host_info_t host_info;
	void *ctx;
	void *data;
	void (*dns_resolver_getip_cb)(struct _dns_ctx_t *dns_ctx, int ret, int error);

}dns_ctx_t;

typedef struct _dns_resolver_t
{
	udp_socket_t udp_socket;
	
	unsigned int dns_req_id;

	dns_ctx_t **dns_reqs;

	hash_table_t *host_cache_table;

}dns_resolver_t;

int simple_dns_resolver_getip(const char *host, unsigned int *backend_ip);

dns_resolver_t* create_dns_resolver();
int dns_resolver_getip(dns_resolver_t *dns_resolver, dns_ctx_t *dns_ctx);
int dns_resolver_del_dns_ctx(dns_resolver_t *dns_resolver, dns_ctx_t *dns_ctx);


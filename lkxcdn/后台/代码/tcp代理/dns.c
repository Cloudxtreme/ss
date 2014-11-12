#include "dns.h"
#include "misc.h"
#include "dns_struct.h"

#define MAX_DNS_REQS	1000

static char *dns_servers[] = {
	"202.96.128.166",
	"114.114.114.114"
};

int simple_dns_resolver_getip(const char *host, unsigned int *backend_ip)
{
	struct sockaddr_in addr;

#ifdef WIN32
	struct hostent *remote_host = gethostbyname(host);
	if( ! remote_host ) { return DNS_ERROR; }
	*backend_ip = *(unsigned int *)remote_host->h_addr_list[0];
#else
	char dns_buff[1024];
	struct hostent hostinfo, *remote_host;
	int rc;

	bzero(&addr, sizeof(struct sockaddr_in));
	if( inet_pton(AF_INET, host, &(addr.sin_addr)) == 1 ) {
		return(addr.sin_addr.s_addr);
	}
	if( ! gethostbyname_r(host, &hostinfo, dns_buff, 1024, &remote_host, &rc)) {
		if( ! remote_host ) { return DNS_ERROR; }
		*backend_ip = *(unsigned int *)remote_host->h_addr_list[0];
	} else {
        *backend_ip = 0;
	}
#endif

	memset(&addr, 0, sizeof(struct sockaddr_in));
	addr.sin_addr.s_addr = *(unsigned int *) remote_host->h_addr_list[0];
	printf("%s => %s\n", host, inet_ntoa(*(struct in_addr*)&addr.sin_addr));

	return 0;

}

int dns_resolver_udp_read_cb(struct _net_poll_t *net_poll, struct _socket_t *socket)
{
	udp_socket_t *udp_socket = (udp_socket_t*)socket;
	dns_resolver_t *dns_resolver = (dns_resolver_t*)socket->ctx;
	char buff[1024], *p;
	dns_header_t *dns_header = (dns_header_t*)buff;
	struct sockaddr_in addr;
	int ret, dnsid;
	dns_ctx_t *dns_ctx;
	
	memset(&addr, 0, sizeof(struct sockaddr_in));
	ret = udp_socket_recvfrom(udp_socket, buff, 1024, &addr);
	if( ret < sizeof(dns_header_t) ) { return 0; }

	dnsid = dns_header->id;
	dns_ctx = dns_resolver->dns_reqs[dnsid % MAX_DNS_REQS];
	dns_resolver->dns_reqs[dnsid % MAX_DNS_REQS] = NULL;
	if( ! dns_ctx ) { return 0; }

	if( dns_header->flags.qr == 1 && dns_header->flags.rcode == 0 ) {
		struct sockaddr_in addr;
		p = buff + ret - 4;
		addr.sin_addr.s_addr = *(unsigned int*)p;
		printf("dns_resolver_udp_read_cb [%s] => [%s] \n", dns_ctx->host, inet_ntoa(*(struct in_addr*)&addr.sin_addr));
		dns_ctx->host_info.ips[0] = *(unsigned int*)p;
		dns_ctx->dns_resolver_getip_cb(dns_ctx, 0, 0);
	} else {
		dns_ctx->dns_resolver_getip_cb(dns_ctx, 1, 1);
	}

	return 0;
}

dns_resolver_t* create_dns_resolver()
{
	dns_resolver_t *dns_resolver = malloc(sizeof(dns_resolver_t));

	dns_resolver->dns_req_id = 0;

	dns_resolver->dns_reqs = malloc(sizeof(dns_ctx_t*) * MAX_DNS_REQS);
	memset(dns_resolver->dns_reqs, 0, sizeof(dns_ctx_t*) * MAX_DNS_REQS);
	
	udp_socket_create(&dns_resolver->udp_socket);
	dns_resolver->udp_socket.socket.event_read_net_poll_cb = dns_resolver_udp_read_cb;
	dns_resolver->udp_socket.socket.event_write_net_poll_cb = NULL;
	dns_resolver->udp_socket.socket.ctx = dns_resolver;
	dns_resolver->udp_socket.socket.data = NULL;

	return dns_resolver;
}

int dns_resolver_getip(dns_resolver_t *dns_resolver, dns_ctx_t *dns_ctx)
{
	udp_socket_t *udp_socket = &dns_resolver->udp_socket;
	char buff[1024], *serverip, *p;
	dns_header_t *dns_header = (dns_header_t*)buff;
	dns_query_t *dns_query = NULL;
	struct sockaddr_in addr;
	int i = 0, len = 0, buff_size = 0;
	int ret, all_false = 1;
	char *host = dns_ctx->host;

	dns_resolver->dns_req_id++;

	for( i = 0; i < sizeof(dns_servers); i++ ) {
		serverip = dns_servers[i];
		memset(&addr, 0, sizeof(struct sockaddr_in));
		addr.sin_family = AF_INET;
		addr.sin_addr.s_addr = inet_addr(serverip);
		addr.sin_port = htons(53);

		dns_header->id = dns_resolver->dns_req_id;
		dns_header->flag = htons(0x100);
		dns_header->q_count = htons(1);
		dns_header->ans_count = 0;
		dns_header->auth_count = 0;
		dns_header->add_count = 0;

		// www.efly.cc => 3www4efly2cc
		// 记录了.符合前面字符的个数
		len = 0;
		p = buff + sizeof(dns_header_t) + 1;
		strcpy(p, host);
		while( p < buff + sizeof(dns_header_t) + strlen(host) + 1 ) {
			if( *p == '.' ) {
				*(p - len - 1) = len;
				len = 0;
			} else {
				len++;
			}
			p++;
		}
		*(p - len - 1) = len;
		
		dns_query = (dns_query_t*)(buff + sizeof(dns_header_t) + 2 + strlen(host));
		dns_query->class = htons(1);
		dns_query->type = htons(1);

		buff_size = sizeof(dns_header_t) + sizeof(dns_query_t) + strlen(host) + 2;

		ret = udp_socket_sendto(udp_socket, buff, buff_size, &addr);
		if( ret == buff_size ) { all_false = 0; }
	}

	if( all_false ) { 
		dns_resolver->dns_req_id--; 
		return DNS_ERROR; 
	}

	//set dns_ctx
	if( ! dns_resolver->dns_reqs[dns_resolver->dns_req_id % MAX_DNS_REQS] ) {
		dns_resolver->dns_reqs[dns_resolver->dns_req_id] = dns_ctx;
		dns_ctx->dnsid = dns_resolver->dns_req_id;
		dns_ctx->status = DNS_QUERY;
		return DNS_WAIT;
	} else {
		printf("dns_resolver_getip max dns query! \n");
		dns_resolver->dns_req_id--; 
		return DNS_ERROR;
	}

	return DNS_WAIT;
}

int dns_resolver_del_dns_ctx(dns_resolver_t *dns_resolver, dns_ctx_t *dns_ctx)
{
	dns_resolver->dns_reqs[dns_ctx->dnsid % MAX_DNS_REQS] = NULL;
	return 0;
}


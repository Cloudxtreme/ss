#include <efnet.h>


#define MAX_ROUTE	128

typedef struct _route_t
{
	unsigned int	dest;
	unsigned int	gateway;
	unsigned char	mask;
	struct _route_t *prev;
	struct _route_t	*next;
}route_t;

static route_t *route_head = 0;

static route_t g_route[MAX_ROUTE] = {0};
static route_t *route_pool[MAX_ROUTE] = {0};
static int route_cur = 0;
static int route_rec = 0;

int route_add(unsigned int dest, unsigned int gateway, unsigned char mask)
{
	route_t *p;

	if(route_rec)
		p = route_pool[--route_rec];
	else if(route_cur < MAX_ROUTE)
		p = &g_route[route_cur++];	//p = route_pool[route_cur++];
	else
		return -1;

	memset(p, 0, sizeof(route_t));
	p->dest = dest;
	p->gateway = gateway;
	p->mask = mask;
	p->next = route_head;
	if(route_head)
		route_head->prev = p;
	route_head = p;
}

int route_del(unsigned int dest, unsigned char mask)
{
	route_t *p = route_head;

	while(p)
	{
		if((p->dest == dest) && (p->mask == mask))
			break;
		p = p->next;
	}
	if(p)
	{
		route_pool[route_rec++] = p;
		if(p == route_head)
			route_head = route_head->next;
		else
			p->prev->next = p->next;
	}
	return 0;
}

unsigned int route_get(unsigned int ip)
{
	route_t *p = route_head;
	unsigned int dest;
	unsigned int gateway = 0;

	while(p)
	{
		dest = ip & (0xffffffff >> (32 - p->mask) << (32 - p->mask));
		if(p->dest == dest)
			break;
	}
	if(p)
	{
		gateway = p->gateway;
	}
	return gateway;
}

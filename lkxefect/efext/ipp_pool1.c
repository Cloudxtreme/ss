#include <efnet.h>

#define MAX_IP_NUM			0x1000000

#define MAX_PORT_LEN		0x400
#define PORT_BEGIN			0x400

#define MAX_FIND_NUM		0x100


typedef struct _ip_key
{
	unsigned int	ip;
}ip_key;

typedef struct _port_iplist
{
	ip_key			ik[MAX_PORT_LEN];
}port_iplist;

typedef struct _port_des
{
	port_iplist		pi[0x10000];
}port_des;

typedef struct _ip_info
{
	unsigned int	ip;
	unsigned short	tpcur_port;
	unsigned short	upcur_port;
}ip_info;

typedef struct _ip_
{
	ip_info			ipinfo[MAX_IP_NUM];
}ip_des;


static ip_des		ipd = {0};
static port_des		tppod = {0};
static port_des		uppod = {0};


int ipp_pool_getport(unsigned int ip, int proto)
{
	unsigned int 	ip_num;
	unsigned short 	po_num;
	unsigned short 	*port_cur;
	port_des		*pod;
	
	int i = 0;

	if((proto != PKT_TYPE_TCP) && (proto != PKT_TYPE_UDP))
		return -1;
	if(ip == 0)
		return -1;

	ip_num = ip >> 8;
	po_num = ip >> 22;
	if((ipd.ipinfo[ip_num].ip == ip) || !(ipd.ipinfo[ip_num].ip))
	{
		ipd.ipinfo[ip_num].ip = ip;
		if(proto == PKT_TYPE_TCP)
		{
			pod = &tppod;
			port_cur = &(ipd.ipinfo[ip_num].tpcur_port);
		}
		else
		{
			pod = &uppod;
			port_cur = &(ipd.ipinfo[ip_num].upcur_port);
		}
		do
		{
			*port_cur = (*port_cur + 1 == 0x10000) ? (0) : (*port_cur + 1);
			if(*port_cur < PORT_BEGIN)
				*port_cur = PORT_BEGIN;
		}while((pod->pi[*port_cur].ik[po_num].ip) && (i++ < MAX_FIND_NUM));

		if(i >= MAX_FIND_NUM)
			goto none;
		
	}
	else
	{
		goto none;
	}

	pod->pi[*port_cur].ik[po_num].ip = ip;
	return *port_cur;
none:
	return 0;
}

int ipp_pool_recport(unsigned int ip, unsigned short port, int proto)
{
	unsigned int ip_num;
	unsigned short 	po_num;
	port_des	*pod;

	if(ip == 0)
		return -1;

	if(proto == PKT_TYPE_TCP)
		pod = &tppod;
	else if(proto == PKT_TYPE_UDP)
		pod = &uppod;
	else
		return -1;

	ip_num = ip >> 8;
	po_num = ip >> 22;

	if(pod->pi[port].ik[po_num].ip == ip)
	{
		pod->pi[port].ik[po_num].ip = 0;
		return 1;
	}

none:
	return 0;
}

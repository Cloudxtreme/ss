#include <efnet.h>


typedef struct _port_des
{
	unsigned int	ip;
	
	unsigned short	tpp_port[0x10000];
	unsigned short	upp_port[0x10000];
	unsigned short	icmp_port[0x10000];

	unsigned char	tpp_stat[0x10000];
	unsigned char	upp_stat[0x10000];
	unsigned char	icmp_stat[0x10000];

	unsigned short	tpp_cur, tpp_rec;
	unsigned short	upp_cur, upp_rec;
	unsigned short	icmp_cur, icmp_rec;

	struct _port_des	*next;
}port_des;


typedef struct _ipp_des
{
	port_des 		*pds[0x10000];
	char			pds_sync[0x10000];
}ipp_des;

static void			*g_buf[0x10000];
static int			g_tot = 0;

static port_des		*port_des_pool[0x10000];
static int			pool_cur = 0;
static int			pool_size = 0;


static ipp_des		ipd = {0};


static int ipp_pool_expand(int n)
{
	void *buf;
	int i, j, k;

	if(pool_size + n >= 0x10000)
		n = 0x10000 - pool_size;
	
	g_buf[g_tot] = buf = (void *)malloc(n * sizeof(port_des));
	if(buf)
	{
		g_tot++;
		memset(buf, 0, n * sizeof(port_des));

		for(i = 0; i < n; i++)
		{
			k = pool_size + i;
			port_des_pool[k] = (port_des *)(buf + i * sizeof(port_des));
			port_des_pool[k]->tpp_cur = port_des_pool[k]->upp_cur = 1024;
			port_des_pool[k]->tpp_rec = port_des_pool[k]->upp_rec = 1024;
			for(j = 0; j < 0x10000; j++)
			{
				port_des_pool[k]->tpp_port[j] = j;
				port_des_pool[k]->upp_port[j] = j;
			}
		}

		pool_size += n;
		goto succ;
	}
	
fail:
	return -1;
succ:
	return 0;
}

int ipp_pool_init(int n)
{
	return ipp_pool_expand(n * 1.5);
}

int ipp_pool_tini()
{
	do
	{
		g_tot--;
		free(g_buf[g_tot]);
	}while(g_tot);
	return 0;
}

int ipp_pool_getport(unsigned int ip, int proto)
{
	unsigned short *p = (unsigned short *)&ip;
	unsigned short x = p[0] ^ p[1];
	port_des	**p_des = &(ipd.pds[x]);

	port_des	*des = 0;
	unsigned short port = 0;

	if((proto != PKT_TYPE_TCP) && (proto != PKT_TYPE_UDP) && (proto != PKT_TYPE_ICMP))
		return -1;

	if(ip == 0)
		return -1;

#ifndef IPP_NO_LOCK
	while(__sync_lock_test_and_set(&(ipd.pds_sync[x]), 1));
#endif

	while(*p_des)
	{
		if((*p_des)->ip == ip)
			break;
		p_des = &((*p_des)->next);
	}
	
	if(!(*p_des))
	{
		if(pool_cur >= pool_size)
			if(-1 == ipp_pool_expand(pool_size>>1))
				goto none;
			
		des = port_des_pool[pool_cur];
		pool_cur++;
		des->ip = ip;
		*p_des = des;
	}
	else
	{
		des = *p_des;
	}

	if(des)
	{
		unsigned short	*p_port;
		unsigned char	*p_stat;
		unsigned short	*p_cur;

		if(proto == PKT_TYPE_TCP)
		{
			p_port = des->tpp_port;
			p_stat = des->tpp_stat;
			p_cur = &(des->tpp_cur);
		}
		else if(proto == PKT_TYPE_UDP)
		{
			p_port = des->upp_port;
			p_stat = des->upp_stat;
			p_cur = &(des->upp_cur);
		}
		else
		{
			p_port = des->icmp_port;
			p_stat = des->icmp_stat;
			p_cur = &(des->icmp_cur);
		}
		
		port = p_port[*p_cur];
		if(port)
		{
			p_port[*p_cur] = 0;
			*p_cur = (*p_cur + 1 == 0x10000) ? (1024) : (*p_cur + 1);
			p_stat[port] = 1;
		}
	}

#ifndef IPP_NO_LOCK
	__sync_lock_test_and_set(&(ipd.pds_sync[x]), 0);
#endif
	return port;

none:
#ifndef IPP_NO_LOCK
	__sync_lock_test_and_set(&(ipd.pds_sync[x]), 0);
#endif
	return 0;
}

int ipp_pool_recport(unsigned int ip, unsigned short port, int proto)
{
	unsigned short *p = (unsigned short *)&ip;
	unsigned short x = p[0] ^ p[1];
	port_des	**p_des = &(ipd.pds[x]);

	port_des	*des;

	if((proto != PKT_TYPE_TCP) && (proto != PKT_TYPE_UDP) && (proto != PKT_TYPE_ICMP))
		return -1;

	if(port < 1024)
		goto none;

	if(ip == 0)
		return -1;
	
#ifndef IPP_NO_LOCK
	while(__sync_lock_test_and_set(&(ipd.pds_sync[x]), 1));
#endif

	while(*p_des)
	{
		if((*p_des)->ip == ip)
			break;
		p_des = &((*p_des)->next);
	}

	des = *p_des;
	if(des)
	{
		unsigned short	*p_port;
		unsigned char	*p_stat;
		unsigned short	*p_rec;

		if(proto == PKT_TYPE_TCP)
		{
			p_port = des->tpp_port;
			p_stat = des->tpp_stat;
			p_rec = &(des->tpp_rec);
		}
		else if(proto == PKT_TYPE_UDP)
		{
			p_port = des->upp_port;
			p_stat = des->upp_stat;
			p_rec = &(des->upp_rec);
		}
		else
		{
			p_port = des->icmp_port;
			p_stat = des->icmp_stat;
			p_rec = &(des->icmp_rec);
		}

		if(p_stat[port])
		{
			p_stat[port] = 0;
			p_port[*p_rec] = port;
			*p_rec = (*p_rec + 1 == 0x10000) ? (1024) : (*p_rec + 1);

			goto succ;
		}
	}

none:
#ifndef IPP_NO_LOCK
	__sync_lock_test_and_set(&(ipd.pds_sync[x]), 0);
#endif
	return 0;
succ:
#ifndef IPP_NO_LOCK
	__sync_lock_test_and_set(&(ipd.pds_sync[x]), 0);
#endif
	return 1;
}

#if 0
int ipp_pool_printall()
{
	int i, j = 0;
	port_des *des;

	for(i = 0; i < 0x10000; i++)
	{
		des = ipd.pds[i];
		while(des)
		{
			fprintf(stderr, "[%d_%d]->0x%x\t", j++, i, des->ip);
			des = des->next;
		}
		if(ipd.pds[i])
			fprintf(stderr, "\n");
	}
}
#endif


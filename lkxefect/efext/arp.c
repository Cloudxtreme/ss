#include <efnet.h>
#include <efio.h>
#include <linux/if.h>
#include <sys/socket.h>
#include <sys/ioctl.h>
#include <stdio.h>


#define EPT_IP   		0x0008			//0x0800    /* type: IP */
#define EPT_ARP   		0x0608			//0x0806    /* type: ARP */
#define EPT_RARP 		0x3580			//0x8035    /* type: RARP */
#define ARP_HARDWARE 	0x0100			//0x0001    /* Dummy type for 802.3 frames */
#define ARP_REQUEST 	0x0100			//0x0001    /* ARP request */
#define ARP_REPLY 		0x0200			//0x0002    /* ARP reply */


#define MAX_LOCAL_LINK		0x100
#define MAX_ARP_NUM			0x5000


typedef struct _local_link
{
	unsigned int	ip;
	unsigned int	net;
	unsigned char	mask;
	unsigned char	dev[16];
	unsigned char	dev_len;
	unsigned char	mac[6];
}local_link;


typedef struct _arp_keyval
{
	unsigned int 		ip;
	unsigned char		name[32];
	unsigned char		name_len;
	unsigned char		mac[6];
	unsigned char		flush;
	struct _arp_keyval	*prev;
	struct _arp_keyval	*next;
}arp_keyval;

typedef struct _arp_table
{
	arp_keyval		*key_info[0x10000];
}arp_table;



static local_link ll[MAX_LOCAL_LINK];
static int ll_total = 0;


static arp_table	at = {0};

static arp_keyval g_ak[MAX_ARP_NUM] = {0};
static int ak_cur = 0;


int arp_build_req(unsigned int sip, unsigned char *smac, unsigned int dip, char *pkt_buf);
int arp_build_reply(unsigned int sip, unsigned char *smac, unsigned int dip, unsigned char *dmac, char *pkt_buf);


int dev_if_up(char *nic_name)
{
    struct ifreq ifr;
    int skfd = socket(AF_INET, SOCK_DGRAM, 0);

    strcpy(ifr.ifr_name, nic_name);
    if (ioctl(skfd, SIOCGIFFLAGS, &ifr) < 0)
    {
        return 0;
    }
    close(skfd);
    if(ifr.ifr_flags & IFF_UP)
        return 1;  // 网卡已启用
    else
        return 0;
}

int dev_if_run(char *nic_name)
{
    struct ifreq ifr;
    int skfd = socket(AF_INET, SOCK_DGRAM, 0);

    strcpy(ifr.ifr_name, nic_name);
    if (ioctl(skfd, SIOCGIFFLAGS, &ifr) < 0)
    {
        return 0;
    }
    close(skfd);
    if(ifr.ifr_flags & IFF_RUNNING)
        return 1;  // 网卡已插上网线
    else
        return 0;
}

int get_dev_mac(unsigned char *dev, unsigned char *mac)
{
	struct ifreq ifr;
	int s;

	if(!dev || !mac)
		return -1;
	s = socket(AF_INET, SOCK_DGRAM, 0);
	if(-1 == s)
		return -1;
	strcpy(ifr.ifr_name, dev);
	if(ioctl(s, SIOCGIFFLAGS, &ifr) == 0)
	{
		if(!(ifr.ifr_flags & IFF_LOOPBACK))
		{
			if(ioctl(s, SIOCGIFHWADDR, &ifr) == 0)
			{
				char *p = (char *)ifr.ifr_hwaddr.sa_data;
				if (*((int *)p) || *((int *)(p+2)) )
					bcopy( ifr.ifr_hwaddr.sa_data, mac, 6);
			}
		}
	}
	close(s);
	return 0;
}

int get_dev_ip(unsigned char *dev)
{
	struct ifreq ifr;
	int s;
	unsigned int ip;

	if(!dev)
		return -1;
	s = socket(AF_INET, SOCK_DGRAM, 0);
	if(-1 == s)
		return -1;
	strcpy(ifr.ifr_name, dev);
	if(ioctl(s, SIOCGIFFLAGS, &ifr) == 0)
	{
		if(!(ifr.ifr_flags & IFF_LOOPBACK))
		{
			if(ioctl(s, SIOCGIFADDR, &ifr) == 0)
			{
				ip = *(unsigned int *)&ifr.ifr_broadaddr.sa_data[2];
			}
		}
	}
	close(s);
	return ip;
}

int local_reg(unsigned int ip, unsigned char mask, unsigned char *dev)
{
	unsigned int net;
	if(!ip || !mask || !dev)
		return -1;
	if(mask > 32)
		return -1;
	net = ip & (0xffffffff << (32 - mask) >> (32 - mask));
	ll[ll_total].ip = ip;
	ll[ll_total].net = net;
	ll[ll_total].mask = mask;
	memcpy(ll[ll_total].dev, dev, strlen(dev));
	ll[ll_total].dev_len = strlen(dev);
	get_dev_mac(ll[ll_total].dev, ll[ll_total].mac);
	ll_total++;
	return 0;
}

static local_link *get_local_link(unsigned int ip)
{
	unsigned int net;
	int i;

	for(i = 0; i < ll_total; i++)
	{
		net = ip & (0xffffffff << (32 - ll[i].mask) >> (32 - ll[i].mask));
		if(net == ll[i].net)
			return &ll[i];
	}
	return 0;
}

int local_getdev_byip(unsigned int ip, unsigned char *dev, int len)
{
	local_link *pll;

	if(!ip || !dev || !len)
		return -1;
	if((pll = get_local_link(ip)))
	{
		if(len > pll->dev_len)
			len = pll->dev_len;
		memcpy(dev, pll->dev, len);
		return 1;
	}
	return 0;
}

static int arp_register(unsigned int ip, unsigned char *name, unsigned char flush)
{
	int key_num;
	unsigned short *p = (unsigned short *)&ip;
	arp_keyval **pak;

	if(!ip)
		return -1;

	key_num = p[0] ^ p[1];
	pak = &at.key_info[key_num];

	while(*pak && ((*pak)->ip != ip)) pak = &((*pak)->next);
	if(!(*pak))
		if(ak_cur < MAX_ARP_NUM)
			*pak = &g_ak[ak_cur++];
		else
			goto none;

	(*pak)->ip = ip;
	if(flush)
		(*pak)->flush = flush;
	if(name)
	{
		memcpy((*pak)->name, name, strlen(name));
		(*pak)->name_len = strlen(name);
	}

	return 1;

none:
	return 0;
}

int arp_reg(unsigned int ip, unsigned char *name)
{
	return arp_register(ip, name, 1);
}

int arp_setmac_byname(unsigned char *name, unsigned char *mac)
{
	int i;
	unsigned char *ipmac;

	if(!name || !mac)
		return -1;

	for(i = 0; i < ak_cur; i++)
	{
		if(!memcpy(g_ak[i].name, name, g_ak[i].name_len))
			break;
	}
	if(i >= ak_cur)
		return 0;


	ipmac = (unsigned int *)g_ak[i].mac;
	*ipmac++ = *mac++;
	*ipmac++ = *mac++;
	*(unsigned int*)ipmac = *(unsigned int*)mac;

	return 1;
}

int arp_setmac_byip(unsigned int ip, unsigned char *mac)
{
	int key_num;
	unsigned short *p = (unsigned short *)&ip;
	unsigned char *ipmac;
	arp_keyval **pak;

	if(!ip || !mac)
		return -1;

	key_num = p[0] ^ p[1];
	pak = &at.key_info[key_num];

	while(*pak && ((*pak)->ip != ip)) pak = &((*pak)->next);
	if(!(*pak))
		return 0;

	ipmac = (*pak)->mac;
	*ipmac++ = *mac++;
	*ipmac++ = *mac++;
	*(unsigned int*)ipmac = *(unsigned int*)mac;

	return 1;
}

int arp_getmac_byname(unsigned char *name, unsigned char *mac)
{
	int i;
	unsigned char *ipmac;

	if(!name || !mac)
		return -1;

	for(i = 0; i < ak_cur; i++)
	{
		if(!memcpy(g_ak[i].name, name, g_ak[i].name_len))
			break;
	}
	if(i >= ak_cur)
		return 0;


	ipmac = (unsigned int *)g_ak[i].mac;
	*mac++ = *ipmac++;
	*mac++ = *ipmac++;
	*(unsigned int*)mac = *(unsigned int*)ipmac;

	return 1;
}

int arp_getmac_byip(unsigned int ip, unsigned char *mac)
{
	unsigned char *ipmac;
	unsigned short *p = (unsigned short *)&ip;
	int key_num;
	arp_keyval **pak;

	if((!ip) || (!mac))
		return -1;

	key_num = p[0] ^ p[1];
	pak = &at.key_info[key_num];

	while(*pak && ((*pak)->ip != ip)) pak = &((*pak)->next);
	if(!(*pak))
		return 0;


	ipmac = (*pak)->mac;
	*mac++ = *ipmac++;
	*mac++ = *ipmac++;
	*(unsigned int*)mac = *(unsigned int*)ipmac;

	return 1;
}

int arp_recv_pkg(char *pkg)
{
	if(!pkg)
		return -1;

	if(IF_ARP_REQ(pkg))
	{
		ef_slot slot;
		unsigned char dev[16] = {0};
		unsigned int sip = GET_ARP_SIP(pkg);
		unsigned int smac = GET_ARP_SMAC(pkg);
		unsigned int dip = GET_ARP_DIP(pkg);

		local_link *ll = get_local_link(dip);

		if(ll)
		{
			arp_register(sip, NULL, 0);
			arp_setmac_byip(sip, smac);
			if(ll->ip == dip)
			{
				int fd = efio_getfd_bydev(ll->dev);
				if(fd)
				{
					slot.len = arp_build_reply(dip, ll->mac, sip, smac, slot.buf);
					efio_send(fd, &slot, 1);
				}
			}
			return 1;
		}
	}
	else if(IF_ARP_REPLY(pkg))
	{
		unsigned int sip = GET_ARP_SIP(pkg);
		unsigned int dip = GET_ARP_DIP(pkg);
		unsigned char *smac = GET_ARP_SMAC(pkg);

		local_link *ll = get_local_link(dip);
		if(ll)
		{
			arp_register(sip, NULL, 0);
			arp_setmac_byip(sip, smac);
		}

		return 1;
	}
	return 0;
}

#if 0
int arp_printf()
{
	int i;
	unsigned char str[32];
	unsigned char *mac;
	fprintf(stderr, "-------------------------\n");
	for(i = 0; i < ak_cur; i++)
	{
		ip_2_str(g_ak[i].ip, str);
		mac = g_ak[i].mac;
		fprintf(stderr, "ip : %s\t mac : %x:%x:%x:%x:%x:%x\n", str, mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);
	}
	fprintf(stderr, "arp num : %d\n", ak_cur);
	fprintf(stderr, "-------------------------\n");
}
#endif

int arp_flush()
{
	int i, j;
	unsigned int ip;
	local_link *ll;
	int fd;
	ef_slot slot;

	j = 0;
	for(i = 0; i < ak_cur; i++)
	{
		if(g_ak[i].flush)
		{
			ip = g_ak[i].ip;
			ll = get_local_link(ip);
			if(ll)
			{
				fd = efio_getfd_bydev(ll->dev);
				if(fd)
				{
					slot.len = arp_build_req(ll->ip, ll->mac, ip, slot.buf);
					efio_send(fd, &slot, 1);
				}
			}
		}
	}
	return 0;
}

int arp_build_req(unsigned int sip, unsigned char *smac, unsigned int dip, char *pkt_buf)
{
	unsigned char dmac[6] = {0xff, 0xff, 0xff, 0xff, 0xff, 0xff};
	pkt_t *p = (pkt_t *)pkt_buf;
	int len;

	if((!sip) || (!smac) || (!dip) || (!pkt_buf))
		return 0;

	memcpy(p->dmac, dmac, 6);
	memcpy(p->smac, smac, 6);
	p->eth_type = PKT_TYPE_ARP;
	p->l2.n_pkt.l3.arpp.arp_hrd = 0x0100;
	p->l2.n_pkt.l3.arpp.arp_pro = PKT_TYPE_IP;
	p->l2.n_pkt.l3.arpp.arp_hln = 6;
	p->l2.n_pkt.l3.arpp.arp_pln = 4;
	p->l2.n_pkt.l3.arpp.arp_op = 0x0100;
	memcpy(p->l2.n_pkt.l3.arpp.arp_sha, smac, 6);
	p->l2.n_pkt.l3.arpp.arp_spa = sip;
	memset(p->l2.n_pkt.l3.arpp.arp_tha, 0, 6);
	p->l2.n_pkt.l3.arpp.arp_tpa = dip;

	len = sizeof(arp_pkt) + 14;
	return len;
}

int arp_build_reply(unsigned int sip, unsigned char *smac, unsigned int dip, unsigned char *dmac, char *pkt_buf)
{
	pkt_t *p = (pkt_t *)pkt_buf;
	int len;

	if((!sip) || (!smac) || (!dip) || (!dmac) || (!pkt_buf))
		return 0;

	memcpy(p->dmac, dmac, 6);
	memcpy(p->smac, smac, 6);
	p->eth_type = PKT_TYPE_ARP;
	p->l2.n_pkt.l3.arpp.arp_hrd = 0x0100;
	p->l2.n_pkt.l3.arpp.arp_pro = PKT_TYPE_IP;
	p->l2.n_pkt.l3.arpp.arp_hln = 6;
	p->l2.n_pkt.l3.arpp.arp_pln = 4;
	p->l2.n_pkt.l3.arpp.arp_op = 0x0200;
	memcpy(p->l2.n_pkt.l3.arpp.arp_sha, smac, 6);
	p->l2.n_pkt.l3.arpp.arp_spa = sip;
	memcpy(p->l2.n_pkt.l3.arpp.arp_tha, dmac, 6);
	p->l2.n_pkt.l3.arpp.arp_tpa = dip;

	len = sizeof(arp_pkt) + 14;
	return len;
}

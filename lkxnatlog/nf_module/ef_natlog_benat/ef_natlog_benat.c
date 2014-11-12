#ifndef __KERNEL__
#define __KERNEL__
#endif
#ifndef MODULE
#define MODULE
#endif

#include <linux/version.h>
#include <linux/module.h>
#include <linux/kernel.h>
#include <linux/skbuff.h>
#include <linux/in.h>
#include <linux/ip.h>
#include <linux/tcp.h>
#include <linux/icmp.h>
#include <linux/netdevice.h>
#include <linux/netfilter.h>
#include <linux/netfilter_ipv4.h>
#include <linux/if_arp.h>
#include <linux/if_ether.h>
#include <linux/if_packet.h>
#include <linux/string.h>
#include <linux/inet.h>
#include <linux/fs.h>
#include <net/ip.h>

/**** add 2013-01-07****/
#if LINUX_VERSION_CODE >= KERNEL_VERSION(2, 6, 25)
#include <net/netfilter/nf_log.h>
#include <linux/netfilter/nf_conntrack_common.h>
#endif
/**********************/


#define LOGIPCHECK_CONF_FILE	"/var/tmp/logipcheck"
#define MAX_PROCNT	10


/**** add 2013-01-07****/

/**********************/

#define SNAT			1
#define DNAT			2


static struct nf_hook_ops   nfho_out;

static unsigned 		logipcheck[MAX_PROCNT] = {0};
static unsigned int		nat_type = 0;
static unsigned int 	enable_logipcheck = 0;
static unsigned int 	enable_ipstatenew = 0;
static unsigned int		check_tosflag = 0;
static int 				max_nat_slot = 0;
static int 				now_nat_slot = 0;
module_param(enable_logipcheck, int, 0);
module_param(nat_type, int, 0);
module_param(enable_ipstatenew, int, 0);
module_param(max_nat_slot, int, 0);
module_param(check_tosflag, int, 0);
MODULE_LICENSE("GPL");

#ifndef NIPQUAD
#define NIPQUAD(addr) \
        ((unsigned char *)&addr)[0], \
        ((unsigned char *)&addr)[1], \
        ((unsigned char *)&addr)[2], \
        ((unsigned char *)&addr)[3]
#endif

#ifndef NIPQUAD_FMT
#define NIPQUAD_FMT "%u.%u.%u.%u"
#endif

int init_logipcheck_conf(void)
{
	struct file *fconf;
	ssize_t ret;
	mm_segment_t old_fs;
	char buff[500] = {0};
	int index = 0, i, len;
	char temp[20];

	fconf = filp_open(LOGIPCHECK_CONF_FILE, O_RDONLY, 0);
	if( IS_ERR(fconf) )
	{
		return 1;
	}

	old_fs = get_fs();
	set_fs(get_ds());

	ret = fconf->f_op->read(fconf, buff, 1000, &fconf->f_pos);
	for( i = 0; i < strlen(buff); i++ )
	{
		len = 0;
		while( buff[i] != ';' && len < 20 ) {
			temp[len++] = buff[i++];
		}
		if( buff[i++] != ';' ) {
			break;
		}
		temp[len] = '\0';

		logipcheck[index++] = in_aton(temp);
	}

	set_fs(old_fs);
	filp_close(fconf, 0);
	return 0;
}

unsigned int logip_check(unsigned int ip)
{
	int i;
	for( i = 0; i < MAX_PROCNT; i++ )
	{
		if( logipcheck[i] == ip ) {
			return 1;
		}
	}
	return 0;
}

#if LINUX_VERSION_CODE >= KERNEL_VERSION(2, 6, 26)
unsigned int hook_out_func(unsigned int hooknum, 
					struct sk_buff *skb, 
					const struct net_device   *in, 
					const struct net_device   *out, 
					int (*okfn)(struct sk_buff *))
#else
unsigned int hook_out_func(unsigned int hooknum, 
					struct sk_buff **skb, 
					const struct net_device   *in, 
					const struct net_device   *out, 
					int (*okfn)(struct sk_buff *))

#endif
{
#if LINUX_VERSION_CODE >= KERNEL_VERSION(2, 6, 26)
	struct sk_buff *sb = skb;
#else
	struct sk_buff *sb = *skb;
#endif
	struct iphdr *ip = ip_hdr(sb);
	
	if(!sb)
	{
		return NF_ACCEPT;
	}

	if(check_tosflag)
	{
		if((!ip) || (ip->tos != check_tosflag))
			return NF_ACCEPT;
	}

	/*
	if( enable_logipcheck && logip_check(ip->daddr) ) 
	{
		return NF_ACCEPT;		
	}
	*/

	if(max_nat_slot)
	{
		if(now_nat_slot >= max_nat_slot)
			return NF_ACCEPT;
		now_nat_slot++;
	}

	if(enable_ipstatenew)
	{
		if(sb->nfctinfo != IP_CT_NEW)
			return NF_ACCEPT;
	}

#if LINUX_VERSION_CODE >= KERNEL_VERSION(2, 6, 28)
	nf_log_packet(NFPROTO_IPV4, hooknum, sb, in, out, NULL, "efly_benat");
#else
	nf_log_packet(PF_INET, hooknum, sb, in, out, NULL, "efly_benat");
#endif

	return NF_ACCEPT;
}


int init_module()
{
	if( enable_logipcheck && init_logipcheck_conf() ) {
		return 1;
	}

	if(max_nat_slot == 0)
		max_nat_slot = 10;
	if(max_nat_slot < 0)
		max_nat_slot = 0;
	
	if((nat_type != SNAT) && (nat_type != DNAT))
	{
		nat_type = SNAT;
	}

	printk("begin init module natlog \n");
	printk("[BENAT] NAT_SLOT:%d NAT_TYPE:%d ENABLE_IPSTATENEW:%d\n", 
		max_nat_slot, nat_type, enable_ipstatenew);

	nfho_out.hook = hook_out_func;
#if LINUX_VERSION_CODE >= KERNEL_VERSION(2, 6, 26)
	if(nat_type == SNAT)
		nfho_out.hooknum = NF_INET_POST_ROUTING;
	else
		nfho_out.hooknum = NF_INET_PRE_ROUTING;
#else
	if(nat_type == SNAT)
		nfho_out.hooknum = NF_IP_POST_ROUTING;
	else
		nfho_out.hooknum = NF_IP_PRE_ROUTING;
#endif
	nfho_out.owner = THIS_MODULE;
	nfho_out.pf = PF_INET;
	if(nat_type == SNAT)
		nfho_out.priority = NF_IP_PRI_NAT_SRC - 1;
	else
		nfho_out.priority = NF_IP_PRI_NAT_DST - 1;
	nf_register_hook(&nfho_out);

	printk("end init module natlog \n");

	return 0;
}

void cleanup_module()
{
	nf_unregister_hook(&nfho_out);
	printk("cleanup module natlog \n");
}
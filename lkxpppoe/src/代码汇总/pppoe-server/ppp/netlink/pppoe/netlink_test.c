#include <linux/kernel.h>
#include <linux/module.h>
#include <linux/types.h>
#include <linux/sched.h>
#include <net/sock.h>
#include <net/netlink.h>
#include <linux/init.h>
#include <linux/netdevice.h>
#include <linux/skbuff.h>
#include <linux/netfilter_ipv4.h>
#include <linux/inet.h>
#include <linux/in.h>
#include <linux/ip.h>
#include <linux/udp.h>

#define NETLINK_TEST 21

MODULE_LICENSE("GPL");
#define NIPQUAD(addr) \
  ((unsigned char *)&addr)[0], \
  ((unsigned char *)&addr)[1], \
  ((unsigned char *)&addr)[2], \
  ((unsigned char *)&addr)[3]

void sendMSG(__be32 src);
void nl_data_ready (struct sk_buff *__skb);
static int pid = 0;
struct sock *nl_sk = NULL;
EXPORT_SYMBOL_GPL(nl_sk);

static unsigned int sample(
unsigned int hooknum,
struct sk_buff * skb,
const struct net_device *in,
const struct net_device *out,
int (*okfn) (struct sk_buff *))
{
    __be32 sip;
    /*
    ((unsigned char *)&server)[0] = 192;
    ((unsigned char *)&server)[1] = 168;
    ((unsigned char *)&server)[2] = 22;
    ((unsigned char *)&server)[3] = 21;
    */
 	if(skb && pid!=0)
 	{
 		struct iphdr *iph;
		struct sk_buff *sb = NULL;
		
		sb = skb;
		iph  = ip_hdr(sb);
		sip = iph->saddr;
		if(((unsigned char *)&sip)[0] == 10 && iph->protocol != 0x11)
		{
			sendMSG(sip);
		}
	}
	return NF_ACCEPT;
}


void nl_data_ready (struct sk_buff *__skb)
{
	struct sk_buff *skb;
	struct nlmsghdr *nlh;
	char str[100];
	
	printk("net_link: data is ready to read.\n");
	skb = skb_get(__skb);
	
	if (skb->len >= NLMSG_SPACE(0)) 
	{
		nlh = nlmsg_hdr(skb);
		memcpy(str,NLMSG_DATA(nlh), sizeof(str));
		if(memcmp(str,"pid",3) == 0)
		{
			pid = nlh->nlmsg_pid; /*pid of sending process */
			
			printk("change pid to %d\n", pid);
		}
		kfree_skb(skb);
	}
	return;
}

void sendMSG(__be32 src)
{
	struct sk_buff *skb;
	struct nlmsghdr *nlh;
	int len = NLMSG_SPACE(sizeof(src));
	
	
	skb = alloc_skb(len, GFP_ATOMIC);
	if (!skb)
	{
		printk(KERN_ERR "net_link: allocate failed.\n");
		return;
	}
	nlh = nlmsg_put(skb,0,0,0,sizeof(src),0);
	NETLINK_CB(skb).pid = 0; /* from kernel */
	
	memcpy(NLMSG_DATA(nlh), &src, sizeof(src));
	netlink_unicast(nl_sk, skb, pid, MSG_DONTWAIT);
	return;
}

static int test_netlink(void) 
{
	nl_sk = netlink_kernel_create(&init_net, NETLINK_TEST, 0, nl_data_ready, NULL, THIS_MODULE);
	
	if (!nl_sk) 
	{
		printk(KERN_ERR "net_link: Cannot create netlink socket.\n");
		return -EIO;
	}
	printk("net_link: create socket ok.\n");
	return 0;
}

struct nf_hook_ops sample_ops = 
{
	.list =  {NULL,NULL},
	.hook = sample,
	.pf = PF_INET,
	.hooknum = NF_INET_PRE_ROUTING,	//NF_INET_FORWARD
	.priority = NF_IP_PRI_RAW+2
};

static int __init sample_init(void) 
{
	test_netlink();
	nf_register_hook(&sample_ops);
	return 0;
}


static void __exit sample_exit(void) 
{
	if (nl_sk != NULL)
	{
		sock_release(nl_sk->sk_socket);
	}
	printk("net_link: remove ok.\n");
	nf_unregister_hook(&sample_ops);
}

module_init(sample_init);
module_exit(sample_exit); 
MODULE_AUTHOR("hezuoxiang");
MODULE_DESCRIPTION("dns");



#ifndef _H_NET
#define _H_NET

#include <netinet/in.h>
#include <netinet/ip.h>
#include <netinet/udp.h>
#include <netinet/tcp.h>
#include <linux/icmp.h>
#include <linux/if_ether.h>


#define likely(x)	__builtin_expect(!!(x), 1)
#define unlikely(x)	__builtin_expect(!!(x), 0)





#define PKT_TYPE_IP					0x0008
#define PKT_TYPE_VL					0x0081
#define PKT_TYPE_ARP				0x0608


#define PKT_TYPE_UDP				0x11
#define PKT_TYPE_TCP				0x06
#define PKT_TYPE_ICMP				0x01


typedef struct ethhdr ETHH, *PETHH;


////	parse pkt api

//get pkt type
//#define GET_ETHTYPE(pkt)			(pkt ? (((pkt_t *)pkt)->eth_type) : (0))
#define ETHTYPE(pkt)			(((pkt_t *)pkt)->eth_type)


//get vlan's pkt type
#define VTHTYPE(pkt)			(((pkt_t *)pkt)->l2.v_pkt.type)

#define GET_SMAC(pkt)				(((pkt_t *)pkt)->smac)
#define GET_DMAC(pkt)				(((pkt_t *)pkt)->dmac)

#define VLAN(pkt)					(((pkt_t *)pkt)->l2.v_pkt.tci)


#define IF_VLAN(pkt)				(ETHTYPE(pkt) == PKT_TYPE_VL)

#define IF_BROADCAST(pkt)			((*(unsigned int *)(pkt) == 0xffffffff)  && (*(unsigned short *)(pkt+4) == 0xffff))

#define IF_IP(pkt)					(unlikely(IF_VLAN(pkt)) ? \
										(VTHTYPE(pkt) == PKT_TYPE_IP) : \
										(ETHTYPE(pkt) == PKT_TYPE_IP))

#define IF_ARP(pkt)					(unlikely(IF_VLAN(pkt)) ? \
										(VTHTYPE(pkt) == PKT_TYPE_ARP) : \
										(ETHTYPE(pkt) == PKT_TYPE_ARP))

//get pkt's iph addr
//#define P_IPP(pkt)					(IF_IP(pkt) ? \
//										(IF_VLAN(pkt) ? \
//											(&((pkt_t *)pkt)->l2.v_pkt.l3.ipp.iph) : \
//											(&((pkt_t *)pkt)->l2.n_pkt.l3.ipp.iph)) : \
//										(0))

//#define P_IPP(pkt)					( likely(((PETHH)pkt)->h_proto == PKT_TYPE_IP) ? \
//										((struct iphdr *)((void *)pkt + 14)) : \
//										((struct iphdr *)((void *)pkt + 18)) \
//									)

#define P_IPP(pkt)					( unlikely(IF_VLAN(pkt)) ? \
										((struct iphdr *)((void *)pkt + 18)) : \
										((struct iphdr *)((void *)pkt + 14)) \
									)

#define L4_HDR(pkt)					((char*)P_IPP(pkt)+(P_IPP(pkt)->ihl<<2))

#define P_UDPP(pkt)					((struct udphdr *)(L4_HDR(pkt)))

#define P_TCPP(pkt)					((struct tcphdr *)(L4_HDR(pkt)))

#define P_ICMPP(pkt)                ((struct icmphdr *)(L4_HDR(pkt)))


#define IF_TCP(pkt)					(P_IPP(pkt)->protocol == PKT_TYPE_TCP)
#define IF_UDP(pkt)					(P_IPP(pkt)->protocol == PKT_TYPE_UDP)
#define IF_ICMP(pkt)				(P_IPP(pkt)->protocol == PKT_TYPE_ICMP)

#define IF_SYN(pkt)					(((struct tcphdr*)L4_HDR(pkt))->syn)
#define IF_ACK(pkt)					(((struct tcphdr*)L4_HDR(pkt))->ack)
#define IF_PSH(pkt)                 (((struct tcphdr*)L4_HDR(pkt))->psh)
#define IF_RST(pkt)                 (((struct tcphdr*)L4_HDR(pkt))->rst)
#define IF_FIN(pkt)					(((struct tcphdr*)L4_HDR(pkt))->fin)

#define GET_IP_TTL(pkt)             (P_IPP(pkt)->ttl)
#define GET_IP_SIP(pkt)				(P_IPP(pkt)->saddr)
#define GET_IP_DIP(pkt)				(P_IPP(pkt)->daddr)
#define GET_IP_SPORT(pkt)			( *(unsigned short*)(L4_HDR(pkt)) )
#define GET_IP_DPORT(pkt)			( *(unsigned short*)(L4_HDR(pkt)+2) )
#define GET_ICMP_TYPE(pkt)			( *(unsigned char*)(L4_HDR(pkt)) )
#define GET_ICMP_CODE(pkt)			( *(unsigned char*)(L4_HDR(pkt)+1) )
#define GET_ICMP_ID(pkt)			( *(unsigned short*)(L4_HDR(pkt)+4) )

//#define GET_IP_SPORT(pkt)			(P_IPP(pkt) ? (ntohs(P_IPP(pkt)->source)) : (0))
//#define GET_IP_DPORT(pkt)			(P_IPP(pkt) ? (ntohs(P_IPP(pkt)->dest)) : (0))


//get pkt's arph addr
#define P_ARPP(pkt)					( unlikely(IF_VLAN(pkt)) ? \
										((arp_pkt *)((void *)pkt + 18)) : \
										((arp_pkt *)((void *)pkt + 14)) \
									)

#define IF_ARP_REQ(pkt)				(P_ARPP(pkt)->arp_op == 0x0100)
#define IF_ARP_REPLY(pkt)			(P_ARPP(pkt)->arp_op == 0x0200)

#define GET_ARP_SMAC(pkt)			(P_ARPP(pkt)->arp_sha)
#define GET_ARP_SIP(pkt)			(P_ARPP(pkt)->arp_spa)
#define GET_ARP_DMAC(pkt)			(P_ARPP(pkt)->arp_tha)
#define GET_ARP_DIP(pkt)			(P_ARPP(pkt)->arp_tpa)



#define ntohi(a)                    (((unsigned char *)&a)[0] << 24 | ((unsigned char *)&a)[1] << 16 | ((unsigned char *)&a)[2] << 8 | ((unsigned char *)&a)[3])



///		arp api


int arp_addipmac(unsigned int ip, unsigned char *mac);

int arp_getipmac(unsigned int ip, unsigned char *mac);

int arp_build_req(unsigned int sip, unsigned char *smac, unsigned int dip, char *pkt_buf);

int arp_build_reply(unsigned int sip, unsigned char *smac, unsigned int dip, unsigned char *dmac, char *pkt_buf);

///		checksum api

unsigned short checksum(void *buf, int size);

unsigned short nat_hdr_checksum(void *old_ipheader, void *new_ipheader, int l);

int nat_pkg_checksum(void *pkg, void *l3_chk, void *l4_chk);

int nat_fast_csum(void *pkg, unsigned int n_sip, unsigned int n_dip,
								unsigned short n_sport, unsigned short n_dport);


///		ip & mac & vlan api

int ip_2_str(unsigned int ip, unsigned char *str);

unsigned int str_2_ip(unsigned char *str);

int mac_2_str(unsigned char *mac, unsigned char *str);

int str_2_mac(unsigned char *str, unsigned char *mac);

int strip_vlan(char *pkg, int len);

int merge_vlan(char *pkg, int len, int vlan);


///     base api
int num_init();

int lnum_2_str(unsigned int num, unsigned char *str);

int bnum_2_str(unsigned long long num, unsigned char *str);





#pragma pack(1)
typedef struct _arp_pkt
{
	unsigned short 		arp_hrd;    	/* format of hardware address */
	unsigned short 		arp_pro;    	/* format of protocol address */
	unsigned char 		arp_hln;    	/* length of hardware address */
	unsigned char 		arp_pln;    	/* length of protocol address */
	unsigned short 		arp_op;     	/* ARP/RARP operation */
	unsigned char 		arp_sha[6];    	/* sender hardware address */
	unsigned int 		arp_spa;    	/* sender protocol address */
	unsigned char 		arp_tha[6];    	/* target hardware address */
	unsigned int 		arp_tpa;    	/* target protocol address */			/* target IP address		*/
}arp_pkt;

typedef struct _ip_pkt
{
	struct iphdr 		iph;
}ip_pkt;

typedef struct _vlan_pkt
{
	unsigned short		tci;
	unsigned short		type;
	union
	{
		ip_pkt		ipp;
		arp_pkt		arpp;
	}l3;
}vlan_pkt;

typedef struct _normal_pkt
{
	union
	{
		ip_pkt		ipp;
		arp_pkt		arpp;
	}l3;
}normal_pkt;

typedef struct _pkt_t
{
	char				dmac[6];
	char				smac[6];
	unsigned short		eth_type;
	union
	{
		vlan_pkt		v_pkt;
		normal_pkt		n_pkt;
	}l2;
}pkt_t;


#endif

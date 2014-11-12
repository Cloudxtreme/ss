#ifndef _EFVPN_
#define _EFVPN_

#define SYS_NAME        "VDL虚拟数据链路"
#define SYS_VERSION     "V2.1.0"

#define VPN_FLAG_NORMAL			0xFFFF524A 		//"RJ"
#define VPN_FLAG_SLICE_EE		0xFFFF4545 		//"EE"
#define VPN_FLAG_SLICE_FF		0xFFFF4646 		//"FF"
#define VPN_FLAG_REPORT_ASK		0xFFFF5241 		//"RA"
#define VPN_FLAG_REPORT_REPLY	0xFFFF5252 		//"RR"

#define VPN_DATA_LEN			52//44			//dmac + smac + vlan + type + iphdr + flag + id
#define VPN_DATA_LEN_EXT		56//48			//VPN_DATA_LEN + PKG_ID

#define VPN_LINK_TIMEOUT		5000000

#define VPN_POSITION_FLAG			46//38
#define VPN_POSITION_LINK_ID		50//42
#define VPN_POSITION_PKG_ID			52//44
#define VPN_POSITION_LINK_REPORT	52//44

#define VPN_HDR_UDP_SPORT           36500
#define VPN_HDR_UDP_DPORT           36501

#define MAX_PKG_LEN				1487//1500

#define SLICE_EE_SLOT_LEN		1600
#define SLICE_FF_SLOT_LEN		1600
#define SLICE_EE_SLOT_NUM		0x100000
#define SLICE_FF_SLOT_NUM		0x100000

#define SLICE_TIMEOUT			500000

#define VPN_MAX_LINK			1024


typedef struct _link_report
{
	unsigned long		time;
	unsigned char		conn;
	unsigned long long	sendpkg;
	unsigned long long	reachpkg;
}link_report;

typedef struct _vpn_link
{
	unsigned short		id;
	unsigned char		enable;
	unsigned char		conn;
	unsigned char		complete;
	unsigned long		runtime;
	unsigned int		gate;
	unsigned char		dmac[6];
	unsigned char		smac[6];
	short				vlan;
	unsigned int		sip;
	unsigned int		dip;
	unsigned char		link_hdr[VPN_DATA_LEN];		//dmac outbound_smac [vlan] iphdr
	unsigned char		iph_offset;
	unsigned long		iph_sum;					//not include id and tot_len
	unsigned long long	pkg;
	unsigned long long	flow;
	unsigned long long	lpkg;
	unsigned long long	lflow;
	unsigned int		delay;
	unsigned int		lost;
	unsigned long		ask;
	unsigned long		reply;
}vpn_link;

typedef struct _vpn_des
{
	unsigned int	use;
	unsigned int	mod;
	unsigned int	log;
	unsigned int	log_ip;
	unsigned int	log_port;
	unsigned int	log_level;
	unsigned int	check;
	unsigned int	vpn_mode;
	unsigned int	vpn_layer;
	unsigned int	vpn_host;

	int				vpn_backend_fd;
	int				vpn_outbound_fd;
	unsigned char	vpn_backend_dev[32];
	unsigned char	vpn_outbound_dev[32];
	unsigned char	vpn_backend_mac[6];
	unsigned char	vpn_outbound_mac[6];
	unsigned int	vpn_backend_ip;
	unsigned int	vpn_outbound_ip;

	vpn_link		vpn_outbound_links[VPN_MAX_LINK];
	unsigned int	vpn_outbound_link_cur;
	unsigned int	vpn_outbound_link_tot;

	unsigned int	vpn_outbound_alive_links[VPN_MAX_LINK];
	unsigned int	vpn_outbound_alive_link_cur;
	unsigned int	vpn_outbound_alive_link_tot;

	unsigned char	vpn_outbound_id_bitmap[0xffff];
	unsigned char	vpn_outbound_ip_bitmap[0xffff];

}vpn_des;

#endif

#ifndef _EFEXT_
#define _EFEXT_


/* ARP API */
int dev_if_up(char *nic_name);
int dev_if_run(char *nic_name);
int get_dev_mac(unsigned char *dev, unsigned char *mac);
int local_reg(unsigned int ip, unsigned char mask, unsigned char *dev);
int local_getdev_byip(unsigned int ip, unsigned char *dev, int len);
int arp_reg(unsigned int ip, unsigned char *name);

int arp_setmac_byname(unsigned char *name, unsigned char *mac);
int arp_setmac_byip(unsigned int ip, unsigned char *mac);
int arp_getmac_byname(unsigned char *name, unsigned char *mac);
int arp_getmac_byip(unsigned int ip, unsigned char *mac);

int arp_recv_pkg(char *pkg);
int arp_flush();
int arp_build_req(unsigned int sip, unsigned char *smac, unsigned int dip, char *pkt_buf);
int arp_build_reply(unsigned int sip, unsigned char *smac, unsigned int dip, unsigned char *dmac, char *pkt_buf);




/* ROUTE API */




/* IPP_POOL API */
int ipp_pool_init(int n);
int ipp_pool_tini();
int ipp_pool_getport(unsigned int ip, int proto);
int ipp_pool_recport(unsigned int ip, unsigned short port, int proto);




/* SESSION API */
typedef struct _session_pool session_pool;
typedef struct _session session;
typedef int (*session_timeout_cbk)(session *s);

session_pool *session_pool_init();
int session_pool_tini(session_pool *sp);
session *session_get(session_pool *sp, void *pkg, int flow);
int session_close(session *s);
int session_set_type(session *s, unsigned int type);
unsigned int session_get_type(session *s);
int session_set_detail(session *s, void *detail);
void *session_get_detail(session *s);
int session_get_flow(session *s);
int session_set_timeout(session *s, unsigned long timeout);
int session_set_timeout_callback(session *s, void *cbk);



/* count api */
#define IPCOUNT_ADD_FLAG_SIP            1 << 0
#define IPCOUNT_ADD_FLAG_DIP            1 << 1
#define IPCOUNT_SESSION_TYPE_NEW        1
#define IPCOUNT_SESSION_TYPE_CLOSE      2
#define IPCOUNT_SESSION_TYPE_TIMEOUT    3
#define IPCOUNT_SESSION_TYPE_HTTP       4
#define IPCOUNT_SESSION_TYPE_UNKNOW     5
#define IPCOUNT_TOP_PPS_IN              1
#define IPCOUNT_TOP_PPS_OUT             2
#define IPCOUNT_TOP_BPS_IN              3
#define IPCOUNT_TOP_BPS_OUT             4
#define IPCOUNT_TOP_NEW_SESSION         5
#define IPCOUNT_TOP_NEW_HTTP            6
#define IPCOUNT_TOP_ICMP_BPS            7
#define IPCOUNT_TOP_HTTP_BPS            8
#define IPCOUNT_ATTACK_SYN_FLOOD        (1 << 0)
#define IPCOUNT_ATTACK_UDP_FLOOD        (1 << 1)
#define IPCOUNT_ATTACK_ICMP_FLOOD       (1 << 2)
typedef struct _ip_count_t ip_count_t;
#pragma pack(push, 8)
typedef struct _top_data
{
    unsigned int ip;
    unsigned long val;
}top_data;
typedef struct _ip_data
{
    unsigned int ip;
    unsigned long recv, send, inflow, outflow;
    unsigned long tcp_flow, udp_flow, icmp_flow, http_flow;
    unsigned long session_total, session_close, session_timeout, http_session;
}ip_data;
#pragma pack(pop)

int ipcount_lock(ip_count_t *ict);
int ipcount_unlock(ip_count_t *ict);
ip_count_t *ipcount_init();
int ipcount_tini(ip_count_t *ict);
int ipcount_add_ip(ip_count_t *ict, unsigned int ip);
int ipcount_del_ip(ip_count_t *ict, unsigned int ip);
int ipcount_add_pkg(ip_count_t *ict, void *pkg, unsigned int len, unsigned char add_ip_flag, unsigned int session_type);
int ipcount_add_session(ip_count_t *ict, unsigned int sip, unsigned int dip, unsigned int session_type);
int ipcount_get_ip(ip_count_t *ict, ip_data *id);
int ipcount_get_ip_total(ip_count_t *ict);
int ipcount_get_top_ip(ip_count_t *ict, int top_flag, top_data *td, unsigned int total);
int ipcount_get_all_ip(ip_count_t *ict, ip_data *id, unsigned int total);
int ipcount_set_attack_cbk(ip_count_t *ict, void *cbk);
typedef int (*ipcount_attack_cbk)(ip_count_t *ict, unsigned int ip, unsigned int attack_type, unsigned char attacking,
                                    unsigned long max_pps, unsigned long max_bps);




#if 0
/* SESSION API */
typedef struct _session session;

typedef int	(*pkg_handle)(session *s, void *pkg, int len);

typedef int (*session_timeout_cbk)(session *s, unsigned int key);
int session_init(int size);
int session_tini();
int session_close(session *s, unsigned int key);
int session_get(session **_s, unsigned int *key, void *pkg);

int session_set_timeout(session *s, unsigned int key, unsigned long timeout);
int session_set_default_timeout(unsigned long timeout);
int session_set_timeout_cbk(session *s, unsigned int key, void *cbk);
int session_set_detail(session *s, unsigned int key, void *detail);
int session_set_type(session *s, unsigned int key, unsigned char type);
int session_update_pkg_last(session *s, unsigned long time);
int session_get_stats(session *s, unsigned int key);
int session_get_recv_pkg(session *s, unsigned int key);
int session_get_send_pkg(session *s, unsigned int key);
char *session_get_smac(session *s, unsigned int key);
char *session_get_dmac(session *s, unsigned int key);
int session_get_sip(session *s, unsigned int key);
int session_get_dip(session *s, unsigned int key);
short session_get_sport(session *s, unsigned int key);
short session_get_dport(session *s, unsigned int key);
void *session_get_detail(session *s, unsigned int key);
unsigned char session_get_type(session *s, unsigned int key);
unsigned long session_get_global_time();
int session_print(session *s);


int session_rewrite_sip(session *s, unsigned int key, unsigned int sip);
int session_rewrite_dip(session *s, unsigned int key, unsigned int dip);
int session_rewrite_sport(session *s, unsigned int key, unsigned short sport);
int session_rewrite_dport(session *s, unsigned int key, unsigned short dport);
int session_rewrite_icmpid(session *s, unsigned int key, unsigned short id);
int session_rewrite_smac(session *s, unsigned int key, unsigned char *smac);
int session_rewrite_dmac(session *s, unsigned int key, unsigned char *dmac);
int session_rewrite_total(session *s, unsigned int key);
session *session_get_master(session *s);
int session_is_master(session *s);
int session_transmit(session *s, unsigned int key, void *pkg, unsigned char mod_pkg);
int get_session_total();
int get_session_rec();
#endif



#endif

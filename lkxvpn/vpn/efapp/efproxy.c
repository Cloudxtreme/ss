#include <stdio.h>
#include <stdlib.h>
#include <signal.h>	/* signal */
#include <unistd.h>
#include <sys/time.h>	/* timersub */
#include <syslog.h>
#include <fcntl.h>
#include <pthread.h>
#include <sched.h>

#include <efio.h>
#include <efnet.h>
#include <efext.h>
#include "conf.h"

#define MAX_SLOT	            10240

#define PING_TIMEOUT            1500000
#define PING_REQ_TIMEOUT        (3 * 1000000)
#define PING_REP_TIMEOUT        (3 * 1000000)
#define TRACE_REQ_TIMEOUT       (30 * 1000000)
#define TRACE_REP_TIMEOUT       (5 * 1000000)

#define MAX_ICMP_INFO           100000
#define EACH_ICMP_MAX_POINT     100
#define ICMP_PKG_MAX_SIZE       1500

#define MAX_TRACE_INFO          5000
#define EACH_TRACE_MAX_POINT    5000
#define TRACE_PKG_MAX_SIZE      1500
#define TRACE_MAX_TTL           30
#define TRACE_MODE_UDP			1
#define TRACE_MODE_PING			2

#define SESSION_TYPE_PING       1
#define SESSION_TYPE_TRACE      2

#define PROXY_FLAG              0xFFEEAABB


typedef struct _icmp_info
{
    unsigned char dir;
    unsigned char use;
    unsigned int request, reply;
    unsigned int cur, sum;
    unsigned int len;
    unsigned long last_reply;
    unsigned long delay;
    unsigned int seq[EACH_ICMP_MAX_POINT];
    unsigned long time1[EACH_ICMP_MAX_POINT];
    unsigned long time2[EACH_ICMP_MAX_POINT];
    unsigned long point[EACH_ICMP_MAX_POINT];
    unsigned char pkg[ICMP_PKG_MAX_SIZE];
}icmp_info;

typedef struct _trace_info
{
    unsigned char dir;
    unsigned char use;
	unsigned char mode;
	short vlan;
	unsigned char req;
    unsigned int source, dest;
	unsigned int sum;
	unsigned int len;
	unsigned int begin;
	unsigned char finttl;
    unsigned char ttl[EACH_TRACE_MAX_POINT];
	unsigned long hdr[EACH_TRACE_MAX_POINT];
    unsigned long point[EACH_TRACE_MAX_POINT];
	unsigned int routers[TRACE_MAX_TTL];
	unsigned long start[TRACE_MAX_TTL];
    unsigned long delay[TRACE_MAX_TTL];
	unsigned char pkg[TRACE_PKG_MAX_SIZE];
}trace_info;

typedef struct _fd_cont
{
    int fd, mi, mj, si, sj;
    unsigned int ip, gate;
    unsigned char smac[6];
    unsigned char gatemac[7];
    ef_slot m_slot[MAX_SLOT];
    ef_slot s_slot[MAX_SLOT];
    unsigned char m_chk[MAX_SLOT];
    unsigned char s_chk[MAX_SLOT];
}fd_cont;


static unsigned char dev1[32] = {0}, dev2[32] = {0};
static fd_cont ft_r1 = {0}, ft_r2 = {0}, ft_w1 = {0}, ft_w2 = {0};
static icmp_info ii[MAX_ICMP_INFO] = {0};
static trace_info ti[MAX_TRACE_INFO] = {0};
static icmp_info *pii[MAX_ICMP_INFO] = {0};
static trace_info *pti[MAX_TRACE_INFO] = {0};
unsigned int pii_cur = 0, pii_rec = 0, pti_cur = 0, pti_rec = 0;
static volatile int proxy_run = 0;
static volatile unsigned long proxy_time = 0;
static unsigned long proxy_speedup;

static int proxy_read(void *arg);
static int process();
static int proxy_control();
static int proxy_send(void *arg);
static int proxy_timeout(session *s);


static int decode_pkg(unsigned char *pkg, int len)
{
	unsigned char dmac[32];
	unsigned char smac[32];
	int vlan = 0;
	unsigned char sip[32];
	unsigned char dip[32];

	mac_2_str(GET_DMAC(pkg), dmac);
	mac_2_str(GET_SMAC(pkg), smac);
	if(IF_VLAN(pkg))
		vlan = VLAN(pkg);
	if(IF_IP(pkg))
	{
		ip_2_str(GET_IP_SIP(pkg), sip);
		ip_2_str(GET_IP_DIP(pkg), dip);
	}
	else if(IF_ARP(pkg))
	{
		ip_2_str(GET_ARP_SIP(pkg), sip);
		ip_2_str(GET_ARP_DIP(pkg), dip);
	}
	fprintf(stderr, "%s --> %s (%d) | %s --> %s\n", smac, dmac, vlan, sip, dip);
	if(IF_ICMP(pkg))
	{
        //struct icmphdr *ich = P_ICMPP(pkg);
        //fprintf(stderr, "icmp pkg : [id:%u] [seq:%u]\n", ntohs(ich->un.echo.id), ntohs(ich->un.echo.sequence));
	}
}

static int print_pkg(unsigned char *pkg, int len, unsigned char *title)
{
	int j;
	if(title)
		fprintf(stderr, "%s\n", title);
	for(j = 0; j < len; j++)
	{
		fprintf(stderr, "0x%x ", pkg[j]);
		if(j && (j % 16 == 0))
			fprintf(stderr, "\n");
	}
	fprintf(stderr, "\n\n------------------------------------\n\n");
}

static int proxy_timer()
{
    struct timeval now;
	while(proxy_run)
	{
		gettimeofday(&now, NULL);
		proxy_time = now.tv_sec * 1000000 + now.tv_usec;
		usleep(0);
	}
}

static int ft_control()
{
    int i = 0, j = 1;
    while(proxy_run)
    {
        #if 0
        //fprintf(stderr, "[%d %d %d %d] [%d %d %d %d] [%d %d %d %d] [%d %d %d %d]\n",
        //                ft_r1.mi, ft_r1.mj, ft_r1.si, ft_r1.sj,
        //                ft_r2.mi, ft_r2.mj, ft_r2.si, ft_r2.sj,
        //                ft_w1.mi, ft_w1.mj, ft_w1.si, ft_w1.sj,
        //                ft_w2.mi, ft_w2.mj, ft_w2.si, ft_w2.sj);

        if(j % 10 == 0)
        {
            for(i = 0; i < MAX_ICMP_INFO; i++)
            {
                icmp_info *detail = &ii[i];
                if(detail->use)
                    fprintf(stderr, "{dir:%u use:%u req:%u rep:%u cur:%u sum:%u lly:%lu delay:%lu seq_cur:%u seq_sum:%u point_cur:%lu point_sum:%lu}\n",
                                    detail->dir, detail->use, detail->request, detail->reply,
                                    detail->cur, detail->sum, detail->last_reply, detail->delay,
                                    detail->seq[detail->cur], detail->seq[detail->sum],
                                    detail->point[detail->cur], detail->point[detail->sum]);
            }
            for(i = 0; i < MAX_TRACE_INFO; i++)
            {
                int k;
                trace_info *detail = &ti[i];
                if(detail->use)
                {
                    unsigned char src[32] = {0};
                    unsigned char dst[32] = {0};
                    ip_2_str(detail->source, src);
                    ip_2_str(detail->dest, dst);
                    fprintf(stderr, "{dir:%u use:%u mode:%u req:%u sum:%u finttl:%u %s %s}\n",
                                    detail->dir, detail->use, detail->mode, detail->req, detail->sum, detail->finttl, src, dst);
                    fprintf(stderr, "{");
                    for(k = 0; k < TRACE_MAX_TTL; k++)
                    {
                        unsigned char ip[32] = {0};
                        ip_2_str(detail->routers[k], ip);
                        fprintf(stderr, "%s:%u,", ip, detail->delay[k]);
                    }
                    fprintf(stderr, "}\n");
                    fprintf(stderr, "{");
                    for(k = 0; k < detail->sum; k++)
                    {
                        fprintf(stderr, "%u ", detail->ttl[k]);
                    }
                    fprintf(stderr, "}\n");
                }
            }
            j = 1;
        }
        j++;
        #endif
        sleep(1);
    }
}

static int proxy_read(void *arg)
{
    fd_cont *ft = (fd_cont *)arg;
    //put pkg to read_slot
	int ret;

    //fprintf(stderr, "[read]...\n");
	while(proxy_run)
	{
        int ret;
		int i = ft->mi;
		int j = ft->mj;
		int max_read = (i >= j) ? (MAX_SLOT - i + j - 1) : (j - i - 1);

        if(i + max_read > MAX_SLOT)
            max_read = MAX_SLOT - i;
		ret = efio_flush(ft->fd, EF_FLUSH_READ, 2);
		if(max_read)
		{
			if(ret & EF_FLUSH_READ)
			{
                int reads = efio_read(ft->fd, &(ft->m_slot[i]), max_read);
				ft->mi = (ft->mi + reads) % MAX_SLOT;
            }
		}
		usleep(0);
        //usleep(1000000);
	}
}

static int process()
{
	void *pkg;
	unsigned short len;
	session_pool *pool;
	session *s;
	void *session_detail;
	unsigned char type;

    //fprintf(stderr, "[process]...\n");
    if(!(pool = session_pool_init()))
        return 0;
	while(proxy_run)
	{
		int mi1 = ft_r1.mi;
		int mj1 = ft_r1.mj;
		int mi2 = ft_r2.mi;
		int mj2 = ft_r2.mj;
		int si1 = ft_w1.si;
		int sj1 = ft_w1.sj;
		int si2 = ft_w2.si;
		int sj2 = ft_w2.sj;
		int process_read1 = (mj1 >= mi1) ? (MAX_SLOT - mj1 + mi1 - 1) : (mi1 - mj1 - 1);
		int process_read2 = (mj2 >= mi2) ? (MAX_SLOT - mj2 + mi2 - 1) : (mi2 - mj2 - 1);
		int canbe_send1 = (si1 >= sj1) ? (MAX_SLOT - si1 + sj1 - 1) : (sj1 - si1 - 1);
		int canbe_send2 = (si2 >= sj2) ? (MAX_SLOT - si2 + sj2 - 1) : (sj2 - si2 - 1);


        if(!process_read1 && !process_read2)
        {
            usleep(0);
            continue;
        }
		while(process_read1 || process_read2)
		{
            fd_cont *ftr = NULL;
            fd_cont *ftw = NULL;
            int *canbe_send = NULL;
            int forward = 1;
            if(process_read1)
            {
                ftr = &ft_r1;
                ftw = &ft_w2;
                canbe_send = &canbe_send2;
            }
            else
            {
                ftr = &ft_r2;
                ftw = &ft_w1;
                canbe_send = &canbe_send1;
            }
            ftr->mj = (ftr->mj + 1 == MAX_SLOT) ? 0 : (ftr->mj + 1);
            pkg = ftr->m_slot[ftr->mj].buf;
            len = ftr->m_slot[ftr->mj].len;
        	*(unsigned char *)(pkg + len) = 0;

            if(IF_IP(pkg))
            {
                struct iphdr *pkg_iph = P_IPP(pkg);
                if(!pkg_iph->ttl)
                    goto next_process;
                s = session_get(pool, pkg);
                if(!s)
                {
                    goto next_process;
                }
                session_detail = session_get_detail(s);
                if((pkg_iph->protocol == PKT_TYPE_ICMP) && (pkg_iph->ttl > 30))
                {
                    struct icmphdr *pkg_ich = P_ICMPP(pkg);
                    unsigned int flag = *(unsigned int *)((char *)pkg_ich + sizeof(struct icmphdr));
                    if((flag != PROXY_FLAG) && (pkg_ich->type == 8) && (pkg_ich->code == 0))
                    {
                        if(canbe_send && *canbe_send)
                        {
                            memcpy(&ftw->s_slot[ftw->si], &ftr->m_slot[ftr->mj], sizeof(ef_slot));
                            ftw->s_slot[ftw->si].flag = 0;
                            ftw->s_slot[ftw->si].in = 0;
                            ftw->s_slot[ftw->si].out = ftw->fd;
                            ftw->s_slot[ftw->si].time = 0;
                            *(unsigned int *)((char *)P_ICMPP(ftw->s_slot[ftw->si].buf) + sizeof(struct icmphdr)) = PROXY_FLAG;
                            *(unsigned long *)((char *)P_ICMPP(ftw->s_slot[ftw->si].buf) + sizeof(struct icmphdr) + sizeof(int)) = proxy_time;
                            ftw->s_chk[ftw->si] = 1;
                            ftw->si = (ftw->si + 1 == MAX_SLOT) ? 0 : (ftw->si + 1);
                            *canbe_send = *canbe_send - 1;
                        }
                    }
                }
                if(!session_detail)
                {
                    if(pkg_iph->ttl <= 30)
                    {
                        int j = pti_cur;
                        if((pkg_iph->protocol != PKT_TYPE_UDP) && (pkg_iph->protocol != PKT_TYPE_ICMP))
                            goto next_process;
                        if(!pti[j]->use)
                        {
                            //trace_info *detail = (trace_info *)&ti[j];
                            trace_info *detail = (trace_info *)pti[j];
                            memset(detail, 0, sizeof(trace_info));
                            detail->use = 1;
                            detail->dir = 1;
							if(!process_read1)
								detail->dir = 2;
							if(IF_UDP(pkg))
							{
								detail->mode = TRACE_MODE_UDP;
								detail->begin = 33434;
							}
							else
							{
								detail->mode = TRACE_MODE_PING;
								detail->begin = 1;
							}
							if(IF_VLAN(pkg))
                                detail->vlan = VLAN(pkg);
                            else
                                detail->vlan = -1;
							detail->source = pkg_iph->saddr;
							detail->dest = pkg_iph->daddr;
							detail->len = len;
							memcpy(detail->pkg, pkg, len);
                            session_set_detail(s, detail);
                            session_set_type(s, SESSION_TYPE_TRACE);
                            session_set_timeout(s, TRACE_REQ_TIMEOUT);
                            session_set_timeout_callback(s, (void *)proxy_timeout);
                            pti_cur = (pti_cur + 1 == MAX_TRACE_INFO) ? 0 : (pti_cur + 1);
                        }
                    }
                    else if(pkg_iph->protocol == PKT_TYPE_ICMP)
                    {
                        struct icmphdr *pkg_ich = P_ICMPP(pkg);
                        unsigned int flag = *(unsigned int *)((char *)pkg_ich + sizeof(struct icmphdr));
                        if((flag != PROXY_FLAG) && (pkg_ich->type == 8) && (pkg_ich->code == 0))
                        {
                            int j = pii_cur;
                            if(!pii[j]->use)
                            {
                                //icmp_info *detail = (icmp_info *)&ii[j];
                                icmp_info *detail = (icmp_info *)pii[j];
                                memset(detail, 0, sizeof(icmp_info));
                                detail->use = 1;
                                detail->len = len;
                                detail->dir = 1;

                                memcpy(detail->pkg, pkg, len);
                                unsigned int tmp = GET_IP_SIP(detail->pkg);
                                GET_IP_SIP(detail->pkg) = GET_IP_DIP(detail->pkg);
                                GET_IP_DIP(detail->pkg) = tmp;
                                P_ICMPP(detail->pkg)->type = 0;
                                P_ICMPP(detail->pkg)->code = 0;

                                detail->request = GET_IP_SIP(pkg);
                                detail->reply = GET_IP_DIP(pkg);
                                if(!process_read1)
                                    detail->dir = 2;

                                session_set_detail(s, detail);
                                session_set_type(s, SESSION_TYPE_PING);
                                session_set_timeout(s, PING_REQ_TIMEOUT);
                                session_set_timeout_callback(s, (void *)proxy_timeout);
                                pii_cur = (pii_cur + 1 == MAX_ICMP_INFO) ? 0 : (pii_cur + 1);
                            }
                        }
                    }
                }
                type = session_get_type(s);
                session_detail = session_get_detail(s);
                if(!session_detail)
                    goto next_process;
                if(type == SESSION_TYPE_PING)
                {
                    struct icmphdr *pkg_ich = P_ICMPP(pkg);
					icmp_info *detail = (icmp_info *)session_detail;
					forward = 0;
                    if(pkg_ich->type == 8)
                    {
                        if(!detail->point[detail->sum])
                        {
                            detail->seq[detail->sum] = pkg_ich->un.echo.sequence;
                            detail->time1[detail->sum] = *(unsigned long *)((char *)pkg_ich + sizeof(struct icmphdr));
                            detail->time2[detail->sum] = *(unsigned long *)((char *)pkg_ich + sizeof(struct icmphdr) + sizeof(long));
                            detail->point[detail->sum] = proxy_time;
                            detail->sum = (detail->sum + 1 == EACH_ICMP_MAX_POINT) ? 0 : (detail->sum + 1);
                        }
                    }
                    else
                    {
                        detail->last_reply = proxy_time;
                        detail->delay = proxy_time - *(unsigned long *)((char *)pkg_ich + sizeof(struct icmphdr) + sizeof(int));
                    }
                }
                else if(type == SESSION_TYPE_TRACE)
                {
                	trace_info *detail = (trace_info *)session_detail;
					forward = 0;
					if((pkg_iph->ttl <= 30) && (detail->source == pkg_iph->saddr))
					{
                        if(ntohs(GET_IP_DPORT(pkg)) == 33434 && detail->req >= TRACE_MAX_TTL)
                            detail->req = 0;
						if(!detail->point[detail->sum] && pkg_iph->ttl)
						{
							unsigned char ttl = detail->ttl[detail->sum] = pkg_iph->ttl;
							detail->hdr[detail->sum] = *(unsigned long *)((char *)pkg_iph + sizeof(struct iphdr));
							detail->point[detail->sum] = proxy_time;
							if(!detail->delay[ttl - 1])
								detail->delay[ttl - 1] = TRACE_REP_TIMEOUT;
							detail->sum = (detail->sum + 1 == EACH_TRACE_MAX_POINT) ? 0 : (detail->sum + 1);
						}
						else
						{
                            //fprintf(stderr, "detail no more point!\n");
						}
					}
					else if((pkg_iph->protocol == PKT_TYPE_ICMP) && (detail->source == pkg_iph->daddr))
                	{
                		struct icmphdr *pkg_ich = P_ICMPP(pkg);
                		if((pkg_ich->type == 0) && (pkg_ich->code == 0))
                		{
                            unsigned char ttl = ntohs(pkg_ich->un.echo.sequence) - detail->begin + 1;
                            {
                                detail->routers[ttl - 1] = pkg_iph->saddr;
                                detail->delay[ttl - 1] = proxy_time - detail->start[ttl - 1];
                            }
                		}
                		else if( (pkg_ich->type == 0x0b && pkg_ich->code == 0)
                                || (pkg_ich->type == 0x3 && pkg_ich->code == 0x3)
                                || (pkg_ich->type == 0x3 && pkg_ich->code == 0xa) )
                		{
                            struct iphdr *iph = (struct iphdr *)((char *)pkg_iph + sizeof(struct iphdr) + sizeof(struct icmphdr));
                            if(detail->mode == TRACE_MODE_UDP)
                            {
                                if(iph->protocol == PKT_TYPE_UDP)
                                {
                                    struct udphdr *uph = (struct udphdr *)((char *)iph + sizeof(struct iphdr));
                                    unsigned char ttl = ntohs(uph->dest) - detail->begin + 1;
                                    detail->routers[ttl - 1] = pkg_iph->saddr;
                                    detail->delay[ttl - 1] = proxy_time - detail->start[ttl - 1];
                                }
                            }
                            else
                            {
                                if(iph->protocol == PKT_TYPE_ICMP)
                                {
                                    struct icmphdr *ich = (struct icmphdr *)((char *)iph + sizeof(struct iphdr));
                                    unsigned char ttl = ntohs(ich->un.echo.sequence) - detail->begin + 1;
                                    detail->routers[ttl - 1] = pkg_iph->saddr;
                                    detail->delay[ttl - 1] = proxy_time - detail->start[ttl - 1];
                                }
                            }
                		}
                	}
                }
            }
        	else if(IF_ARP(pkg))
        	{
                if(IF_ARP_REQ(pkg))
                {
                    unsigned char *mac = GET_ARP_SMAC(pkg);
                    unsigned int sip = GET_ARP_SIP(pkg);
                    unsigned int dip = GET_ARP_DIP(pkg);
                    unsigned char str[32] = {0};

                    if(dip == ft_w1.ip)
                    {
                        forward = 0;
                        ef_slot slot = {0};
                        slot.len = arp_build_reply(dip, ft_w1.smac, sip, mac, slot.buf);
                        slot.out = ft_w1.fd;
                        if(canbe_send1)
                        {
                            memcpy(&(ft_w1.s_slot[ft_w1.si]), &slot, sizeof(ef_slot));
                            ft_w1.si = (ft_w1.si + 1 == MAX_SLOT) ? 0 : (ft_w1.si + 1);
                            canbe_send1--;
                        }
                        else
                        {
                            //fprintf(stderr, "can not send!\n");
                        }
                    }
                    if(dip == ft_w2.ip)
                    {
                        forward = 0;
                        ef_slot slot = {0};
                        slot.len = arp_build_reply(dip, ft_w2.smac, sip, mac, slot.buf);
                        slot.out = ft_w2.fd;
                        if(canbe_send2)
                        {
                            memcpy(&(ft_w2.s_slot[ft_w2.si]), &slot, sizeof(ef_slot));
                            ft_w2.si = (ft_w2.si + 1 == MAX_SLOT) ? 0 : (ft_w2.si + 1);
                            canbe_send2--;
                        }
                    }
                }
                else if(IF_ARP_REPLY(pkg))
                {
                    unsigned char *mac = GET_ARP_SMAC(pkg);
                    unsigned int ip = GET_ARP_SIP(pkg);
                    unsigned char str[32] = {0};

                    ip_2_str(ip, str);
                    if(ip == ft_w1.gate)
                    {
                        forward = 0;
                        memcpy(&ft_w1.gatemac[1], mac, 6);
                        ft_w1.gatemac[0] = 1;
                    }
                    if(ip == ft_w2.gate)
                    {
                        forward = 0;
                        memcpy(&ft_w2.gatemac[1], mac, 6);
                        ft_w2.gatemac[0] = 1;
                    }
                }
        	}
        next_process:
            if(forward && canbe_send && *canbe_send)
            {
                memcpy(&ftw->s_slot[ftw->si], &ftr->m_slot[ftr->mj], sizeof(ef_slot));
                ftw->s_slot[ftw->si].flag = 0;
                ftw->s_slot[ftw->si].in = 0;
                ftw->s_slot[ftw->si].out = ftw->fd;
                ftw->s_slot[ftw->si].time = 0;
                ftw->s_chk[ftw->si] = 1;
                ftw->si = (ftw->si + 1 == MAX_SLOT) ? 0 : (ftw->si + 1);
                *canbe_send = *canbe_send - 1;
            }
            if(process_read1)
                process_read1--;
            else
                process_read2--;
		}
	}
	session_pool_tini(pool);
}

static int proxy_control()
{
    static int iii = 0;
    struct iphdr proxy_iph =
    {
        .version = 0x4,
        .ihl = 0x5,
        .tos = 0,
        .tot_len = 0,
        .id = 0,
        .frag_off = 0x0040,
        .ttl = 0xff,
        .protocol = PKT_TYPE_ICMP,
        .check = 0,
        .saddr = 0,
        .daddr = 0,
    };
    int i, j;

    //fprintf(stderr, "[control]...\n");
    while(proxy_run)
    {
        unsigned long use_tot = 0;
		int mi1 = ft_w1.mi;
		int mj1 = ft_w1.mj;
		int mi2 = ft_w2.mi;
		int mj2 = ft_w2.mj;
		int canbe_send1 = (mi1 >= mj1) ? (MAX_SLOT - mi1 + mj1 - 1) : (mj1 - mi1 - 1);
		int canbe_send2 = (mi2 >= mj2) ? (MAX_SLOT - mi2 + mj2 - 1) : (mj2 - mi2 - 1);

        for(i = 0; i < MAX_ICMP_INFO; i++)
        {
            icmp_info *detail = &ii[i];
            if(detail->use)
            {
                use_tot++;
                if(detail->delay && (detail->last_reply + PING_REP_TIMEOUT < proxy_time))
                {
                    detail->delay = 0;
                }
                while(proxy_run && detail->use && detail->delay && detail->point[detail->cur] && (detail->point[detail->cur] + detail->delay < proxy_time + proxy_speedup))
                {
                    fd_cont *ft = NULL;
                    if(detail->dir == 1)
                    {
                        if(canbe_send1)
                        {
                            ft = &ft_w1;
                            canbe_send1--;
                        }
                    }
                    else if(detail->dir == 2)
                    {
                        if(canbe_send2)
                        {
                            ft = &ft_w2;
                            canbe_send2--;
                        }
                    }
                    if(ft)
                    {
                        struct icmphdr *ich;
                        memcpy(ft->m_slot[ft->mi].buf, detail->pkg, detail->len);
                        ft->m_slot[ft->mi].len = detail->len;
                        ft->m_slot[ft->mi].flag = 0;
                        ft->m_slot[ft->mi].in = 0;
                        ft->m_slot[ft->mi].out = ft->fd;
                        ft->m_slot[ft->mi].time = 0;
                        ich = P_ICMPP(ft->m_slot[ft->mi].buf);
                        ich->un.echo.sequence = detail->seq[detail->cur];
                        *(unsigned long *)((char *)ich + sizeof(struct icmphdr)) = detail->time1[detail->cur];
                        *(unsigned long *)((char *)ich + sizeof(struct icmphdr) + sizeof(long)) = detail->time2[detail->cur];
                        ft->m_chk[ft->mi] = 1;
                        ft->mi = (ft->mi + 1 == MAX_SLOT) ? 0 : (ft->mi + 1);
                        detail->point[detail->cur] = 0;
                        detail->cur = (detail->cur + 1 == EACH_ICMP_MAX_POINT) ? 0 : (detail->cur + 1);
                    }
                }
            }
        }

        #if 1
		for(i = 0; i < MAX_TRACE_INFO; i++)
		{
            int *canbe_send;
            fd_cont *ft;
			trace_info *detail = &ti[i];
			if(detail->use)
			{
                use_tot++;
				if(detail->req < TRACE_MAX_TTL)
				{
					if(detail->dir == 1)
					{
						canbe_send = &canbe_send2;
						ft = &ft_w2;
					}
					else
					{
						canbe_send = &canbe_send1;
						ft = &ft_w1;
					}
					while(*canbe_send && detail->use && (detail->req < TRACE_MAX_TTL))
					{
						memcpy(ft->m_slot[ft->mi].buf, detail->pkg, detail->len);
						ft->m_slot[ft->mi].len = detail->len;
                        ft->m_slot[ft->mi].flag = 0;
                        ft->m_slot[ft->mi].in = 0;
                        ft->m_slot[ft->mi].out = ft->fd;
                        ft->m_slot[ft->mi].time = 0;
                        P_IPP(ft->m_slot[ft->mi].buf)->ttl = detail->req + 1;
                        detail->start[detail->req] = proxy_time;
						if(detail->mode == TRACE_MODE_UDP)
						{
							struct udphdr *uph = P_UDPP(ft->m_slot[ft->mi].buf);
							uph->dest = htons(detail->begin + detail->req);
							//*(unsigned long *)((char *)uph + sizeof(struct udphdr)) = proxy_time;
						}
						else
						{
							struct icmphdr *ich = P_ICMPP(ft->m_slot[ft->mi].buf);
							ich->un.echo.sequence = htons(detail->begin + detail->req);
							//*(unsigned long *)((char *)ich + sizeof(struct icmphdr)) = proxy_time;
						}
						ft->m_chk[ft->mi] = 1;
                        ft->mi = (ft->mi + 1 == MAX_SLOT) ? 0 : (ft->mi + 1);
						*canbe_send = *canbe_send - 1;
						detail->req++;
					}
				}

				if(detail->dir == 1)
                {
                    canbe_send = &canbe_send1;
                    ft = &ft_w1;
                }
                else
                {
                    canbe_send = &canbe_send2;
                    ft = &ft_w2;
                }
                if(!canbe_send || !(*canbe_send))
                    continue;

				for(j = 0; j < EACH_TRACE_MAX_POINT; j++)
				{
					unsigned char ttl = detail->ttl[j];
					if(proxy_run && detail->use && detail->delay[ttl - 1] && detail->point[j]
                        && (detail->point[j] + detail->delay[ttl - 1] < proxy_time + proxy_speedup))
					{
                        struct iphdr *iph;
                        struct icmphdr *ich;

						if(!detail->routers[ttl - 1] && (detail->delay[ttl - 1] == TRACE_REP_TIMEOUT))
                            goto next_point;
                        //if((ttl != detail->finttl) && (detail->routers[ttl - 1] == detail->routers[detail->finttl - 1]))
                            //goto next_point;
                        //if(detail->routers[ttl - 1] == detail->dest)
                            //if(!detail->finttl) detail->finttl = ttl;
						if(detail->vlan >= 0)
						{
                            ETHTYPE(ft->m_slot[ft->mi].buf) = PKT_TYPE_VL;
                            VLAN(ft->m_slot[ft->mi].buf) = detail->vlan;
                            VTHTYPE(ft->m_slot[ft->mi].buf) = PKT_TYPE_IP;
						}
						else
                            ETHTYPE(ft->m_slot[ft->mi].buf) = PKT_TYPE_IP;
						iph = P_IPP(ft->m_slot[ft->mi].buf);
						memcpy(iph, &proxy_iph, sizeof(struct iphdr));
						iph->saddr = detail->routers[ttl - 1];
						iph->daddr = detail->source;
						ich = (struct icmphdr *)((char *)iph + sizeof(struct iphdr));
						//if(ttl != detail->finttl)
						if(detail->routers[ttl - 1] != detail->dest)
						{
                            ich->type = 0xb;
                            ich->code = 0;
                            ich->un.echo.id = 0;
                            ich->un.echo.sequence = 0;
						}
						else
						{
                            if(detail->mode == TRACE_MODE_PING)
                            {
                                *(unsigned long *)ich = detail->hdr[j];
                                ich->type = 0x0;
                                ich->code = 0x0;
                            }
                            else
                            {
                                ich->type = 0x3;
                                ich->code = 0x3;
                                ich->un.echo.id = 0;
                                ich->un.echo.sequence = 0;
                            }
						}
						if(ich->type == 0)
						{
                            if(detail->vlan >= 0)
                                iph->tot_len = detail->len - 18;
                            else
                                iph->tot_len = detail->len - 14;
                            memcpy((char *)ich + sizeof(struct icmphdr), (char *)P_ICMPP(detail->pkg) + sizeof(struct icmphdr), iph->tot_len - sizeof(struct iphdr) - sizeof(struct icmphdr));
						}
						else
						{
                            iph->tot_len = sizeof(struct iphdr) + sizeof(struct icmphdr) + detail->len;
                            memcpy((char *)ich + sizeof(struct icmphdr), P_IPP(detail->pkg), ntohs(P_IPP(detail->pkg)->tot_len));
                            *(unsigned long *)((char *)ich + sizeof(struct icmphdr) + sizeof(struct iphdr)) = detail->hdr[j];
						}
						if(detail->vlan >= 0)
                            ft->m_slot[ft->mi].len = iph->tot_len + 18;
                        else
                            ft->m_slot[ft->mi].len = iph->tot_len + 14;
						iph->tot_len = htons(iph->tot_len);
                        ft->m_slot[ft->mi].flag = 0;
                        ft->m_slot[ft->mi].in = 0;
                        ft->m_slot[ft->mi].out = ft->fd;
                        ft->m_slot[ft->mi].time = 0;
						ft->m_chk[ft->mi] = 1;
                        ft->mi = (ft->mi + 1 == MAX_SLOT) ? 0 : (ft->mi + 1);
						*canbe_send = *canbe_send - 1;
                    next_point:
						detail->point[j] = 0;
					}
				}
			}
		}
        #endif
        if(!use_tot)
            usleep(100);
    }
}

static int proxy_send(void *arg)
{
    fd_cont *ft = (fd_cont *)arg;
    unsigned long arp_time = 0;

    //fprintf(stderr, "[send]...\n");
	while(proxy_run)
	{
        int ret;
        int i1 = ft->mi;
		int j1 = ft->mj;
		int i2 = ft->si;
		int j2 = ft->sj;
		int max_send1 = (j1 >= i1) ? (MAX_SLOT - j1 + i1 - 1) : (i1 - j1 - 1);
		int max_send2 = (j2 >= i2) ? (MAX_SLOT - j2 + i2 - 1) : (i2 - j2 - 1);

        if(j1 + max_send1 > MAX_SLOT)
            max_send1 = MAX_SLOT - j1;
        if(j2 + max_send2 > MAX_SLOT)
            max_send2 = MAX_SLOT - j2;
		ret = efio_flush(ft->fd, EF_FLUSH_SEND, 2);
		if((!arp_time) && (ret & EF_FLUSH_SEND))
		{
            ef_slot slot;
            slot.len = arp_build_req(ft->ip, ft->smac, ft->gate, slot.buf);
            slot.flag = 0;
            slot.in = 0;
            slot.out = ft->fd;
            slot.pbuf = NULL;
            slot.plen = 0;
            slot.time = 0;
            efio_send(ft->fd, &slot, 1);
            arp_time = proxy_time;
		}
		if((max_send1 || max_send2) && (ft->gatemac[0]) && (ret & EF_FLUSH_SEND))
		{
            int k;
            for(k = (j1 + 1) % MAX_SLOT; k != i1; k = (k + 1 == MAX_SLOT) ? 0 : (k + 1))
			{
                if(ft->m_chk[k])
                {
                    struct iphdr *iph = P_IPP(ft->m_slot[k].buf);
					if(iph->protocol == PKT_TYPE_UDP)
					{
                        pkg_checksum(ft->m_slot[k].buf);
					}
					else
					{
                        struct icmphdr *ich = P_ICMPP(ft->m_slot[k].buf);
                        iph->check = 0;
                        ich->checksum = 0;
                        iph->check = checksum(iph, sizeof(struct iphdr));
                        ich->checksum = checksum(ich, (unsigned int)(ft->m_slot[k].len) - ((unsigned int)ich - (unsigned int)(ft->m_slot[k].buf)));
					}
                    memcpy(ft->m_slot[k].buf, &ft->gatemac[1], 6);
                    memcpy(ft->m_slot[k].buf + 6, ft->smac, 6);
                    ft->m_chk[k] = 0;
                }
			}
			for(k = (j2 + 1) % MAX_SLOT; k != i2; k = (k + 1 == MAX_SLOT) ? 0 : (k + 1))
			{
                if(ft->s_chk[k])
                {
                    struct iphdr *iph = P_IPP(ft->s_slot[k].buf);
                    struct icmphdr *ich = P_ICMPP(ft->s_slot[k].buf);
					if(iph->protocol == PKT_TYPE_UDP)
					{
                        pkg_checksum(ft->s_slot[k].buf);
					}
					else
					{
                        iph->check = 0;
                        ich->checksum = 0;
                        iph->check = checksum(iph, sizeof(struct iphdr));
                        ich->checksum = checksum(ich, (unsigned int)(ft->s_slot[k].len) - ((unsigned int)ich - (unsigned int)(ft->s_slot[k].buf)));
					}
                    memcpy(ft->s_slot[k].buf, &ft->gatemac[1], 6);
                    memcpy(ft->s_slot[k].buf + 6, ft->smac, 6);
                    ft->s_chk[k] = 0;
                }
			}
            ft->mj = (ft->mj + efio_send(ft->fd, &(ft->m_slot[(j1 + 1 == MAX_SLOT) ? 0 : (j1 + 1)]), max_send1)) % MAX_SLOT;
            if(max_send2)
            {
                int sends = efio_send(ft->fd, &(ft->s_slot[(j2 + 1 == MAX_SLOT) ? 0 : (j2 + 1)]), max_send2);
                ft->sj = (ft->sj + sends) % MAX_SLOT;
            }
		}
        else
            usleep(0);
        if(!ft->gatemac[0])
        {
            if(arp_time + 1000000 < proxy_time)
                arp_time = 0;
        }
        else
        {
            if(arp_time + 30000000 < proxy_time)
                arp_time = 0;
        }
	}
}

static int proxy_timeout(session *s)
{
	//process timeout session
	unsigned char session_type = session_get_type(s);
	if(session_type == SESSION_TYPE_PING)
	{
		icmp_info *detail = (icmp_info *)session_get_detail(s);
		if(detail)
		{
            pii[pii_rec] = detail;
            pii_rec = (pii_rec + 1 == MAX_ICMP_INFO) ? 0 : (pii_rec + 1);
			detail->use = 0;
        }
	}
	else if(session_type == SESSION_TYPE_TRACE)
	{
		trace_info *detail = (trace_info *)session_get_detail(s);
		if(detail)
		{
            pti[pti_rec] = detail;
            pti_rec = (pti_rec + 1 == MAX_TRACE_INFO) ? 0 : (pti_rec + 1);
			detail->use = 0;
        }
	}
	//return 1;
	return 0;
}

static int read_config(const char *conf_file)
{
    int ret = 0;
	CONF_DES *cd;
	char val[128] = {0};

    if(!conf_file)
        goto end;
	conf_init(&cd);
	if(!conf_read_file(cd, conf_file, " ", 0))
		goto end;
	//conf_print_all(cd);

	if(conf_ifkey(cd, "dev"))
	{
        unsigned int addr;
        conf_getval(cd, "dev", dev1, sizeof(dev1), 1);
        conf_getval(cd, "dev", val, sizeof(val), 2);
        if(!(addr = str_2_ip(val)))
            goto end;
        ft_w1.ip = addr;
        conf_getval(cd, "dev", val, sizeof(val), 3);
        if(!(addr = str_2_ip(val)))
            goto end;
        ft_w1.gate = addr;

        conf_getval(cd, "dev", dev2, sizeof(dev2), 4);
        conf_getval(cd, "dev", val, sizeof(val), 5);
        if(!(addr = str_2_ip(val)))
            goto end;
        ft_w2.ip = addr;
        conf_getval(cd, "dev", val, sizeof(val), 6);
        if(!(addr = str_2_ip(val)))
            goto end;
        ft_w2.gate = addr;
    }
    else
        goto end;
    if(conf_ifkey(cd, "speedup"))
    {
        conf_getval(cd, "speedup", val, sizeof(val), 1);
        proxy_speedup = atoi(val) * 1000;
    }
	ret = 1;
end:
	conf_uninit(&cd);
	return ret;
}

static void usage()
{
	fprintf(stderr, "efproxy -c config_file\n");
}

/* control-C handler */
static void
sigint_h(int sig)
{
	(void)sig;	/* UNUSED */

	proxy_run = 0;
}

int main(int argc, char *argv[])
{
    int i, ch;
    unsigned char *conf_file = NULL;

    pthread_t timer_t;
    pthread_t control_t;
    pthread_t read_t1;
    pthread_t read_t2;
    pthread_t process_t;
    pthread_t proxy_t;
    pthread_t send_t1;
    pthread_t send_t2;

	while ( (ch = getopt(argc, argv, "c:hT")) != -1)
	{
		switch(ch)
		{
			case 'c':
				conf_file = optarg;
				break;
			case 'h':
				usage();
				return 0;
				break;
			default:
				fprintf(stderr, "error option!\n");
				return -1;
		}
	}

    if(!conf_file)
    {
        usage();
        return 0;
    }
    if(daemon(1, 1) >= 0)
    {
        if(!read_config(conf_file))
            goto over;
        if(!strlen(dev1) || !strlen(dev2))
            goto over;
        for(i = 0; i < MAX_ICMP_INFO; i++)
            pii[i] = &ii[i];
        for(i = 0; i < MAX_TRACE_INFO; i++)
            pti[i] = &ti[i];
        fprintf(stderr, "Proxy initing, please wait 30s ...\n");
        ft_r1.fd = efio_init(dev1, EF_CAPTURE_NETMAP, EF_ENABLE_READ, 1);
        ft_r2.fd = efio_init(dev2, EF_CAPTURE_NETMAP, EF_ENABLE_READ, 1);
        ft_w1.fd = efio_init(dev1, EF_CAPTURE_NETMAP, EF_ENABLE_SEND, 1);
        ft_w2.fd = efio_init(dev2, EF_CAPTURE_NETMAP, EF_ENABLE_SEND, 1);
        if((ft_r1.fd == -1) || (ft_r2.fd == -1) || (ft_w1.fd == -1) || (ft_w2.fd == -1))
            goto over;
        ft_r1.mj = MAX_SLOT - 1;
        ft_r1.sj = MAX_SLOT - 1;
        ft_r2.mj = MAX_SLOT - 1;
        ft_r2.sj = MAX_SLOT - 1;
        ft_w1.mj = MAX_SLOT - 1;
        ft_w1.sj = MAX_SLOT - 1;
        ft_w2.mj = MAX_SLOT - 1;
        ft_w2.sj = MAX_SLOT - 1;
        get_dev_mac(dev1, ft_w1.smac);
        get_dev_mac(dev2, ft_w2.smac);
        proxy_run = 1;
        signal(SIGINT, sigint_h);
        signal(SIGTERM, sigint_h);
        signal(SIGKILL, sigint_h);

        pthread_create(&timer_t, NULL, proxy_timer, NULL);
        pthread_create(&control_t, NULL, ft_control, NULL);
        pthread_create(&read_t1, NULL, proxy_read, (void *)&ft_r1);
        pthread_create(&read_t2, NULL, proxy_read, (void *)&ft_r2);
        pthread_create(&send_t1, NULL, proxy_send, (void *)&ft_w1);
        pthread_create(&send_t2, NULL, proxy_send, (void *)&ft_w2);
        pthread_create(&process_t, NULL, process, NULL);
        pthread_create(&proxy_t, NULL, proxy_control, NULL);


        fprintf(stderr, "Proxy run success!\n");
        pthread_join(timer_t, NULL);
        pthread_join(control_t, NULL);
        pthread_join(read_t1, NULL);
        pthread_join(read_t2, NULL);
        //fprintf(stderr, "read end\n");
        pthread_join(send_t1, NULL);
        pthread_join(send_t2, NULL);
        //fprintf(stderr, "send end\n");
        pthread_join(process_t, NULL);
        //fprintf(stderr, "process end\n");
        pthread_join(proxy_t, NULL);
        //fprintf(stderr, "icmp end\n");
    }
    else
        fprintf(stderr, "cannot create run proxy!\n");
over:
    //fprintf(stderr, "efio tini fdr\n");
    if(ft_r1.fd)
        efio_tini(ft_r1.fd);
    if(ft_r2.fd)
        efio_tini(ft_r2.fd);
    //fprintf(stderr, "efio tini fdw\n");
    if(ft_w1.fd)
        efio_tini(ft_w1.fd);
    if(ft_w2.fd)
        efio_tini(ft_w2.fd);
    //fprintf(stderr, "wait session\n");
    return 0;
}

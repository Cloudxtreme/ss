#include <stdio.h>
#include <stdlib.h>
#include <signal.h>	/* signal */
#include <unistd.h>
#include <sys/time.h>	/* timersub */
#include <syslog.h>
#include <fcntl.h>
#include <pthread.h>
#include <sched.h>
#include <sys/shm.h>

#include <efio.h>
#include <efnet.h>
#include <efext.h>
#include <eftrack.h>
#include "conf.h"

#define MAX_TRACKER             2
#define TRACKER_TYPE_SESSION    1
#define TRACKER_TYPE_IP         2

typedef struct _tracker
{
    unsigned int type, run_at_cpu;
    unsigned int recv[MAX_SLOT];
    ef_slot send[MAX_SLOT];
    unsigned char send_chk[MAX_SLOT];
    http_info *ti, **pti, **ri, **ri_timeout;
    unsigned int rvi, rvj, sdi, sdj, rii, rij, rti, rtj, pti_cur, pti_rec;
    session_pool *pool;
    session *s;
    ip_count_t *ict;
    ip_data *ip_report;
    pthread_t process;
    pthread_t report;
    unsigned long long detail_use, detail_rec, detail_timeout, tcp_total, http_total;
}tracker;

//ip_count_t *ict = NULL;
//ip_data *ip_report;
tracker global_ter[MAX_TRACKER + 1] = {0}; //the last one for counting ip
track_opera *opera = NULL;
static send_target log_server = {0};
static send_target log_server_copy = {0};

static ef_slot r_slot[MAX_SLOT] = {0};
static unsigned int rsi = 0, rsj, rsj1, rsj2;
static char inbound[32] = {0}, outbound[32] = {0};
static int fdr = 0, fdw = 0;
static volatile int track_run = 0;
static unsigned long g_session_time = 0;
static int read_cpu = 0, send_cpu = 1;

static unsigned long long pkg_total = 0, flow_total = 0, send_pkg_total = 0, send_flow_total = 0;

extern unsigned long base_time;
extern int max_level;
extern unsigned int max_deep;


static int session_read();
static int session_process();
static int session_timeout();
static int session_report();
static int session_send();

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


static track_opera *get_opera()
{
    int *key = ftok("/etc/eftrack", (int)'a');
	int *id = shmget(key, sizeof(track_opera), IPC_CREAT | 0777);
	track_opera *to = (track_opera *)shmat(id, NULL, 0);

	if((int)to == -1)
	{
		shmctl(id, IPC_RMID, NULL);
		to = NULL;
	}
	to->id = id;
	return to;
}

static void release_opera(track_opera *to)
{
    if(to)
    {
        int *id = to->id;
        shmdt(to);
        shmctl(id, IPC_RMID, NULL);
    }
}


static int session_control()
{
    unsigned long long pkg_prev = 0, flow_prev = 0, send_pkg_prev = 0, send_flow_prev = 0, tcp_prev = 0, http_prev = 0;
    unsigned long long pps, bps, send_pps, send_bps, tcps, https;
    unsigned long pass_time = 0;
    unsigned long long detail_use, detail_rec, detail_timeout, tcp_total, http_total;
    unsigned int i;
    unsigned long max_pps = 0, max_bps = 0;
    while(track_run)
    {
        if(pass_time >= 1000000)
        {
            tcp_total = http_total = detail_use = detail_rec = detail_timeout = 0;
            for(i = 0; i < MAX_TRACKER; i++)
            {
                tracker *ter = &global_ter[i];
                tcp_total += ter->tcp_total;
                http_total += ter->http_total;
                detail_use += ter->detail_use;
                detail_rec += ter->detail_rec;
                detail_timeout += ter->detail_timeout;
            }
            pps = pkg_total - pkg_prev;
            bps = flow_total - flow_prev;
            send_pps = send_pkg_total - send_pkg_prev;
            send_bps = send_flow_total - send_flow_prev;
            tcps = tcp_total - tcp_prev;
            https = http_total - http_prev;

            if(pps > max_pps)
                max_pps = pps;
            if(bps > max_bps)
                max_bps = bps;

            fprintf(stderr, "%llu(pps max[%llu]) %llu(bps max[%llu]) %llu(tcp/s) %llu(http/s) %llu(tcp total)\n",
                pps, max_pps, bps * 8, max_bps * 8, tcps, https, detail_use - detail_rec);

            fprintf(stderr, "%llu %llu %llu %llu %llu %d %u\n", send_pps, send_bps * 8, detail_use, detail_timeout, detail_rec, max_level, max_deep);

            pkg_prev += pps;
            flow_prev += bps;
            send_pkg_prev += send_pps;
            send_flow_prev += send_bps;
            tcp_prev += tcps;
            http_prev += https;
            pass_time = 0;
        }

        usleep(100);
        pass_time += 100;
    }
}

static int session_read()
{
	//put pkg to read_slot
    if(read_cpu >= 0)
	{
        unsigned long mask = 1;
		mask = mask << read_cpu;
		sched_setaffinity(0, sizeof(mask), &mask);
	}
	while(track_run)
	{
        int ret;
        unsigned int i, j, max_read;

        rsj1 = rsj2 = MAX_SLOT;
        for(i = 0; i <= MAX_TRACKER; i++)
        {
            tracker *ter = &global_ter[i];
            j = ter->rvj;
            if(ter->recv[j] < rsi)
            {
                if(ter->recv[j] < rsj1)
                    rsj1 = ter->recv[j];
            }
            else
            {
                if(ter->recv[j] < rsj2)
                    rsj2 = ter->recv[j];
            }
        }
        if(rsj2 != MAX_SLOT)
            rsj = rsj2;
        else
            rsj = rsj1;
        max_read = (rsi >= rsj) ? (MAX_SLOT - rsi + rsj - 1) : (rsj - rsi - 1);
        if(rsi + max_read > MAX_SLOT)
            max_read = MAX_SLOT - rsi;


		if(max_read)
		{
            ret = efio_flush(fdr, EF_FLUSH_READ, 2);
            if(!(ret & EF_FLUSH_READ))
            {
                usleep(0);
                continue;
            }
            int reads = efio_read(fdr, &r_slot[rsi], max_read);
            for(j = 0; j < reads; j++)
            {
                void *pkg = r_slot[rsi + j].buf;
                unsigned int len = r_slot[rsi + j].len;

                pkg_total++;
                flow_total += len;
                if(IF_IP(pkg))
                {
                    unsigned int sip = GET_IP_SIP(pkg);
                    unsigned int dip = GET_IP_DIP(pkg);
                    unsigned int key = (sip ^ dip) % MAX_TRACKER;
                    tracker *ter = &global_ter[key];
                    tracker *cer = &global_ter[MAX_TRACKER];
                    unsigned int rv = (ter->rvi + 1 == MAX_SLOT) ? 0 : (ter->rvi + 1);
                    unsigned int rv2 = (cer->rvi + 1 == MAX_SLOT) ? 0 : (cer->rvi + 1);
                    if(rv != ter->rvj)
                    {
                        ter->recv[ter->rvi] = rsi + j;
                        ter->rvi = rv;
                    }
                    if(rv2 != cer->rvj)
                    {
                        cer->recv[cer->rvi] = rsi + j;
                        cer->rvi = rv2;
                    }
                    //ipcount_add_pkg(ict, pkg, len);
                }
            }
            rsi = (rsi + reads) % MAX_SLOT;
		}
		else
		{
			usleep(0);
        }
	}

}

#define STR_OVERFLOW(pkg, len, str) ((str < pkg) || (str > pkg + len))
static int session_process(void *arg)
{
	//get pkg from read_slot and process and put result to report info
	void *pkg;
	unsigned short len;
	unsigned long time;
	char http_host[100];
	tracker *ter = (tracker *)arg;

    if(ter->run_at_cpu > 0)
	{
        unsigned long mask = 1;
		mask = mask << ter->run_at_cpu;
		sched_setaffinity(0, sizeof(mask), &mask);
	}

	while(track_run)
	{
		int i1 = ter->rvi;//rsi;
		int j1 = ter->rvj;//rsj;
		int i2 = ter->rii;
		int j2 = ter->rij;
		int process_read = (j1 >= i1) ? (MAX_SLOT - j1 + i1 - 1) : (i1 - j1 - 1);
		int create_report = (i2 >= j2) ? (MAX_REPORT - i2 + j2 - 1) : (j2 - i2 - 1);


        if(ter->type == TRACKER_TYPE_IP)
        {
            //fprintf(stderr, "lock\n");
            if(opera->code)
            {
                ip_count_t *ict = global_ter[MAX_TRACKER].ict;
                opera->result = 0;
                switch(opera->code)
                {
                    case OPERA_ADD_IP:
                    {
                        unsigned int total = ipcount_get_ip_total(ict);
                        if(total + opera->arg < MAX_IP_NUM)
                        {
                            int i;
                            unsigned int ip;
                            for(i = 0; i < opera->arg; i++)
                            {
                                ip = opera->ip[i].ip;
                                opera->result += ipcount_add_ip(ict, ip);
                            }
                        }
                    }
                        break;
                    case OPERA_DEL_IP:
                    {
                        unsigned int ip = opera->ip[0].ip;
                        opera->result = ipcount_del_ip(ict, ip);
                    }
                        break;
                    case OPERA_GET_IP:
                        opera->result = ipcount_get_ip(ict, opera->ip);
                        break;
                    case OPERA_GET_ALL:
                        opera->result = ipcount_get_all_ip(ict, opera->ip, MAX_IP_NUM);
                        break;
                    default:;
                }
                opera->code = 0;
            }
        }
        if(!process_read || !create_report)
        {
            usleep(0);
            continue;
        }
		while(process_read && create_report)
		{
            ter->rvj = (ter->rvj + 1 == MAX_SLOT) ? 0 : (ter->rvj + 1);
			pkg = r_slot[ter->recv[ter->rvj]].buf;
        	len = r_slot[ter->recv[ter->rvj]].len;
        	time = r_slot[ter->recv[ter->rvj]].time;
        	*(unsigned char *)(pkg + len) = 0;
        	//goto next_process;

            if(ter->type == TRACKER_TYPE_IP)
            {
                ipcount_add_pkg(ter->ict, pkg, len);
                goto next_process;
            }
        	if(likely(IF_TCP(pkg)))
        	{
            	struct tcphdr *tph = P_TCPP(pkg);
            	http_info *detail = NULL;
         		unsigned int seq, ack;

         		ter->s = session_get(ter->pool, pkg);
            	if(!ter->s)
                	goto next_process;
            	seq = ntohi(tph->seq);
            	ack = ntohi(tph->ack_seq);
            	detail = (http_info *)session_get_detail(ter->s);
            	if(IF_SYN(pkg) && !IF_ACK(pkg))//if(IF_SYN(pkg))
            	{
                	if(!detail)//if(session_is_master(s) && !detail)
                	{
                        ter->tcp_total++;
                    	int j = ter->pti_cur;
                    	if(!ter->pti[j]->use)
                    	{
                        	memset(ter->pti[j], 0, sizeof(http_info));
                        	detail = ter->pti[j];
                        	detail->use = 1;
                        	detail->syn_time = time;
                        	detail->stats = TCP_STAT_CREAT;
                        	detail->sip = GET_IP_SIP(pkg);
                        	detail->dip = GET_IP_DIP(pkg);
                        	detail->sport = GET_IP_SPORT(pkg);
                        	detail->dport = GET_IP_DPORT(pkg);
                        	session_set_detail(ter->s, detail);
                        	session_set_timeout(ter->s, TCP_TIMEOUT);
                        	session_set_timeout_callback(ter->s, (void *)session_timeout);
                        	ter->detail_use++;
                        	ter->pti_cur = (ter->pti_cur + 1 == MAX_TCP_SESSION) ? 0 : (ter->pti_cur + 1);
                    	}
                    	else
                            goto next_process;
                	}
            	}
            syn_end:
            	if(IF_ACK(pkg) && !IF_SYN(pkg))
            	{
                	if(detail)
                	{
                    	char *http_info = ((char*)P_TCPP(pkg)+(P_TCPP(pkg)->doff<<2));
                    	unsigned int http_method;
                    	char *url_begin = NULL;
                    	char *url_end = NULL;
                    	char *host_begin = NULL;
                    	char *host_end = NULL;
                    	unsigned int url_len = 0, host_len = 0;

                        if(STR_OVERFLOW(pkg, len, http_info))
                            goto ack_end;
                        if(detail->stats == TCP_STAT_FIN)
                            goto next_process;
                    	if(detail->stats != TCP_STAT_CONN)
                    	{
                        	detail->first_ack_time = time;
                        	detail->stats = TCP_STAT_CONN;
                    	}
                    	detail->last_ack_time = time;

                    	http_method = *(unsigned int *)http_info;
                    	switch(http_method)
                    	{
                            case HTTP_METHOD_GET:
                            case HTTP_METHOD_POST:
                            case HTTP_METHOD_HEAD:
                            case HTTP_METHOD_PUT:
                                goto http_parse;
                            case HTTP_METHOD_DELETE1:
                                if(*(unsigned short *)(http_info + 4) == HTTP_METHOD_DELETE2)
                                    goto http_parse;
                            case HTTP_METHOD_TRACE1:
                                if(http_info[5] == HTTP_METHOD_TRACE2)
                                    goto http_parse;
                            default:;
                    	}
                    	if((*(unsigned long long *)http_info == HTTP_METHOD_OPTIONS) || (*(unsigned long long *)http_info == HTTP_METHOD_CONNECT))
                            goto http_parse;
                        goto ack_end;

                	http_parse:
                        detail->protocol = SESSION_PROTO_HTTP;
                    	url_begin = strchr(http_info, ' ') + 1;
                        if(STR_OVERFLOW(pkg, len, url_begin))
                            goto ack_end;
                    	url_end = strchr(url_begin, ' ');
                    	if(STR_OVERFLOW(pkg, len, url_end))
                            goto ack_end;
                    	if(url_end)
                    	{
                            url_len = url_end - http_info;
                            host_begin = strstr(url_end, "Host:");
                    	}
                    	else
                            url_len = len - ((unsigned int)http_info - (unsigned int)pkg);
                    	if(host_begin)
                    	{
                            if(STR_OVERFLOW(pkg, len, host_begin))
                                goto ack_end;
                        	host_begin += 6;
                        	host_end = strstr(host_begin, "\r\n");
                        	if(host_end && !STR_OVERFLOW(pkg, len, host_end))
                        	{
                                host_len = host_end - host_begin;
                                host_len = (host_len >= sizeof(http_host)) ? sizeof(http_host) : host_len;
                                memcpy(http_host, host_begin, host_len);
                            }
                    	}
                    	if(!host_len)
                            host_len = ip_2_str(GET_IP_DIP(pkg), http_host);


                    	if(detail->url_len + host_len + url_len + 2 < MAX_URL_LEN)
                    	{
                            detail->url[detail->url_len++] = '[';
                            memcpy(&detail->url[detail->url_len], http_host, host_len);
                            detail->url_len += host_len;
                            memcpy(&detail->url[detail->url_len], http_info, url_len);
                            detail->url_len += url_len;
                            detail->url[detail->url_len++] = ']';
                        }
                	}
            	}
            ack_end:
            	if(IF_FIN(pkg) || IF_RST(pkg))
            	{
                    if(!detail)
                        goto next_pkg;
                	if(detail->stats != TCP_STAT_CONN)
                	{
                    	detail->stats = TCP_STAT_ERROR;
                	}
                	else //if(session_is_master(s))
                	{
                    	detail->fin_time = time;
                    	detail->stats = TCP_STAT_FIN;
                	}
            	}
        	next_pkg:
                if(detail)
                {
                    if(detail->sip == GET_IP_SIP(pkg))//if(session_is_master(s))
                    {
                        if(seq < detail->min_seq || !detail->min_seq) detail->min_seq = seq;
                        if(seq > detail->max_seq) detail->max_seq = seq;
                        if(ack < detail->min_ack || !detail->min_ack) detail->min_ack = ack;
                        if(ack > detail->max_ack) detail->max_ack = ack;
                    }
                    if(detail->stats == TCP_STAT_FIN || detail->stats == TCP_STAT_ERROR)
                    {
                        ter->ri[ter->rii] = detail;
                        ter->rii = (ter->rii + 1 == MAX_REPORT) ? 0 : (ter->rii + 1);
                        session_close(ter->s);
                        create_report--;
                    }
                }
        	}
        next_process:
            process_read--;
		}
	}
}

static int session_timeout(session *s)
{
	//process timeout session
	http_info *detail = (http_info *)session_get_detail(s);
	if(detail)
	{
        unsigned int key = (detail->sip ^ detail->dip) % MAX_TRACKER;
        tracker *ter = &global_ter[key];
        ter->detail_timeout++;
        detail->stats = TCP_STAT_TIMEOUT;
        if((ter->rti + 1) % MAX_REPORT != ter->rtj)
        {
            ter->ri_timeout[ter->rti] = detail;
            ter->rti = (ter->rti + 1 == MAX_REPORT) ? 0 : (ter->rti + 1);
        }
	}
	return 0;
}

static int session_report(void *arg)
{
	//get report info and build send_slot
	tracker *ter = (tracker *)arg;
	char *mon_str[] = {"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"};
    unsigned char log_header[1024] = {0};
    unsigned char log_header_len = 0;
    unsigned long last_time = 0, ip_report_time = 0;
    struct iphdr *iph;
    struct udphdr *uph;

    if(ter->run_at_cpu > 0)
	{
        unsigned long mask = 1;
		mask = mask << ter->run_at_cpu;
		sched_setaffinity(0, sizeof(mask), &mask);
	}

	while(track_run)
	{
		//if(!log_server.complete)
		{
            //usleep(0);
            //continue;
		}
		g_session_time = base_time;
        if(g_session_time - last_time >= 1000000)
        {
            static struct tm *now = NULL;
            static time_t lt;
            lt =time(NULL);
            now=localtime(&lt);
            last_time = g_session_time;
            snprintf(log_header, sizeof(log_header), "<7>%s %02d %02d:%02d:%02d localhost kernel: \0",
                    mon_str[now->tm_mon], now->tm_mday, now->tm_hour, now->tm_min, now->tm_sec);
            log_header_len = strlen(log_header);
        }

        if(ter->type == TRACKER_TYPE_IP)
        {
            int i2 = ter->sdi;
            int j2 = ter->sdj;
            int max_report = (i2 >= j2) ? (MAX_SLOT - i2 + j2 - 1) : (j2 - i2 - 1);
            if((g_session_time - ip_report_time >= 1000000) && (max_report > 2))
            {
                int i = 0, j = 0;
                unsigned int ip_total = ipcount_get_ip_total(ter->ict);
                if(ip_total > MAX_IP_NUM)
                    ip_total = MAX_IP_NUM;
                //ipcount_lock(ter->ict);
                ipcount_get_all_ip(ter->ict, ter->ip_report, ip_total);
                //ipcount_unlock(ter->ict);
                while((max_report > 2) && (i < ip_total))
                {
                    int sdi_copy = (ter->sdi + 1 == MAX_SLOT) ? 0 : (ter->sdi + 1);
                    char *log_buf = ter->send[ter->sdi].buf;
                    char *log_buf_copy = ter->send[sdi_copy].buf;
                    int log_len = 0;

                    memcpy(&log_buf[log_len], log_server.hdr, log_server.hdr_len); log_len += log_server.hdr_len;
                    memcpy(&log_buf[log_len], log_header, log_header_len); log_len += log_header_len;
                    memcpy(&log_buf[log_len], "{\"ip_info\":[", 12); log_len += 12;
                    j = 0;
                    while((log_len < 1000) && (i < ip_total) && (j < 10))
                    {
                        if(j)
                            log_buf[log_len++] = ',';
                        log_buf[log_len++] = '{';

                        //"ip":"              0x22706922      0x223a
                        //"recv":"            0x223a227663657222
                        //"send":"            0x223a22646e657322
                        //"inflow":"          0x22776f6c666e6922  0x223a
                        //"outflow":"         0x776f6c6674756f22  0x3a22  0x22

                        //memcpy(&log_buf[log_len], "\"ip\":\"", 6); log_len += 6;
                        *(unsigned int *)&log_buf[log_len] = 0x22706922;
                        log_len += 4;
                        *(unsigned short *)&log_buf[log_len] = 0x223a;
                        log_len += 2;
                        log_len += ip_2_str(ter->ip_report[i].ip, &log_buf[log_len]);
                        log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                        //memcpy(&log_buf[log_len], "\"recv\":\"", 8); log_len += 8;
                        *(unsigned long long *)&log_buf[log_len] = 0x223a227663657222;
                        log_len += 8;
                        log_len += num_2_str(ter->ip_report[i].recv, &log_buf[log_len]);
                        log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                        //memcpy(&log_buf[log_len], "\"send\":\"", 8); log_len += 8;
                        *(unsigned long long *)&log_buf[log_len] = 0x223a22646e657322;
                        log_len += 8;
                        log_len += num_2_str(ter->ip_report[i].send, &log_buf[log_len]);
                        log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                        //memcpy(&log_buf[log_len], "\"inflow\":\"", 10); log_len += 10;
                        *(unsigned long long *)&log_buf[log_len] = 0x22776f6c666e6922;
                        log_len += 8;
                        *(unsigned short *)&log_buf[log_len] = 0x223a;
                        log_len += 2;
                        log_len += num_2_str(ter->ip_report[i].inflow, &log_buf[log_len]);
                        log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                        //memcpy(&log_buf[log_len], "\"outflow\":\"", 11); log_len += 11;
                        *(unsigned long long *)&log_buf[log_len] = 0x776f6c6674756f22;
                        log_len += 8;
                        *(unsigned short *)&log_buf[log_len] = 0x3a22;
                        log_len += 2;
                        log_buf[log_len++] = 0x22;
                        log_len += num_2_str(ter->ip_report[i].outflow, &log_buf[log_len]);
                        log_buf[log_len++] = '"';log_buf[log_len++] = '}';
                        i++;j++;
                    }
                    log_buf[log_len++] = ']';log_buf[log_len++] = '}';log_buf[log_len] = 0;
                    memcpy(log_buf_copy, log_buf, log_len);
                    memcpy(log_buf_copy, log_server_copy.hdr, log_server_copy.hdr_len);

                    iph = P_IPP(ter->send[ter->sdi].buf);
                    uph = P_UDPP(ter->send[ter->sdi].buf);
                    iph->tot_len = htons(log_len - ((unsigned char *)iph - ter->send[ter->sdi].buf));
                    uph->len = htons(log_len - ((unsigned char *)uph - ter->send[ter->sdi].buf));
                    //fprintf(stderr, "%s\n", &log_buf[log_server.hdr_len]);
                    ter->send_chk[ter->sdi] = 0;
                    ter->send[ter->sdi].len = log_len;

                    iph = P_IPP(ter->send[sdi_copy].buf);
                    uph = P_UDPP(ter->send[sdi_copy].buf);
                    iph->tot_len = htons(log_len - ((unsigned char *)iph - ter->send[sdi_copy].buf));
                    uph->len = htons(log_len - ((unsigned char *)uph - ter->send[sdi_copy].buf));
                    //fprintf(stderr, "%s\n", &log_buf[log_server.hdr_len]);
                    ter->send_chk[sdi_copy] = 0;
                    ter->send[sdi_copy].len = log_len;

                    ter->sdi = (sdi_copy + 1 == MAX_SLOT) ? 0 : (sdi_copy + 1);
                    max_report = max_report - 2;
                }

                ip_report_time = g_session_time;
            }
        }
        else
        {
            #if 1
            int i1 = ter->rii;
            int j1 = ter->rij;
            int i2 = ter->sdi;
            int j2 = ter->sdj;
            int t1 = ter->rti;
            int t2 = ter->rtj;
            int timeout = (t2 >= t1) ? (MAX_REPORT - t2 + t1 - 1) : (t1 - t2 - 1);
            int report1 = (j1 >= i1) ? (MAX_REPORT - j1 + i1 - 1) : (i1 - j1 - 1);
            int report2 = (i2 >= j2) ? (MAX_SLOT - i2 + j2 - 1) : (j2 - i2 - 1);
            int max_report = (report1 + timeout) <= report2 ? (report1 + timeout) : report2;
            int done = 0;
            if(!max_report)
            {
                usleep(0);
                continue;
            }
            while(max_report)
            {
                char *session_log;
                int log_len = 0;
                http_info *detail = NULL;

                if(done++ < timeout)
                {
                    ter->rtj = (ter->rtj + 1 == MAX_REPORT) ? 0 : (ter->rtj + 1);
                    detail = ter->ri_timeout[ter->rtj];
                }
                else
                {
                    ter->rij = (ter->rij + 1 == MAX_REPORT) ? 0 : (ter->rij + 1);
                    detail = ter->ri[ter->rij];
                }

                goto next_report;

                session_log = ter->send[ter->sdi].buf;
                memcpy(&session_log[log_len], log_server.hdr, log_server.hdr_len);
                log_len += log_server.hdr_len;
                memcpy(&session_log[log_len], log_header, log_header_len);
                log_len += log_header_len;
                memcpy(&session_log[log_len], "[session] ", 10);
                log_len += 10;
                log_len += ip_2_str(detail->sip, &session_log[log_len]);
                session_log[log_len++] = ':';
                log_len += num_2_str(detail->sport, &session_log[log_len]);
                session_log[log_len++] = ' ';
                log_len += ip_2_str(detail->dip, &session_log[log_len]);
                session_log[log_len++] = ':';
                log_len += num_2_str(detail->dport, &session_log[log_len]);
                session_log[log_len++] = ' ';

                switch(detail->stats)
                {
                    case TCP_STAT_FIN:
                        memcpy(&session_log[log_len], "normal ", 7);
                        log_len += 7;
                        break;
                    case TCP_STAT_ERROR:
                        memcpy(&session_log[log_len], "error ", 6);
                        log_len += 6;
                        break;
                    case TCP_STAT_TIMEOUT:
                        memcpy(&session_log[log_len], "timeout ", 8);
                        log_len += 8;
                        break;
                    default:;
                }

                detail->conn_time = (detail->syn_time && (detail->first_ack_time > detail->syn_time)) ? (detail->first_ack_time - detail->syn_time) : 0;
                detail->tran_time = detail->last_ack_time - detail->first_ack_time;
                detail->close_time = (detail->last_ack_time && (detail->fin_time > detail->last_ack_time)) ? (detail->fin_time - detail->last_ack_time) : 0;
                log_len += num_2_str(detail->conn_time/1000, &session_log[log_len]);
                session_log[log_len++] = ' ';
                log_len += num_2_str(detail->tran_time/1000, &session_log[log_len]);
                session_log[log_len++] = ' ';
                log_len += num_2_str(detail->close_time/1000, &session_log[log_len]);
                session_log[log_len++] = ' ';
                log_len += num_2_str(detail->max_seq - detail->min_seq, &session_log[log_len]);
                session_log[log_len++] = ' ';
                log_len += num_2_str(detail->max_ack - detail->min_ack, &session_log[log_len]);
                session_log[log_len++] = ' ';

                switch(detail->protocol)
                {
                    case SESSION_PROTO_HTTP:
                        ter->http_total++;
                        if(detail->url_len)
                        {
                            memcpy(&session_log[log_len], "http ", 5);
                            log_len += 5;
                            memcpy(&session_log[log_len], detail->url, detail->url_len);
                            log_len += detail->url_len;
                        }
                        break;
                    default:
                        memcpy(&session_log[log_len], "unknow\0", 7);
                        log_len += 7;
                }
                session_log[log_len] = 0;
                iph = P_IPP(ter->send[ter->sdi].buf);
                uph = P_UDPP(ter->send[ter->sdi].buf);
                iph->tot_len = htons(log_len - ((unsigned char *)iph - ter->send[ter->sdi].buf));
                uph->len = htons(log_len - ((unsigned char *)uph - ter->send[ter->sdi].buf));
                ter->send_chk[ter->sdi] = 0;
                ter->send[ter->sdi].len = log_len;
                ter->sdi = (ter->sdi + 1 == MAX_SLOT) ? 0 : (ter->sdi + 1);
            next_report:
                ter->detail_rec++;
                ter->pti[ter->pti_rec] = detail;
                ter->pti_rec = (ter->pti_rec + 1 == MAX_TCP_SESSION) ? 0 : (ter->pti_rec + 1);
                detail->use = 0;
                max_report--;
            }
            #endif
        }
	}
}

static int session_send()
{
	//send the send_slot
	FILE *fp = fopen("/tmp/test_pkg", "wb+");
	static ef_slot slot[1024] = {0};
	static unsigned int send_cur = 0;
    if(send_cpu >= 0)
	{
        unsigned long mask = 1;
		mask = mask << send_cpu;
		sched_setaffinity(0, sizeof(mask), &mask);
	}
	while(track_run)
	{
        int ret;
        ret = efio_flush(fdw, EF_FLUSH_READ | EF_FLUSH_SEND, 2);
        g_session_time = base_time;

        if(g_session_time > log_server.flush)
        {
            memcpy(slot[0].buf, log_server.arp_req, log_server.arp_len);
            slot[0].flag = 0;
            slot[0].out = fdw;
            slot[0].len = log_server.arp_len;
            efio_send(fdw, slot, 1);
            //fprintf(stderr, "ask gateway!\n");
            if(log_server.complete)
                log_server.flush = g_session_time + 30000000;
            else
                log_server.flush = g_session_time + 3000000;
        }
        if(g_session_time > log_server_copy.flush)
        {
            memcpy(slot[0].buf, log_server_copy.arp_req, log_server_copy.arp_len);
            slot[0].flag = 0;
            slot[0].out = fdw;
            slot[0].len = log_server_copy.arp_len;
            efio_send(fdw, slot, 1);
            //fprintf(stderr, "ask gateway!\n");
            if(log_server_copy.complete)
                log_server_copy.flush = g_session_time + 30000000;
            else
                log_server_copy.flush = g_session_time + 3000000;
        }
        if(ret & EF_FLUSH_READ)
        {
            int i;
            void *pkg;
            unsigned int len;
            int reads = efio_read(fdw, slot, 1024);
            for(i = 0; i < reads; i++)
            {
                pkg = slot[i].buf;
                len = slot[i].len;
                if(IF_ARP_REPLY(pkg))
                {
                    unsigned char *mac = GET_ARP_SMAC(pkg);
                    unsigned int sip = GET_ARP_SIP(pkg);
                    unsigned int dip = GET_ARP_DIP(pkg);
                    if((sip == log_server.gate) && (dip == log_server.sip))
                    {
                        fprintf(stderr, "gateway complete!\n");
                        log_server.complete = 1;
                        memcpy(log_server.dmac, mac, 6);
                        memcpy(log_server.hdr, mac, 6);
                    }
                    if((sip == log_server_copy.gate) && (dip == log_server_copy.sip))
                    {
                        fprintf(stderr, "copy gateway complete!\n");
                        log_server_copy.complete = 1;
                        memcpy(log_server_copy.dmac, mac, 6);
                        memcpy(log_server_copy.hdr, mac, 6);
                    }
                }
            }
        }
		if(ret & EF_FLUSH_SEND)
		{
			unsigned int i, j, k, l, send = 0, max = 0, aaa = 0;
			for(l = 0; (l <= MAX_TRACKER) && (send >= max); l++)
			{
                tracker *ter = &global_ter[send_cur];
                i = ter->sdi;
                j = ter->sdj;
                max = (j >= i) ? (MAX_SLOT - j + i - 1) : (i - j - 1);
                if(j + max > MAX_SLOT)
                    max = MAX_SLOT - j;
                for(k = (j + 1) % MAX_SLOT; k != i; k = (k + 1 == MAX_SLOT) ? 0 : (k + 1))
                {
                    if(!ter->send_chk[k])
                    {
                        pkg_checksum(ter->send[k].buf);
                        //decode_pkg(w_slot[k].buf, w_slot[k].len);
                        if(!aaa)
                        {
                            //fwrite(ter->send[k].buf, ter->send[k].len, 1, fp);
                            //print_pkg(ter->send[k].buf, ter->send[k].len, "Log ");
                            //decode_pkg(ter->send[k].buf, ter->send[k].len);
                        }
                        aaa++;
                        ter->send_chk[k] = 1;
                        send_flow_total += ter->send[k].len;
                    }
                }
                //wsj = (wsj + max_send) % MAX_SLOT;
                //wsj = (wsj + efio_send(fdw, &w_slot[(wsj + 1 == MAX_SLOT) ? 0 : (wsj + 1)], max_send)) % MAX_SLOT;
                send = efio_send(fdw, &ter->send[(ter->sdj + 1 == MAX_SLOT) ? 0 : (ter->sdj + 1)], max);
                //send = max;
                send_pkg_total += send;
                ter->sdj = (ter->sdj + send) % MAX_SLOT;
                if(send == max)
                    send_cur = (send_cur + 1 == MAX_TRACKER + 1) ? 0 : (send_cur + 1);
			}
		}
		else
			usleep(0);
	}
	fclose(fp);
}

static void usage()
{
	fprintf(stderr, "session_track -c config_file\n");
}

static int session_config(const char *conf_file)
{
	int ret = 0;
	int i = 0, j = 0, k = 0;
	CONF_DES *cd;
	char val[128];

    if(!conf_file)
        goto end;
	conf_init(&cd);
	if(!conf_read_file(cd, conf_file, " ", 0))
		goto end;
	//conf_print_all(cd);

	if(!conf_ifkey(cd, "inbound") || !conf_ifkey(cd, "outbound"))
        goto end;
    conf_getval(cd, "inbound", inbound, sizeof(inbound), 1);
    conf_getval(cd, "outbound", outbound, sizeof(outbound), 1);

	if(conf_ifkey(cd, "log"))
	{
        char type[32];
		char gate[32];
		char vlan[32];
		char sip[32];
		char dip[32];
		char sport[32];
		char dport[32];
		struct iphdr *iph;
		struct udphdr *uph;
		send_target *target = NULL;

        i = 1;
    build_log_server:
        if(i == 1)
            target = &log_server;
        else
            target = &log_server_copy;
        if(!conf_getval(cd, "log", gate, sizeof(gate), i++))
            goto end;
        if(!conf_getval(cd, "log", vlan, sizeof(vlan), i++))
            goto end;
        if(!conf_getval(cd, "log", sip, sizeof(sip), i++))
            goto end;
        if(!conf_getval(cd, "log", dip, sizeof(dip), i++))
            goto end;
        if(!conf_getval(cd, "log", sport, sizeof(sport), i++))
            goto end;
        if(!conf_getval(cd, "log", dport, sizeof(dport), i++))
            goto end;

        if((target->gate = str_2_ip(gate)) == 0)
            goto end;
        if(atoi(vlan) < -1 || atoi(vlan) > 0x7fff)
            goto end;
        target->vlan = atoi(vlan);
        if((target->sip = str_2_ip(sip)) == 0)
            goto end;
        if((target->dip = str_2_ip(dip)) == 0)
            goto end;
        if(atoi(sport) < 1 || atoi(sport) > 0xffff)
            goto end;
        if(atoi(dport) < 1 || atoi(dport) > 0xffff)
            goto end;
        target->sport = atoi(sport);
        target->dport = atoi(dport);

        get_dev_mac(outbound, target->smac);
        memcpy(target->hdr + 6, target->smac, 6);
        if(target->vlan >= 0)
        {
            ETHTYPE(target->hdr) = PKT_TYPE_VL;
            VLAN(target->hdr) = htons(target->vlan);
            VTHTYPE(target->hdr) = PKT_TYPE_IP;
            iph = (struct iphdr *)(target->hdr + 18);
            target->hdr_len = 46;
        }
        else
        {
            ETHTYPE(target->hdr) = PKT_TYPE_IP;
            iph = (struct iphdr *)(target->hdr + 14);
            target->hdr_len = 42;
        }
        iph->version = 0x4;
        iph->ihl = 0x5;
        iph->frag_off = 0x0040;
        iph->ttl = 0xff;
        iph->protocol = PKT_TYPE_UDP;
        iph->saddr = target->sip;
        iph->daddr = target->dip;
        uph = P_UDPP(target->hdr);
        uph->source = htons(target->sport);
        uph->dest = htons(target->dport);

        target->arp_len = arp_build_req(target->sip, target->smac, target->gate, target->arp_req);
        if(target != (&log_server_copy))
            goto build_log_server;
        //target->complete = 1;
	}
	ret = 1;
end:
	conf_uninit(&cd);
	return ret;
}

/* control-C handler */
static void
sigint_h(int sig)
{
	(void)sig;	/* UNUSED */

	track_run = 0;
}

int main(int argc, char *argv[])
{
    int ch;
    int i, j;
    unsigned char *conf_file = "session.conf";//NULL;
    pthread_t read_t;
    pthread_t send_t;
    pthread_t control_t;

    /*
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
	*/

    if(!conf_file)
    {
        usage();
        return 0;
    }
    //if(daemon(1, 1) >= 0)
    {
        if(!session_config(conf_file))
        {
            fprintf(stderr, "please check conf file!\n");
            goto over;
        }
        if(!strlen(inbound) || !strlen(outbound))
        {
            fprintf(stderr, "no inbound or outbound!\n");
            goto over;
        }

        //ict = ipcount_init();
        for(i = 0; i < MAX_TRACKER; i++)
        {
            tracker *ter = &global_ter[i];
            ter->type = TRACKER_TYPE_SESSION;
            ter->pool = session_pool_init();
            ter->ti = (http_info *)malloc(MAX_TCP_SESSION * sizeof(http_info));
            ter->pti = (http_info **)malloc(MAX_TCP_SESSION * sizeof(http_info *));
            ter->ri = (http_info **)malloc(MAX_REPORT * sizeof(http_info *));
            ter->ri_timeout = (http_info **)malloc(MAX_REPORT * sizeof(http_info *));
            if(!ter->pool || !ter->ti || !ter->pti || !ter->ri || !ter->ri_timeout)
                goto over;
            memset(ter->ti, 0, MAX_TCP_SESSION * sizeof(http_info));
            memset(ter->pti, 0, MAX_TCP_SESSION * sizeof(http_info *));
            memset(ter->ri, 0, MAX_REPORT * sizeof(http_info *));
            memset(ter->ri_timeout, 0, MAX_REPORT * sizeof(http_info *));
            for(j = 0; j < MAX_TCP_SESSION; j++)
                ter->pti[j] = &(ter->ti[j]);
            ter->rvj = ter->sdj = MAX_SLOT - 1;
            ter->rij = ter->rtj = MAX_REPORT - 1;
            ter->run_at_cpu = i + 2;
        }
        {
            tracker *cer = &global_ter[MAX_TRACKER];
            cer->type = TRACKER_TYPE_IP;
            cer->ict = ipcount_init();
            cer->ip_report = (ip_data *)malloc(MAX_IP_NUM * sizeof(ip_data));
            if(cer->ip_report)
                memset(cer->ip_report, 0, MAX_IP_NUM * sizeof(ip_data));
            cer->rvj = cer->sdj = MAX_SLOT - 1;
            cer->rij = cer->rtj = MAX_REPORT - 1;
            cer->run_at_cpu = MAX_TRACKER + 2;
        }
        num_init();
        if(!(opera = get_opera()))
            goto over;

        fdr = efio_init(inbound, EF_CAPTURE_NETMAP, EF_ENABLE_READ, 1);
        fdw = efio_init(outbound, EF_CAPTURE_NETMAP, EF_ENABLE_READ | EF_ENABLE_SEND, 1);
        signal(SIGINT, sigint_h);
        signal(SIGTERM, sigint_h);
        signal(SIGKILL, sigint_h);

        track_run = 1;
        for(i = 0; i <= MAX_TRACKER; i++)
        {
            pthread_create(&(global_ter[i].process), NULL, session_process, (void *)&(global_ter[i]));
            pthread_create(&(global_ter[i].report), NULL, session_report, (void *)&(global_ter[i]));
        }
        pthread_create(&read_t, NULL, session_read, NULL);
        pthread_create(&send_t, NULL, session_send, NULL);
        pthread_create(&control_t, NULL, session_control, NULL);
        for(i = 0; i <= MAX_TRACKER; i++)
        {
            pthread_join(global_ter[i].process, NULL);
            pthread_join(global_ter[i].report, NULL);
        }
        fprintf(stderr, "timer end\n");
        pthread_join(read_t, NULL);
        fprintf(stderr, "read end\n");
        pthread_join(send_t, NULL);
        fprintf(stderr, "send end\n");
        pthread_join(control_t, NULL);
    }
    //else
        //fprintf(stderr, "cannot create run session track process!\n");
over:
    //if(ict)
        //ipcount_tini(ict);
    for(i = 0; i < MAX_TRACKER; i++)
    {
        tracker *ter = &global_ter[i];
        if(ter->pool)
            session_pool_tini(ter->pool);
        if(ter->ti)
            free(ter->ti);
        if(ter->pti)
            free(ter->pti);
        if(ter->ri)
            free(ter->ri);
        if(ter->ri_timeout)
            free(ter->ri_timeout);
    }
    {
        tracker *cer = &global_ter[MAX_TRACKER];
        if(cer->ict)
            ipcount_tini(cer->ict);
        if(cer->ip_report)
            free(cer->ip_report);
    }
    if(fdr)
        efio_tini(fdr);
    if(fdw)
        efio_tini(fdw);
    if(opera)
        release_opera(opera);
    fprintf(stderr, "session track over!\n");
    return 0;
}

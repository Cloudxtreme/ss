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
#include <eftrack.h>
#include "conf.h"



static ip_info *ii = NULL;
static ip_info **pii = NULL;
static ip_info *ii_hash[0x10000] = {0};

static http_info *ti = NULL;
static http_info **pti = NULL;
static unsigned int pti_cur = 0, pti_rec = 0;

static send_target log_server = {0};
static http_info **ri = NULL;
static http_info **ri_timeout = NULL;
static unsigned int rsi = 0, rsj = MAX_SLOT - 1, wsi = 0, wsj = MAX_SLOT - 1, rii = 0, rij = MAX_REPORT - 1, rti = 0, rtj = MAX_REPORT - 1;

static ef_slot r_slot[MAX_SLOT] = {0};
static ef_slot w_slot[MAX_SLOT] = {0};
static int w_chk[MAX_SLOT] = {0};

static char inbound[32] = {0}, outbound[32] = {0};
static int fdr = 0, fdw = 0;
static int track_run = 0;
static unsigned long g_session_time = 0;
static int read_cpu = -1, process_cpu = -1, report_cpu = -1, send_cpu = -1;

static unsigned long long detail_use = 0, detail_rec = 0, detail_timeout = 0;
static unsigned long long pkg_total = 0;
static unsigned long long http_total = 0;


static int session_timer();
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


static int session_control()
{
    unsigned long long recv_curr = 0, recv_prev = 0, pps;
    while(track_run)
    {
        recv_curr = pkg_total;
        pps = recv_curr - recv_prev;

        usleep(1000000);
        recv_prev = recv_curr;

    }
}

static int session_timer()
{
    struct timeval now;
	while(track_run)
	{
		gettimeofday(&now, NULL);
		g_session_time = now.tv_sec * 1000000 + now.tv_usec;
		usleep(0);
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
	fprintf(stderr, "[read]...\n");
	while(track_run)
	{
        int ret;
		int i = rsi;
		int j = rsj;
		//int max_read = (i >= j) ? (MAX_SLOT - i + j - 1) : (j - i - 1);
		int max_read = (i >= j) ? (MAX_SLOT - i + j - 1) : (j - i - 1);

        if(i + max_read > MAX_SLOT)
            max_read = MAX_SLOT - i;
		ret = efio_flush(fdr, EF_FLUSH_READ, 2);
		if(max_read)
		{
			if(ret & EF_FLUSH_READ)
			{
                int reads = efio_read(fdr, &r_slot[rsi], max_read);
				//rsi = (rsi + efio_read(fdr, &r_slot[rsi], max_read)) % MAX_SLOT;
				rsi = (rsi + reads) % MAX_SLOT;
				pkg_total += reads;
            }
		}
		else
			usleep(0);
	}

}

#define STR_OVERFLOW(pkg, len, str) ((str < pkg) || (str > pkg + len))
static int session_process()
{
	//get pkg from read_slot and process and put result to report info
	void *pkg;
	unsigned short len;
	session_pool *pool = NULL;
	session *s;
	buf_t *url_buf = NULL;

    if(process_cpu >= 0)
	{
        unsigned long mask = 1;
		mask = mask << process_cpu;
		sched_setaffinity(0, sizeof(mask), &mask);
	}

    if(!(pool = session_pool_init()))
        goto done;
    if(!(url_buf = buf_t_init(1024 * 1024 * 1024)))
        goto done;
	while(track_run)
	{
		int i1 = rsi;
		int j1 = rsj;
		int i2 = rii;
		int j2 = rij;
		int process_read = (j1 >= i1) ? (MAX_SLOT - j1 + i1 - 1) : (i1 - j1 - 1);
		int create_report = (i2 >= j2) ? (MAX_REPORT - i2 + j2 - 1) : (j2 - i2 - 1);

        if(!process_read || !create_report)
        {
            usleep(0);
            continue;
        }
		while(process_read && create_report)
		{
            rsj = (rsj + 1 == MAX_SLOT) ? 0 : (rsj + 1);
			pkg = r_slot[rsj].buf;
        	len = r_slot[rsj].len;
        	*(unsigned char *)(pkg + len) = 0;

            #if 0
            if(IF_IP(pkg))
            {
                unsigned int ip = 0;
                unsigned short *p = (unsigned short *)&ip;
                unsigned short hash;
                ip_info *pii = NULL;
                if(record_sip)
                    ip = GET_IP_SIP(pkg);
                else
                    ip = GET_IP_DIP(pkg);
                hash = p[0] ^ p[1];
                if(!ii_hash[hash])
                {
                    if(ii_cur < ii_tot)
                    {
                        ii_hash[hash] = &ii[ii_cur++];
                        pii = ii_hash[hash];
                        pii->ip = ip;
                        pii->pkg = 0;
                        pii->flow = 0;
                        pii->next = NULL;
                    }
                }
                else
                {
                    pii = ii_hash[hash];
                    while((pii->ip != ip) && (pii->next))
                        pii = pii->next;
                    if((pii->ip != ip) && (ii_cur < ii_tot))
                    {
                        pii->next = &ii[ii_cur++];
                        pii = pii->next;
                        pii->ip = ip;
                        pii->pkg = 0;
                        pii->flow = 0;
                        pii->next = NULL;
                    }
                }
                if(pii)
                {
                    pii->pkg++;
                    pii->flow += len;
                }
            }
            #endif

        	if(likely(IF_TCP(pkg)))
        	{
            	struct tcphdr *tph = P_TCPP(pkg);
            	http_info *detail = NULL;
         		unsigned int seq, ack;

         		s = session_get(pool, pkg);
            	if(!s)
                	goto next_process;
            	seq = ntohi(tph->seq);
            	ack = ntohi(tph->ack_seq);
            	detail = (http_info *)session_get_detail(s);
            	if(IF_SYN(pkg) && !IF_ACK(pkg))//if(IF_SYN(pkg))
            	{
                	if(!detail)//if(session_is_master(s) && !detail)
                	{
                    	int j = pti_cur;
                    	if(!pti[j]->use)
                    	{
                        	memset(pti[j], 0, sizeof(http_info));
                        	pti[j]->use = 1;
                        	pti[j]->syn_time = r_slot[rsj].time;
                        	pti[j]->stats = TCP_STAT_CREAT;
                        	detail = pti[j];
                        	detail->sip = GET_IP_SIP(pkg);
                        	detail->dip = GET_IP_DIP(pkg);
                        	detail->sport = GET_IP_SPORT(pkg);
                        	detail->dport = GET_IP_DPORT(pkg);
                        	get_block(url_buf, &(detail->url_buf_block));
                        	session_set_detail(s, detail);
                        	session_set_timeout(s, TCP_TIMEOUT);
                        	session_set_timeout_callback(s, (void *)session_timeout);
                        	detail_use++;
                        	pti_cur++;
                    	}
                    	else
                            goto next_process;
                	}
            	}
            syn_end:
            	if(IF_ACK(pkg))
            	{
                    //fprintf(stderr, "[process] ack\n");
                	if(detail)
                	{
                    	char *http_info = ((char*)P_TCPP(pkg)+(P_TCPP(pkg)->doff<<2));
                    	unsigned int http_method;
                    	char *url_begin = NULL;
                    	char *url_end = NULL;
                    	char *host_begin = NULL;
                    	char *host_end = NULL;
                    	char http_host[100];
                    	unsigned int url_len = 0, host_len = 0;

                        if(STR_OVERFLOW(pkg, len, http_info))
                            goto ack_end;
                        if(detail->stats == TCP_STAT_FIN)
                            goto next_process;
                    	if(detail->stats != TCP_STAT_CONN)
                    	{
                        	detail->first_ack_time = r_slot[rsj].time;
                        	detail->stats = TCP_STAT_CONN;
                    	}
                    	detail->last_ack_time = r_slot[rsj].time;

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
                        	if(host_end)
                        	{
                                host_len = host_end - host_begin;
                                host_len = (host_len > 99) ? 99 : host_len;
                                memcpy(&http_host[1], host_begin, host_len);
                            }
                            else
                                host_len = ip_2_str(GET_IP_DIP(pkg), &http_host[1]);
                    	}
                    	else
                    	{
                        	host_len = ip_2_str(GET_IP_DIP(pkg), &http_host[1]);
                    	}


                    	if(detail->url_len < MAX_URL_LEN_EACH_SESSION)
                    	{
                            http_host[0] = http_host[host_len + 1] = ' ';
                            host_len += 2;
							detail->url_len += put_buf_to_block(&(detail->url_buf_block), http_host, host_len);
							detail->url_len += put_buf_to_block(&(detail->url_buf_block), http_info, url_len);
                        }
                	}
                	else
                	{
                        ack_without_detail++;
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
                    	detail->fin_time = r_slot[rsj].time;
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
                    #if 1
						ri[rii] = detail;
                        rii = (rii + 1 == MAX_REPORT) ? 0 : (rii + 1);
                    #endif
                        session_close(s);
                        create_report--;
                    }
                }
        	}
            else if(IF_ARP_REPLY(pkg))
            {
                unsigned char *mac = GET_ARP_SMAC(pkg);
                unsigned int sip = GET_ARP_SIP(pkg);
                unsigned int dip = GET_ARP_DIP(pkg);
                if((sip == log_server.gate) && (dip == log_server.sip))
                {
                    log_server.complete = 1;
                    memcpy(log_server.dmac, mac, 6);
                    memcpy(log_server.hdr, mac, 6);
                }
            }
        next_process:
            process_read--;
		}
	}
done:
    if(pool)
        session_pool_tini(pool);
    if(url_buf)
        buf_t_tini(url_buf);
}

static int session_timeout(session *s)
{
	//process timeout session
	http_info *detail = (http_info *)session_get_detail(s);
	if(detail)
	{
        detail_timeout++;
        detail->stats = TCP_STAT_TIMEOUT;
        if((rti + 1) % MAX_REPORT != rtj)
        {
            ri_timeout[rti] = detail;
            rti = (rti + 1 == MAX_REPORT) ? 0 : (rti + 1);
        }
	}
	return 0;
}

static int session_report()
{
	//get report info and build send_slot
	char *mon_str[] = {"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"};
    unsigned char log_header[64] = {0};
    unsigned char log_header_len = 0;
    unsigned long last_time = 0, ip_report_time = 0;

    if(report_cpu >= 0)
	{
        unsigned long mask = 1;
		mask = mask << report_cpu;
		sched_setaffinity(0, sizeof(mask), &mask);
	}

	while(track_run)
	{
		int i1 = rii;
		int j1 = rij;
		int i2 = wsi;
		int j2 = wsj;
        int t1 = rti;
        int t2 = rtj;
        int timeout = (t2 >= t1) ? (MAX_REPORT - t2 + t1 - 1) : (t1 - t2 - 1);
		int report1 = (j1 >= i1) ? (MAX_REPORT - j1 + i1 - 1) : (i1 - j1 - 1);
		int report2 = (i2 >= j2) ? (MAX_SLOT - i2 + j2 - 1) : (j2 - i2 - 1);
		int max_report = (report1 + timeout) <= report2 ? (report1 + timeout) : report2;
		int done = 0;

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


        if((g_session_time - ip_report_time >= 60000000) || (ii_send < ii_cur))
        {
            int log_len = 0;
            char *log_buf = NULL;


            if(log_server.complete)
            {
                if(ii_send >= ii_cur)
                    ii_send = 0;

                while(report2)
                {
                    log_buf = w_slot[wsi].buf;
                    log_len = 0;
                    w_chk[wsi] = 0;
                    memcpy(&log_buf[log_len], log_server.hdr, log_server.hdr_len);
                    log_len += log_server.hdr_len;
                    memcpy(&log_buf[log_len], log_header, log_header_len);
                    log_len += log_header_len;
                    memcpy(&log_buf[log_len], "[ip_info] ", 10);
                    log_len += 10;
                    while((log_len < 1000) && (ii_send < ii_cur))
                    {
                        log_len += ip_2_str(ii[ii_send].ip, &log_buf[log_len]);
                        log_buf[log_len++] = ':';
                        log_len += num_2_str(ii[ii_send].pkg, &log_buf[log_len]);
                        log_buf[log_len++] = ':';
                        log_len += num_2_str(ii[ii_send].flow, &log_buf[log_len]);
                        log_buf[log_len++] = ' ';
                        ii_send++;
                    }
                    w_slot[wsi].len = log_len;
                    wsi = (wsi + 1 == MAX_SLOT) ? 0 : (wsi + 1);
                    report2--;
                }
                ip_report_time = g_session_time;
                max_report = (report1 + timeout) <= report2 ? (report1 + timeout) : report2;
            }
        }


		while(max_report)
		{
    		char *session_log;
    		int log_len = 0;
    		http_info *detail = NULL;
    		struct iphdr *iph;
    		struct udphdr *uph;

    		if(done++ < timeout)
            {
                rtj = (rtj + 1 == MAX_REPORT) ? 0 : (rtj + 1);
                detail = ri_timeout[rtj];
            }
            else
            {
                rij = (rij + 1 == MAX_REPORT) ? 0 : (rij + 1);
                detail = ri[rij];
            }

    		session_log = w_slot[wsi].buf;
    		w_chk[wsi] = 0;

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
                    http_total++;
                    if(detail->url_len)
                    {
                        memcpy(&session_log[log_len], "http ", 5);
                        log_len += 5;
                        copy_buf_from_block(&(detail->url_buf_block), &session_log[log_len], detail->url_len);
                        release_block(&(detail->url_buf_block));
                    }
                    break;
                default:
                    memcpy(&session_log[log_len], "unknow\0", 7);
                    log_len += 7;
    		}
			session_log[log_len] = 0;
    		iph = P_IPP(w_slot[wsi].buf);
    		uph = P_UDPP(w_slot[wsi].buf);
    		iph->tot_len = htons(log_len - ((unsigned char *)iph - w_slot[wsi].buf));
    		uph->len = htons(log_len - ((unsigned char *)uph - w_slot[wsi].buf));
            w_slot[wsi].len = log_len;
			wsi = (wsi + 1 == MAX_SLOT) ? 0 : (wsi + 1);
        next_report:
			detail_rec++;
			pti[pti_rec++] = detail;
    		detail->use = 0;
			max_report--;
		}
	}
}

static int session_send()
{
	//send the send_slot
    if(send_cpu >= 0)
	{
        unsigned long mask = 1;
		mask = mask << send_cpu;
		sched_setaffinity(0, sizeof(mask), &mask);
	}
	while(track_run)
	{
        int ret;
        int i = wsi;
		int j = wsj;
		int max_send = (j >= i) ? (MAX_SLOT - j + i - 1) : (i - j - 1);

        if(j + max_send > MAX_SLOT)
            max_send = MAX_SLOT - j;
		ret = efio_flush(fdw, EF_FLUSH_SEND, 2);

        if(g_session_time > log_server.flush)
        {
            ef_slot slot;
            memcpy(slot.buf, log_server.arp_req, log_server.arp_len);
            slot.flag = 0;
            slot.out = fdw;
            slot.len = log_server.arp_len;
            efio_send(fdw, &slot, 1);
            if(log_server.complete)
                log_server.flush = g_session_time + 30000000;
            else
                log_server.flush = g_session_time + 3000000;
        }
		if(max_send && (ret & EF_FLUSH_SEND))
		{
			int k = 0;
			for(k = (j + 1) % MAX_SLOT; k != i; k = (k + 1 == MAX_SLOT) ? 0 : (k + 1))
			{
                if(!w_chk[k])
                {
                    pkg_checksum(w_slot[k].buf);
                    //decode_pkg(w_slot[k].buf, w_slot[k].len);
                    w_chk[k] = 1;
                }
			}
			wsj = (wsj + max_send) % MAX_SLOT;
            //wsj = (wsj + efio_send(fdw, &w_slot[(wsj + 1 == MAX_SLOT) ? 0 : (wsj + 1)], max_send)) % MAX_SLOT;
		}
		else
			usleep(0);
	}
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

        i = 1;
        send_target *target = &log_server;
        if(!conf_getval(cd, "log", type, sizeof(type), i++))
            break;
        if(!conf_getval(cd, "log", gate, sizeof(gate), i++))
            break;
        if(!conf_getval(cd, "log", vlan, sizeof(vlan), i++))
            break;
        if(!conf_getval(cd, "log", sip, sizeof(sip), i++))
            break;
        if(!conf_getval(cd, "log", dip, sizeof(dip), i++))
            break;
        if(!conf_getval(cd, "log", sport, sizeof(sport), i++))
            break;
        if(!conf_getval(cd, "log", dport, sizeof(dport), i++))
            break;

        if((target->gate = str_2_ip(gate)) == 0)
            break;
        if(atoi(vlan) < -1 || atoi(vlan) > 0x7fff)
            break;
        target->vlan = atoi(vlan);
        if((target->sip = str_2_ip(sip)) == 0)
            break;
        if((target->dip = str_2_ip(dip)) == 0)
            break;
        if(atoi(sport) < 1 || atoi(sport) > 0xffff)
            break;
        target->sport = atoi(sport);
        if(atoi(dport) < 1 || atoi(dport) > 0xffff)
            break;
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
    int i;
    unsigned char *conf_file = "session.conf";
    pthread_t timer_t;
    pthread_t read_t;
    pthread_t process_t;
    pthread_t report_t;
    pthread_t send_t;
    pthread_t control_t;

	/*while ( (ch = getopt(argc, argv, "c:hT")) != -1)
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
	}*/

    if(!conf_file)
    {
        usage();
        return 0;
    }
    //if(daemon(1, 1) >= 0)
    {
        num_init();
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
        fdr = efio_init(inbound, EF_CAPTURE_NETMAP, EF_ENABLE_READ, 1);
        fdw = efio_init(outbound, EF_CAPTURE_NETMAP, EF_ENABLE_SEND, 1);
        signal(SIGINT, sigint_h);
        signal(SIGTERM, sigint_h);
        signal(SIGKILL, sigint_h);
        ti = (http_info *)malloc(MAX_TCP_SESSION * sizeof(http_info));
        pti = (http_info **)malloc(MAX_TCP_SESSION * sizeof(http_info *));
        ri = (http_info **)malloc(MAX_REPORT * sizeof(http_info *));
        ri_timeout = (http_info **)malloc(MAX_REPORT * sizeof(http_info *));
        ii = (ip_info *)malloc(MAX_IP * sizeof(ip_info));
        if(!ti || !pti || !ri || !ri_timeout || !ii)
            goto over;
        memset(ti, 0, MAX_TCP_SESSION * sizeof(http_info));
        memset(pti, 0, MAX_TCP_SESSION * sizeof(http_info *));
        memset(ri, 0, MAX_REPORT * sizeof(http_info *));
        memset(ri_timeout, 0, MAX_REPORT * sizeof(http_info *));
        memset(ii, 0, MAX_IP * sizeof(ip_info));
        for(i = 0; i < MAX_TCP_SESSION; i++)
            pti[i] = &ti[i];
        track_run = 1;
        pthread_create(&timer_t, NULL, session_timer, NULL);
        pthread_create(&read_t, NULL, session_read, NULL);
        pthread_create(&process_t, NULL, session_process, NULL);
        pthread_create(&report_t, NULL, session_report, NULL);
        pthread_create(&send_t, NULL, session_send, NULL);
        pthread_create(&control_t, NULL, session_control, NULL);
        pthread_join(timer_t, NULL);
        fprintf(stderr, "timer end\n");
        pthread_join(read_t, NULL);
        fprintf(stderr, "read end\n");
        pthread_join(process_t, NULL);
        fprintf(stderr, "process end\n");
        pthread_join(report_t, NULL);
        fprintf(stderr, "report end\n");
        pthread_join(send_t, NULL);
        fprintf(stderr, "send end\n");
        pthread_join(control_t, NULL);
        fprintf(stderr, "wait session\n");
    }
    //else
        //fprintf(stderr, "cannot create run session track process!\n");
over:
    if(fdr)
        efio_tini(fdr);
    if(fdw)
        efio_tini(fdw);
    if(ti)
        free(ti);
    if(pti)
        free(pti);
    if(ri)
        free(ri);
    if(ri_timeout)
        free(ri_timeout);
    if(ii)
        free(ii);
    fprintf(stderr, "session track over!\n");
    return 0;
}

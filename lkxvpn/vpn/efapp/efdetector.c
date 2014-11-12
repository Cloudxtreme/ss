#include <stdio.h>
#include <efdetect.h>
#include <signal.h>	/* signal */
#include <sys/shm.h>
#include "conf.h"




static unsigned int g_database_num = 0;
static unsigned int g_reader_num = 0;
static unsigned int g_worker_num = 0;
static unsigned int g_log_target_num = 0;

static database *g_db[MAX_DATABASE] = {0};
static reader_t *g_reader[MAX_READER] = {0};
static worker_t *g_worker[MAX_WORKER] = {0};
static log_target *g_log_target[LOG_MAX_TARGET] = {0};
static volatile int g_run = 0;
static volatile int c_run = 0;

extern unsigned long base_time;

unsigned volatile long long pkg_total[MAX_READER] = {0};


static void lock(char *lock)
{
    while(__sync_lock_test_and_set(lock, 1));
}

static void unlock(char *lock)
{
    __sync_lock_test_and_set(lock, 0);
}



static int control()
{
    int i, j;
    unsigned long long pkg_sum = 0, pkg_prev = 0, pkgs = 0;
    while(g_run)
    {
        pkg_sum = 0;
        for(i = 0; i < g_reader_num; i++)
            pkg_sum += pkg_total[i];
        pkgs = pkg_sum - pkg_prev;
        fprintf(stderr, "pkg: %llu pps\n", pkgs);
        pkg_prev += pkgs;
        fprintf(stderr, "database:");
        for(i = 0; i < g_database_num; i++)
            fprintf(stderr, "|%u %u %u %u %u %u %u %u %llu|", g_db[i]->rii, g_db[i]->rij, g_db[i]->rti, g_db[i]->rtj, g_db[i]->pti_cur, g_db[i]->pti_rec, g_db[i]->sli, g_db[i]->slj, g_db[i]->ip_total);
        fprintf(stderr, "\n");
        fprintf(stderr, "reader:");
        for(i = 0; i < g_reader_num; i++)
            fprintf(stderr, "|%d %d|", g_reader[i]->cur, g_reader[i]->fin);
        fprintf(stderr, "\n");
        fprintf(stderr, "worker:\n");
        for(i = 0; i < g_worker_num; i++)
        {
            for(j = 0; j < g_reader_num; j++)
            {
                work_line *line = &(g_worker[i]->line[j]);
                fprintf(stderr, "|%d\t%d\t%d|\t", line->cur, line->fin, line->alive);
            }
            fprintf(stderr, "\n");
        }
        fprintf(stderr, "\n------------------------------------------------------------------------\n");
        sleep(1);
    }
}


static int read(void *arg)
{
    reader_t *reader = (reader_t *)arg;
    fprintf(stderr, "reader %s begin!\n", reader->dev);
    if(1)
	{
        unsigned long mask = 1;
		mask = mask << reader->id;
		sched_setaffinity(0, sizeof(mask), &mask);
	}
	while(g_run)
	{
        int i, ret;
        unsigned int cur, fin, max_read, reads, done, pos1, pos2;

        pos1 = pos2 = READER_MAX_SLOT;
        for(i = 0; i < g_worker_num; i++)
        {
            work_line *line = &(g_worker[i]->line[reader->id]);
            cur = line->cur;
            fin = line->fin;
            done = line->boxs[fin].pos;
            if(done < reader->fin)
            {
                if(done < pos1)
                    pos1 = done;
            }
            else
            {
                if(done < pos2)
                    pos2 = done;
            }
            line->alive = (cur >= fin) ? (LINE_LENGTH - cur + fin - 1) : (fin - cur - 1);
        }
        if(pos2 != READER_MAX_SLOT)
            reader->fin = pos2;
        else
            reader->fin = pos1;

        if(reader->fin == reader->cur)
            reader->fin = (reader->cur) ? (reader->cur - 1) : (READER_MAX_SLOT - 1);
        cur = reader->cur;
        fin = reader->fin;
        max_read = (cur >= fin) ? (READER_MAX_SLOT - cur + fin - 1) : (fin - cur - 1);
        if(cur + max_read > READER_MAX_SLOT)
            max_read = READER_MAX_SLOT - cur;
        if(max_read)
        {
            ret = efio_flush(reader->fd, EF_FLUSH_READ, 2);
            if(!(ret & EF_FLUSH_READ))
            {
                usleep(0);
                continue;
            }
            reads = efio_read(reader->fd, &reader->slot[cur], max_read);
            pkg_total[reader->id] += reads;
            for(i = 0; i < reads; i++)
            {
                void *pkg = reader->slot[cur + i].buf;
                unsigned int len = reader->slot[cur + i].len;
                unsigned int key = 0;
                work_line *line = NULL;

                if(IF_IP(pkg))
                {
                    unsigned int sip = GET_IP_SIP(pkg);
                    unsigned int dip = GET_IP_DIP(pkg);
                    key = (sip ^ dip) % g_worker_num;
                }
                line = &(g_worker[key]->line[reader->id]);
                if(line->alive)
                {
                    line->boxs[line->cur].reader_id = reader->id;
                    line->boxs[line->cur].pos = cur + i;
                    line->cur = (line->cur + 1 == LINE_LENGTH) ? 0 : (line->cur + 1);
                    line->alive--;
                }
            }
            reader->cur = (reader->cur + reads) % READER_MAX_SLOT;
        }
        else
            usleep(0);
	}
}

static int pkg_process(database *db, reader_t *reader, ef_slot *slot);
static int work(void *arg)
{
    worker_t *worker = (worker_t *)arg;
    unsigned int w_cur = 0, l_cur = 0;
    fprintf(stderr, "worker %d begin!\n", worker->id);
    if(1)
	{
        unsigned long mask = 1;
		mask = mask << (g_reader_num + worker->id);
		sched_setaffinity(0, sizeof(mask), &mask);
	}
    while(g_run)
    {
        int i;
        unsigned int cur, fin, tasks, total = 0;

        for(i = 0; i < g_reader_num; i++)
        {
            box_t *box = NULL;
            reader_t *reader = NULL;
            ef_slot *slot = NULL;

            work_line *line = &(worker->line[i]);

            cur = line->cur;
            fin = line->fin;
            tasks = (fin >= cur) ? (LINE_LENGTH - fin + cur - 1) : (cur - fin - 1);
            total += tasks;
            while(tasks)
            {
                fin = (fin + 1 == LINE_LENGTH) ? 0 : (fin + 1);
                box = &(line->boxs[fin]);
                reader = g_reader[box->reader_id];
                slot = &(reader->slot[box->pos]);
                pkg_process(reader->db, reader, slot);
                tasks--;
            }
            line->fin = fin;
        }
        if(!total)
            usleep(0);
    }
}

static http_info *get_http_detail(database *db)
{
    http_info *detail = NULL;
    lock(&db->lock);
    if(!db->pti[db->pti_cur]->use)
    {
        detail = db->pti[db->pti_cur];
        memset(detail, 0, sizeof(http_info));
        detail->db_id = db->id;
        db->pti_cur = (db->pti_cur + 1 == DATABASE_MAX_SESSION) ? 0 : (db->pti_cur + 1);
    }
    unlock(&db->lock);
    return detail;
}

static int fin_http_detail(database *db, http_info *detail, int timeout)
{
    lock(&db->lock);
    if(!timeout)
    {
        unsigned int tmp = (db->rii + 1 == DATABASE_MAX_REPORT) ? 0 : (db->rii + 1);
        if(tmp != db->rij)
        {
            db->ri[db->rii] = detail;
            db->rii = tmp;
        }
    }
    else
    {
        unsigned int tmp = (db->rti + 1 == DATABASE_MAX_REPORT) ? 0 : (db->rti + 1);
        if(tmp != db->rtj)
        {
            db->ri_timeout[db->rti] = detail;
            db->rti = tmp;
        }
    }
    unlock(&db->lock);
    return 1;
}

static int rec_http_detail(database *db, http_info *detail)
{
    lock(&db->lock);
    db->pti[db->pti_rec] = detail;
    db->pti_rec = (db->pti_rec + 1 == DATABASE_MAX_SESSION) ? 0 : (db->pti_rec + 1);
    detail->use = 0;
    unlock(&db->lock);
    return 1;
}

static int timeout_process(session *s)
{
	http_info *detail = (http_info *)session_get_detail(s);
	if(detail)
	{
        detail->stats = TCP_STAT_TIMEOUT;
        fin_http_detail(g_db[detail->db_id], detail, 1);
	}
	return 0;
}

#define STR_OVERFLOW(pkg, len, str) ((str < pkg) || (str > pkg + len))
static int pkg_process(database *db, reader_t *reader, ef_slot *slot)
{
    session_pool *pool = db->pool;
    session *s;
    ip_count_t *ict = db->ict;
    void *pkg = slot->buf;
    unsigned short len = slot->len;
    char http_host[100];

    if(IF_IP(pkg))
    {
        struct iphdr *iph = P_IPP(pkg);
        ipcount_add_pkg(ict, pkg, len, reader->flag);
        if(IF_TCP(pkg))
        {
            	struct tcphdr *tph = P_TCPP(pkg);
            	http_info *detail = NULL;
         		unsigned int seq, ack;

         		s = session_get(pool, pkg, len);
            	if(!s)
                	goto fin_process;
            	seq = ntohi(tph->seq);
            	ack = ntohi(tph->ack_seq);
            	detail = (http_info *)session_get_detail(s);
            	if(IF_SYN(pkg) && !IF_ACK(pkg))//if(IF_SYN(pkg))
            	{
                	if(!detail)
                	{
                        detail = get_http_detail(db);
                    	if(detail)
                    	{
                        	detail->use = 1;
                        	detail->syn_time = time;
                        	detail->stats = TCP_STAT_CREAT;
                        	detail->sip = GET_IP_SIP(pkg);
                        	detail->dip = GET_IP_DIP(pkg);
                        	detail->sport = GET_IP_SPORT(pkg);
                        	detail->dport = GET_IP_DPORT(pkg);
                        	session_set_detail(s, detail);
                        	session_set_timeout(s, TCP_TIMEOUT);
                        	session_set_timeout_callback(s, (void *)timeout_process);
                        	ipcount_add_session(ict, detail->sip, detail->dip, IPCOUNT_SESSION_FLAG_ADD, 0, 0);
                    	}
                    	else
                    	{
                            fprintf(stderr, "no free detail!\n");
                            goto fin_process;
                        }
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
                            goto fin_process;
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


                    	if(detail->url_len + host_len + url_len + 2 < HTTP_URL_LEN)
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
                    if(detail)
                    {
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
            	}
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
                        int flow = session_get_flow(s);
                        if(detail->protocol == SESSION_PROTO_HTTP)
                            ipcount_add_session(ict, detail->sip, detail->dip, IPCOUNT_SESSION_FLAG_CLOSE, IPCOUNT_SESSION_TYPE_HTTP, flow);
                        else
                            ipcount_add_session(ict, detail->sip, detail->dip, IPCOUNT_SESSION_FLAG_CLOSE, 0, flow);
                        fin_http_detail(db, detail, 0);
                        //rec_http_detail(db, detail);
                        session_close(s);
                    }
                }
        	}
        fin_process:
            return 1;
    }
}

static int db_opera(database *db)
{
    detect_opera *opera = db->opera;
    if(opera->code)
    {
        ip_count_t *ict = db->ict;
        opera->result = 0;
        switch(opera->code)
        {
            case OPERA_ADD_IP:
            {
                unsigned int total = ipcount_get_ip_total(ict);
                if(total + opera->arg < DATABASE_MAX_IP)
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
                opera->result = ipcount_get_all_ip(ict, opera->ip, DATABASE_MAX_IP);
                break;
            default:;
        }
        opera->code = 0;
    }
}

static int db_collecter(void *arg)
{
    database *db = (database *)arg;
	char *mon_str[] = {"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"};
    unsigned char log_header[1024] = {0};
    unsigned char log_header_len = 0;
    unsigned long last_time = 0, ip_log_time = 0;
    unsigned long now;
    ip_data *ip_detail = NULL;



    ip_detail = (ip_data *)malloc(DATABASE_MAX_IP * sizeof(ip_data));
    if(1)
	{
        unsigned long mask = 1;
		mask = mask << (g_reader_num + g_worker_num + db->id);
		sched_setaffinity(0, sizeof(mask), &mask);
	}
	while(g_run)
	{
		now = base_time;
        if(now - last_time >= 1000000)
        {
            static struct tm *date = NULL;
            static time_t lt;
            lt = time(NULL);
            date = localtime(&lt);
            snprintf(log_header, sizeof(log_header), "<7>%s %02d %02d:%02d:%02d localhost kernel: \0",
                    mon_str[date->tm_mon], date->tm_mday, date->tm_hour, date->tm_min, date->tm_sec);
            log_header_len = strlen(log_header);
            last_time = now;
        }

        db_opera(db);

        if((now - ip_log_time >= 1000000) && (db->ili == db->ilj))
        {
            int i = 0, j = 0;
            unsigned int ip_total = ipcount_get_ip_total(db->ict);
            if(ip_total > DATABASE_MAX_IP)
                ip_total = DATABASE_MAX_IP;
            ipcount_get_all_ip(db->ict, ip_detail, ip_total);
            db->ip_total = ip_total;
            db->ip_log_total = 0;
            while(i < ip_total)
            {
                char *log_buf = db->ip_log[db->ip_log_total].str;//&db->ip_log[db->ip_log_total * DATABASE_LOG_LENGTH];
                int log_len = 0;

                memcpy(&log_buf[log_len], log_header, log_header_len); log_len += log_header_len;
                memcpy(&log_buf[log_len], db->name, strlen(db->name)); log_len += strlen(db->name);
                log_buf[log_len++] = ' ';
                memcpy(&log_buf[log_len], "{\"ip_info\":[", 12); log_len += 12;
                j = 0;
                while((log_len < 1000) && (i < ip_total))
                {
                    if(j)
                        log_buf[log_len++] = ',';
                    log_buf[log_len++] = '{';

                    //"ip":"              0x22706922      0x223a
                    //"recv":"            0x223a227663657222
                    //"send":"            0x223a22646e657322
                    //"inflow":"          0x22776f6c666e6922  0x223a
                    //"outflow":"         0x776f6c6674756f22  0x3a22  0x22
                    //"tcpflow":"         0x776f6c6670637422  0x3a22  0x22
                    //"udpflow":"         0x776f6c6670647522  0x3a22  0x22
                    //"icmpflow":"        0x6f6c66706d636922  0x223a2277
                    //"httpflow":"        0x6f6c667074746822  0x223a2277
                    //"s_total":"         0x6c61746f745f7322  0x3a22  0x22
                    //"s_close":"         0x65736f6c635f7322  0x3a22  0x22
                    //"s_http":"          0x22707474685f7322  0x223a

                    //memcpy(&log_buf[log_len], "\"ip\":\"", 6); log_len += 6;
                    *(unsigned int *)&log_buf[log_len] = 0x22706922;
                    log_len += 4;
                    *(unsigned short *)&log_buf[log_len] = 0x223a;
                    log_len += 2;
                    log_len += ip_2_str(ip_detail[i].ip, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    //memcpy(&log_buf[log_len], "\"recv\":\"", 8); log_len += 8;
                    *(unsigned long long *)&log_buf[log_len] = 0x223a227663657222;
                    log_len += 8;
                    log_len += num_2_str(ip_detail[i].recv, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    //memcpy(&log_buf[log_len], "\"send\":\"", 8); log_len += 8;
                    *(unsigned long long *)&log_buf[log_len] = 0x223a22646e657322;
                    log_len += 8;
                    log_len += num_2_str(ip_detail[i].send, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    //memcpy(&log_buf[log_len], "\"inflow\":\"", 10); log_len += 10;
                    *(unsigned long long *)&log_buf[log_len] = 0x22776f6c666e6922;
                    log_len += 8;
                    *(unsigned short *)&log_buf[log_len] = 0x223a;
                    log_len += 2;
                    log_len += num_2_str(ip_detail[i].inflow, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    //memcpy(&log_buf[log_len], "\"outflow\":\"", 11); log_len += 11;
                    *(unsigned long long *)&log_buf[log_len] = 0x776f6c6674756f22;
                    log_len += 8;
                    *(unsigned short *)&log_buf[log_len] = 0x3a22;
                    log_len += 2;
                    log_buf[log_len++] = 0x22;
                    log_len += num_2_str(ip_detail[i].outflow, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';


                    memcpy(&log_buf[log_len], "\"tcpflow\":\"", 11); log_len += 11;
                    log_len += num_2_str(ip_detail[i].tcp_flow, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    memcpy(&log_buf[log_len], "\"udpflow\":\"", 11); log_len += 11;
                    log_len += num_2_str(ip_detail[i].udp_flow, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    memcpy(&log_buf[log_len], "\"icmpflow\":\"", 12); log_len += 12;
                    log_len += num_2_str(ip_detail[i].icmp_flow, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    memcpy(&log_buf[log_len], "\"httpflow\":\"", 12); log_len += 12;
                    log_len += num_2_str(ip_detail[i].http_flow, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    memcpy(&log_buf[log_len], "\"session_total\":\"", 17); log_len += 17;
                    log_len += num_2_str(ip_detail[i].session_total, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    memcpy(&log_buf[log_len], "\"session_close\":\"", 17); log_len += 17;
                    log_len += num_2_str(ip_detail[i].session_close, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    memcpy(&log_buf[log_len], "\"session_http\":\"", 16); log_len += 16;
                    log_len += num_2_str(ip_detail[i].http_session, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = '}';
                    i++;j++;
                }
                log_buf[log_len++] = ']';log_buf[log_len++] = '}';log_buf[log_len] = 0;
                db->ip_log[db->ip_log_total++].len = log_len;
            }
            ip_log_time = now;
            db->ili++;
        }

        if(1)
        {
            #if 1
            int i = db->sli;
            int j = db->slj;
            int rij = (db->rij + 1 == DATABASE_MAX_REPORT) ? 0 : (db->rij + 1);
            int rtj = (db->rtj + 1 == DATABASE_MAX_REPORT) ? 0 : (db->rtj + 1);
            http_info *timeout_detail = db->ri_timeout[rtj];
            http_info *normal_detail = db->ri[rij];
            int max_log = (i >= j) ? (DATABASE_MAX_LOG - i + j - 1) : (j - i - 1);
            if((!timeout_detail && !normal_detail) || !max_log)
            {
                usleep(0);
                continue;
            }
            while((timeout_detail || normal_detail) && max_log)
            {
                char *session_log;
                int log_len = 0;
                http_info *detail = NULL;

                if(timeout_detail)
                {
                    detail = timeout_detail;
                    db->ri_timeout[rtj] = NULL;
                    db->rtj = rtj;
                    rtj = (rtj + 1 == DATABASE_MAX_REPORT) ? 0 : (rtj + 1);
                    timeout_detail = db->ri_timeout[rtj];
                }
                else
                {
                    detail = normal_detail;
                    db->ri[rij] = NULL;
                    db->rij = rij;
                    rij = (rij + 1 == DATABASE_MAX_REPORT) ? 0 : (rij + 1);
                    normal_detail = db->ri[rij];
                }
                session_log = db->session_log[db->sli].str;
                memcpy(&session_log[log_len], log_header, log_header_len);
                log_len += log_header_len;
                memcpy(&session_log[log_len], db->name, strlen(db->name)); log_len += strlen(db->name);
                session_log[log_len++] = ' ';
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
                db->session_log[db->sli].len = log_len;
                db->sli = (db->sli + 1 == DATABASE_MAX_LOG) ? 0 : (db->sli + 1);
                max_log--;
            next_report:
                rec_http_detail(db, detail);
            }
            #endif
        }
	}
	free(ip_detail);
}

static int db_sender(void *arg)
{
    database *db = (database *)arg;
    unsigned int send_total = 0;

    if(1)
	{
        unsigned long mask = 1;
		mask = mask << (g_reader_num + g_worker_num + db->id);
		sched_setaffinity(0, sizeof(mask), &mask);
	}
    while(g_run)
    {
        int i = 0, j = 0, send = 0, send_len = 0, ret = 0;

        send_total = 0;
        if(db->ili != db->ilj)
        {
            for(i = 0; i < g_log_target_num; i++)
            {
                if(g_log_target[i]->log_type & LOG_TYPE_IP)
                {
                    for(j = 0; j < db->ip_log_total; j++)
                    {
                        send_len = 0;
                        while(send_len < db->ip_log[j].len)
                        {
                            ret = sendto(db->log_fd[i], &(db->ip_log[j].str[send_len]), db->ip_log[j].len, 0, &(g_log_target[i]->target), sizeof(struct sockaddr_in));
                            if(ret > 0)
                                send_len += ret;
                        }
                        send_total++;
                    }
                }
            }
            db->ilj++;
        }

        i = db->sli;
        j = db->slj;
        send = (j >= i) ? (DATABASE_MAX_LOG - j + i - 1) : (i - j - 1);
        if(j + send > DATABASE_MAX_LOG)
            send = DATABASE_MAX_LOG - j;
        if(send)
        {
            for(i = 0; i < g_log_target_num; i++)
            {
                if(g_log_target[i]->log_type & LOG_TYPE_SESSION)
                {
                    unsigned int slj = db->slj;
                    for(j = 0; j < send; j++)
                    {
                        slj = (slj + 1 == DATABASE_MAX_LOG) ? 0 : (slj + 1);
                        sendto(db->log_fd[i], db->session_log[slj].str, db->session_log[slj].len, 0, &(g_log_target[i]->target), sizeof(struct sockaddr_in));
                        send_total++;
                    }
                }
            }
            db->slj = (db->slj + send) % DATABASE_MAX_LOG;
        }

        if(!send_total)
            usleep(0);
        usleep(100);
    }
}

static void release_opera(detect_opera *opera)
{
    if(opera)
    {
        int *id = opera->id;
        shmdt(opera);
        shmctl(id, IPC_RMID, NULL);
    }
}

static detect_opera *get_opera(const unsigned char *name)
{
    FILE *fp = NULL;
    char path[1024];
    snprintf(path, sizeof(path), "/etc/efdetect/%s\0", name);
    if((fp = fopen(path, "w")) == NULL)
    {
        return NULL;
    }
    fclose(fp);
    int *key = ftok(path, (int)'a');
	int *id = shmget(key, sizeof(detect_opera), IPC_CREAT | 0777);
	detect_opera *opera = (detect_opera *)shmat(id, NULL, 0);

	if((int)opera == -1)
	{
		shmctl(id, IPC_RMID, NULL);
		opera = NULL;
	}
	opera->id = id;
	return opera;
}


static int free_database(database *db)
{
    int i;
    if(db)
    {
        if(db->ict)
            ipcount_tini(db->ict);
        if(db->pool)
            session_pool_tini(db->pool);
        if(db->opera)
            release_opera(db->opera);
        if(db->ti)
            free(db->ti);
        if(db->pti)
            free(db->pti);
        if(db->ri)
            free(db->ri);
        if(db->ri_timeout)
            free(db->ri_timeout);
        if(db->ip_log)
            free(db->ip_log);
        if(db->session_log)
            free(db->session_log);
        for(i = 0; i < g_log_target_num; i++)
        {
            if(db->log_fd[i])
                close(db->log_fd[i]);
        }
        free(db);
    }
    return 1;
}

static database *init_database(unsigned int id, unsigned char *name)
{
    int j;
    database *db;
    db = (database *)malloc(sizeof(database));
    if(!db)
        goto err;
    memset(db, 0, sizeof(database));
    db->id = id;
    db->ict = ipcount_init();
    db->pool = session_pool_init();
    db->opera = get_opera(name);
    db->ti = (http_info *)malloc(DATABASE_MAX_SESSION * sizeof(http_info));
    db->pti = (http_info **)malloc(DATABASE_MAX_SESSION * sizeof(http_info *));
    db->ri = (http_info **)malloc(DATABASE_MAX_REPORT * sizeof(http_info *));
    db->ri_timeout = (http_info **)malloc(DATABASE_MAX_REPORT * sizeof(http_info *));
    db->ip_log = (log_content *)malloc(DATABASE_MAX_LOG * sizeof(log_content));
    db->session_log = (log_content *)malloc(DATABASE_MAX_LOG * sizeof(log_content));
    memset(db->ti, 0, DATABASE_MAX_SESSION * sizeof(http_info));
    memset(db->pti, 0, DATABASE_MAX_SESSION * sizeof(http_info *));
    memset(db->ri, 0, DATABASE_MAX_REPORT * sizeof(http_info *));
    memset(db->ri_timeout, 0, DATABASE_MAX_REPORT * sizeof(http_info *));
    memset(db->ip_log, 0, DATABASE_MAX_LOG * sizeof(log_content));
    memset(db->session_log, 0, DATABASE_MAX_LOG * sizeof(log_content));
    for(j = 0; j < DATABASE_MAX_SESSION; j++)
    {
        db->pti[j] = &(db->ti[j]);
    }
    db->rij = db->rtj = DATABASE_MAX_REPORT - 1;
    db->slj = DATABASE_MAX_LOG - 1;
    snprintf(db->name, sizeof(db->name), "%s\0", name);
    for(j = 0; j < g_log_target_num; j++)
    {
        if((db->log_fd[j] = socket(AF_INET, SOCK_DGRAM, 0)) == -1)
            goto err;
        if(-1 == fcntl(db->log_fd[j], F_SETFL, O_NONBLOCK))
            goto err;
    }
    return db;
err:
    free_database(db);
    return NULL;
}

static int free_logger(log_target *lt)
{
    free(lt);
    return 1;
}

static log_target *init_logger(unsigned int *ip, unsigned short port, unsigned char type)
{
    log_target *lt = (log_target *)malloc(sizeof(log_target));
    if(lt)
    {
        memset(lt, 0, sizeof(log_target));
        lt->target.sin_family = AF_INET;
        lt->target.sin_port = htons(port);
        lt->target.sin_addr.s_addr = str_2_ip(ip);
        lt->log_type = type;
        return lt;
    }
    return NULL;
}

static int free_reader(reader_t *reader)
{
    efio_tini(reader->fd);
    free(reader);
    return 1;
}

static reader_t *init_reader(unsigned int id, database *db, unsigned char *dev, unsigned int flag)
{
    reader_t *reader;
    reader = (reader_t *)malloc(sizeof(reader_t));
    memset(reader, 0, sizeof(reader_t));
    reader->db = db;
    reader->flag = flag;
    snprintf(reader->dev, sizeof(reader->dev), "%s", dev);
    reader->fd = efio_init(dev, EF_CAPTURE_NETMAP, EF_ENABLE_READ, 1);
    reader->id = id;
    reader->fin = READER_MAX_SLOT - 1;
    return reader;
}

static free_worker()
{
    int i;
    for(i = 0; i < g_worker_num; i++)
        free(g_worker[i]);
    return 1;
}

static int init_worker(unsigned int worker_total)
{
    int i, j;
    g_worker_num = worker_total;
    for(i = 0; i < g_worker_num; i++)
    {
        worker_t *worker = (worker_t *)malloc(sizeof(worker_t));
        if(!worker)
            goto err;
        worker->id = i;
        for(j = 0; j < g_reader_num; j++)
            worker->line[j].fin = LINE_LENGTH - 1;
        g_worker[i] = worker;
    }
    return 1;
err:
    return 0;
}

static int tini()
{
    //free database
    int i;
    for(i = 0; i < g_database_num; i++)
        free_database(g_db[i]);
    for(i = 0; i < g_reader_num; i++)
        free_reader(g_reader[i]);
    for(i = 0; i < g_log_target_num; i++)
        free(g_log_target[i]);
    free_worker();
}

static int config(const char *conf_file)
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

	if(conf_ifkey(cd, "log"))
	{
        char type[32];
		char ip[32];
		char port[32];

        i = 1;
    build_log_target:
        conf_getval(cd, "log", ip, sizeof(ip), i++);
        conf_getval(cd, "log", port, sizeof(port), i++);
        conf_getval(cd, "log", type, sizeof(type), i++);

        if(strlen(ip))
        {
            if(!strlen(port) || !strlen(type))
                goto end;
            if(!str_2_ip(ip))
                goto end;
            if(atoi(port) < 0 || atoi(port) > 0xffff)
                goto end;
            if(atoi(type) < 0 || atoi(type) > (LOG_TYPE_IP | LOG_TYPE_SESSION))
                goto end;

            fprintf(stderr, "log %s %s %s\n", ip, port, type);

            g_log_target[g_log_target_num++] = init_logger(ip, atoi(port), atoi(type));
            goto build_log_target;
        }
	}

	if(conf_ifkey(cd, "outbound"))
    {
        char name[256] = {0};
        char dev1[32] = {0};
        char dev2[32] = {0};
        char dev3[32] = {0};
        i = 1;
    build_database:
        fprintf(stderr, "build database!\n");
        while(conf_getval(cd, "outbound", name, sizeof(name), i++))
        {
            fprintf(stderr, "db %s\n", name);
            if(conf_ifkey(cd, name))
            {
                database *db = init_database(g_database_num, name);
                g_db[g_database_num++] = db;
                conf_getval(cd, name, dev1, sizeof(dev1), 1);
                conf_getval(cd, name, dev2, sizeof(dev2), 2);
                conf_getval(cd, name, dev3, sizeof(dev3), 3);
                fprintf(stderr, "%s %s %s\n", dev1, dev2, dev3);
                if(strlen(dev3))
                {
                    fprintf(stderr, "err 1\n");
                    goto end;
                }
                for(j = 0; j < g_reader_num; j++)
                {
                    if(!memcmp(g_reader[j]->dev, dev1, strlen(g_reader[j]->dev)))
                    {
                        fprintf(stderr, "err 2\n");
                        goto end;
                    }
                    if(!memcmp(g_reader[j]->dev, dev2, strlen(g_reader[j]->dev)))
                    {
                        fprintf(stderr, "err 3\n");
                        goto end;
                    }
                    if(!memcmp(dev1, dev2, strlen(dev1)))
                    {
                        fprintf(stderr, "err 4\n");
                        goto end;
                    }
                }
                if(strlen(dev1))
                {
                    if(strlen(dev2))
                    {
                        fprintf(stderr, "pos 1!\n");
                        g_reader[g_reader_num] = init_reader(g_reader_num, db, dev1, READER_FLAG_INBOUND);
                        g_reader_num++;
                        g_reader[g_reader_num] = init_reader(g_reader_num, db, dev2, READER_FLAG_OUTBOUND);
                        g_reader_num++;
                    }
                    else
                    {
                        g_reader[g_reader_num] = init_reader(g_reader_num, db, dev1, READER_FLAG_ALL);
                        g_reader_num++;
                    }
                }
                else
                    goto end;
            }
        }
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

	g_run = 0;
}


int main(int argc, char *argv[])
{
    int ch;
    int i, j;
    unsigned char *conf_file = "detect.conf";//NULL;
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
        if(!config(conf_file))
        {
            fprintf(stderr, "please check conf file!\n");
            goto over;
        }
        init_worker(g_reader_num * 3);
        num_init();
        signal(SIGINT, sigint_h);
        signal(SIGTERM, sigint_h);
        signal(SIGKILL, sigint_h);
        g_run = 1;
        c_run = 1;
        for(i = 0; i < g_reader_num; i++)
            pthread_create(&(g_reader[i]->thread), NULL, read, (void *)(g_reader[i]));
        for(i = 0; i < g_worker_num; i++)
            pthread_create(&(g_worker[i]->thread), NULL, work, (void *)(g_worker[i]));
        for(i = 0; i < g_database_num; i++)
        {
            pthread_create(&(g_db[i]->collecter), NULL, db_collecter, (void *)(g_db[i]));
            pthread_create(&(g_db[i]->sender), NULL, db_sender, (void *)(g_db[i]));
        }
        pthread_create(&control_t, NULL, control, NULL);

        fprintf(stderr, "wait!\n");
        for(i = 0; i < g_reader_num; i++)
            pthread_join(g_reader[i]->thread, NULL);
        fprintf(stderr, "reader over!\n");
        for(i = 0; i < g_worker_num; i++)
            pthread_join(g_worker[i]->thread, NULL);
        fprintf(stderr, "worker over!\n");
        for(i = 0; i < g_database_num; i++)
        {
            pthread_join(g_db[i]->collecter, NULL);
            fprintf(stderr, "collecter over!\n");
            pthread_join(g_db[i]->sender, NULL);
            fprintf(stderr, "sender over!\n");
        }
        fprintf(stderr, "db over!\n");
        pthread_join(control_t, NULL);
        fprintf(stderr, "control over!\n");
    }
    //else
        //fprintf(stderr, "cannot create run session track process!\n");
over:
    tini();
    fprintf(stderr, "detect over!\n");
    return 0;
}

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

extern unsigned long base_time;

unsigned long no_process_detail = 0;


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
    while(g_run)
    {
        for(i = 0; i < g_reader_num; i++)
        {
            unsigned long tmp_pkg, tmp_flow;
            reader_t *reader = g_reader[i];
            database *db = reader->db;
            tmp_pkg = reader->pkg;
            tmp_flow = reader->flow;
            switch(reader->flag)
            {
                case READER_FLAG_INBOUND:
                    db->in_pps = tmp_pkg - reader->l_pkg;
                    db->in_bps = tmp_flow - reader->l_flow;
                    break;
                case READER_FLAG_OUTBOUND:
                    db->out_pps = tmp_pkg - reader->l_pkg;
                    db->out_bps = tmp_flow - reader->l_flow;
                    break;
                case READER_FLAG_ALL:
                    break;
                default:;
            }
            reader->l_pkg = tmp_pkg;
            reader->l_flow = tmp_flow;
        }
        fprintf(stderr, "database:");
        for(i = 0; i < g_database_num; i++)
            fprintf(stderr, "%lu pps %lu bps |%u %u %u %u %u %u %u %u %llu|", g_db[i]->in_pps + g_db[i]->out_pps, (g_db[i]->in_bps + g_db[i]->out_bps) * 8,
                        g_db[i]->rii, g_db[i]->rij, g_db[i]->rti, g_db[i]->rtj,
                        g_db[i]->pti_cur, g_db[i]->pti_rec, g_db[i]->sli, g_db[i]->slj, g_db[i]->ip_total);
        fprintf(stderr, "\n");
        fprintf(stderr, "reader:");
        for(i = 0; i < g_reader_num; i++)
            fprintf(stderr, "|%d %d %d|", g_reader[i]->cur, g_reader[i]->fin, g_reader[i]->max_read);
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
        reader->max_read = max_read;
        max_read = max_read / 2;
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
            reads = efio_read(reader->fd, &reader->slot[cur], max_read, 1);
            reader->pkg += reads;
            for(i = 0; i < reads; i++)
            {
                void *pkg = reader->slot[cur + i].buf;
                unsigned int len = reader->slot[cur + i].len;
                unsigned int key = 0;
                work_line *line = NULL;

                reader->flow += len;
                if(IF_IP(pkg))
                {
                    unsigned int sip = GET_IP_SIP(pkg);
                    unsigned int dip = GET_IP_DIP(pkg);
                    key = (sip ^ dip) % g_worker_num;
                    ipcount_add_pkg(reader->db->ict, pkg, len, reader->flag, 0);
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
            tasks = tasks / 2;
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

static int rec_http_detail(database *db, http_info *detail)
{
    lock(&db->lock);
    db->pti[db->pti_rec] = detail;
    db->pti_rec = (db->pti_rec + 1 == DATABASE_MAX_SESSION) ? 0 : (db->pti_rec + 1);
    detail->use = 0;
    unlock(&db->lock);
    return 1;
}

static int fin_http_detail(database *db, http_info *detail, int timeout)
{
    int fin_succ = 0;
    lock(&db->lock);
    if(!timeout)
    {
        unsigned int tmp = (db->rii + 1 == DATABASE_MAX_REPORT) ? 0 : (db->rii + 1);
        if(tmp != db->rij)
        {
            db->ri[db->rii] = detail;
            db->rii = tmp;
            fin_succ = 1;
        }
    }
    else
    {
        unsigned int tmp = (db->rti + 1 == DATABASE_MAX_REPORT) ? 0 : (db->rti + 1);
        if(tmp != db->rtj)
        {
            db->ri_timeout[db->rti] = detail;
            db->rti = tmp;
            fin_succ = 1;
        }
    }
    unlock(&db->lock);
    if(!fin_succ)
    {
        rec_http_detail(db, detail);
        no_process_detail++;
    }
    return 1;
}

static int timeout_process(session *s)
{
	http_info *detail = (http_info *)session_get_detail(s);
	if(detail)
	{
        database *db = g_db[detail->db_id];
        int flow = session_get_flow(s);
        detail->stats = TCP_STAT_TIMEOUT;
        ipcount_add_session(db->ict, detail->sip, detail->dip, IPCOUNT_SESSION_TYPE_TIMEOUT, flow);
        fin_http_detail(db, detail, 1);
	}
	return 0;
}

static int attack_process(ip_count_t *ict, unsigned int ip, unsigned int attack_type, unsigned char attacking,
                            unsigned long pps, unsigned long bps)
{
    int i;
    int ret = 0;
    int find = 0;
    database *db = NULL;
    attack_event *attack = NULL;
    for(i = 0; i < g_database_num; i++)
    {
        if(g_db[i]->ict == ict)
        {
            db = g_db[i];
            break;
        }
    }
    if(!db)
        goto done;
    for(i = 0; i < db->attack_count; i++)
    {
        attack = &db->attack[i];
        if((!attack->attack_over) && (attack->ip == ip) && (attack_type == attack->attack_type))
        {
            find = 1;
            break;
        }
    }
    if(find)
    {
        attack->attack_cur_pps = pps;
        attack->attack_cur_bps = bps;
        if(attacking)
        {
            if(pps > attack->attack_max_pps)
                attack->attack_max_pps = pps;
            if(pps > attack->attack_max_bps)
                attack->attack_max_bps = bps;
        }
        else
        {
            attack->attack_over = time(NULL);
        }
    }
    else if(attacking)
    {
        if(db->attack_count < MAX_ATTACK_EVENT)
        {
            attack = &db->attack[db->attack_count];
            attack->attack_begin = time(NULL);
            attack->ip = ip;
            attack->attack_type = attack_type;
            attack->attack_cur_pps = attack->attack_max_pps = pps;
            attack->attack_cur_bps = attack->attack_max_bps = bps;
            switch(attack_type)
            {
                case IPCOUNT_ATTACK_SYN_FLOOD:
                    snprintf(attack->attack_name, sizeof(attack->attack_name), "%s", "syn_flood\0");
                    break;
                case IPCOUNT_ATTACK_UDP_FLOOD:
                    snprintf(attack->attack_name, sizeof(attack->attack_name), "%s", "udp_flood\0");
                    break;
                case IPCOUNT_ATTACK_ICMP_FLOOD:
                    snprintf(attack->attack_name, sizeof(attack->attack_name), "%s", "icmp_flood\0");
                    break;
                default:
                    snprintf(attack->attack_name, sizeof(attack->attack_name), "%s", "unknow attack\0");
            }
            db->attack_count++;
        }
    }
    ret = 1;
done:
    return ret;
}

#define STR_OVERFLOW(pkg, len, str) ((str < pkg) || (str > pkg + len))
static int pkg_process(database *db, reader_t *reader, ef_slot *slot)
{
    session_pool *pool = db->pool;
    session *s;
    ip_count_t *ict = db->ict;
    void *pkg = slot->buf;
    unsigned short len = slot->len;
    http_info *detail = NULL;
    char http_host[100];

    if(IF_IP(pkg))
    {
        struct iphdr *iph = P_IPP(pkg);
        if(IF_TCP(pkg))
        {
            	struct tcphdr *tph = P_TCPP(pkg);
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
                        	detail->syn_time = base_time;
                        	detail->stats = TCP_STAT_CREAT;
                        	detail->sip = GET_IP_SIP(pkg);
                        	detail->dip = GET_IP_DIP(pkg);
                        	detail->sport = GET_IP_SPORT(pkg);
                        	detail->dport = GET_IP_DPORT(pkg);
                        	session_set_detail(s, detail);
                        	session_set_timeout(s, TCP_TIMEOUT);
                        	session_set_timeout_callback(s, (void *)timeout_process);
                        	ipcount_add_session(ict, detail->sip, detail->dip, IPCOUNT_SESSION_TYPE_NEW, 0);
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
                        	detail->first_ack_time = base_time;
                        	detail->stats = TCP_STAT_CONN;
                    	}
                    	detail->last_ack_time = base_time;

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
                        if(!detail->protocol)
                        {
                            detail->protocol = SESSION_PROTO_HTTP;
                            ipcount_add_session(ict, detail->sip, detail->dip, IPCOUNT_SESSION_TYPE_HTTP, 0);
                        }
                    	url_begin = http_info;
                    	url_end = strstr(url_begin, "HTTP");
                    	if(!url_end)
                            url_end = strstr(url_begin, "\r\n");

                        if(url_end && !STR_OVERFLOW(pkg, len, url_end))
                        {
                            url_len = url_end - url_begin;
                            host_begin = strstr(url_end, "Host:");
                        }
                        else
                        {
                            url_len = len - ((unsigned int)http_info - (unsigned int)pkg);
                        }

                    	if(host_begin && !STR_OVERFLOW(pkg, len, host_begin))
                    	{
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
                        if(url_len > 128)
                            url_len = 128;


                    	if(detail->url_len + host_len + url_len + 3 < HTTP_URL_LEN)
                    	{
                            detail->url[detail->url_len++] = '|';
                            memcpy(&detail->url[detail->url_len], http_host, host_len);
                            detail->url_len += host_len;
                            detail->url[detail->url_len++] = ' ';
                            memcpy(&detail->url[detail->url_len], http_info, url_len);
                            detail->url_len += url_len;
                            detail->url[detail->url_len++] = '|';
                        }
                        else
                        {
                            //char tmp[2048];
                            //int tmp_len = len - ((unsigned int)http_info - (unsigned int)pkg);
                            //memcpy(tmp, http_info, tmp_len);
                            //tmp[tmp_len] = 0;
                            //fprintf(stderr, "%s \n----\n %s %u %u\n", tmp, detail->url, url_len, host_len);
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
                            detail->fin_time = base_time;
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
                        ipcount_add_session(ict, detail->sip, detail->dip, IPCOUNT_SESSION_TYPE_CLOSE, flow);
                        fin_http_detail(db, detail, 0);
                        //rec_http_detail(db, detail);
                        session_close(s);
                    }
                }
        	}
        	//if(detail && detail->protocol == SESSION_PROTO_HTTP)
                //ipcount_add_pkg(ict, pkg, len, reader->flag, IPCOUNT_SESSION_TYPE_HTTP);
            //else
                //ipcount_add_pkg(ict, pkg, len, reader->flag, 0);
        fin_process:
            return 1;
    }
}

static int db_opera(database *db)
{
    detect_opera *opera = db->opera;
    if(opera && opera->code)
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
            case OPERA_GET_TOP_PPS_IN:
                opera->result = ipcount_get_top_ip(ict, IPCOUNT_TOP_PPS_IN, opera->top, 100);
                break;
            case OPERA_GET_TOP_PPS_OUT:
                opera->result = ipcount_get_top_ip(ict, IPCOUNT_TOP_PPS_OUT, opera->top, 100);
                break;
            case OPERA_GET_TOP_BPS_IN:
                opera->result = ipcount_get_top_ip(ict, IPCOUNT_TOP_BPS_IN, opera->top, 100);
                break;
            case OPERA_GET_TOP_BPS_OUT:
                opera->result = ipcount_get_top_ip(ict, IPCOUNT_TOP_BPS_OUT, opera->top, 100);
                break;
            case OPERA_GET_TOP_NEW_SESSION:
                opera->result = ipcount_get_top_ip(ict, IPCOUNT_TOP_NEW_SESSION, opera->top, 100);
                break;
            case OPERA_GET_TOP_NEW_HTTP:
                opera->result = ipcount_get_top_ip(ict, IPCOUNT_TOP_NEW_HTTP, opera->top, 100);
                break;
            case OPERA_GET_ALL:
                opera->result = ipcount_get_all_ip(ict, opera->ip, DATABASE_MAX_IP);
                break;
            default:;
        }
        opera->code = 0;
    }
}

static int db_recorder(void *arg)
{
    database *db = (database *)arg;
    ip_data *id = NULL;
    top_data *td = NULL;
    char file_gen[256] = {0};
    char file_data[256] = {0};
    char file_top[256] = {0};
    char file_attack[256] = {0};
    char buf[2048] = {0};
    char ip_str[32] = {0};
    int log_gen = 0, log_data = 0, log_top = 0, log_attack = 0;
    int ip_total = 0, top_total = 0;
    int top_type[6] = {IPCOUNT_TOP_PPS_IN, IPCOUNT_TOP_PPS_OUT, IPCOUNT_TOP_BPS_IN, IPCOUNT_TOP_BPS_OUT,
                        IPCOUNT_TOP_NEW_SESSION, IPCOUNT_TOP_NEW_HTTP};
    int *top_str[6] = {"ppsin", "ppsout", "bpsin", "bpsout", "session", "http"};

    id = (ip_data *)malloc(DATABASE_MAX_IP * sizeof(ip_data));
    td = (top_data *)malloc(100 * sizeof(top_data));
    snprintf(file_gen, sizeof(file_gen), "/dev/shm/gen_%s", db->name);
    snprintf(file_data, sizeof(file_data), "/dev/shm/data_%s", db->name);
    snprintf(file_top, sizeof(file_top), "/dev/shm/top_%s", db->name);
    snprintf(file_attack, sizeof(file_attack), "/dev/shm/attack_%s", db->name);
    while(g_run && db && id && td)
    {
        log_gen = open(file_gen, O_RDWR | O_CREAT, S_IRWXU | S_IRWXG | S_IRWXO);
        log_data = open(file_data, O_RDWR | O_CREAT, S_IRWXU | S_IRWXG | S_IRWXO);
        log_top = open(file_top, O_RDWR | O_CREAT, S_IRWXU | S_IRWXG | S_IRWXO);
        log_attack = open(file_attack, O_RDWR | O_CREAT, S_IRWXU | S_IRWXG | S_IRWXO);

        if(log_gen != -1)
        {
            flock(log_gen, LOCK_EX);
            snprintf(buf, sizeof(buf), "%lu %lu %lu %lu %lu\n\0", db->in_pps, db->in_bps, db->out_pps, db->out_bps, db->ip_total);
            write(log_gen, &buf, strlen(buf));
            flock(log_gen, LOCK_UN);
            close(log_gen);
        }

        if(log_data != -1)
        {
            int len = sizeof(ip_data);
            ip_total = ipcount_get_all_ip(db->ict, id, DATABASE_MAX_IP);
            flock(log_data, LOCK_EX);
            write(log_data, &ip_total, sizeof(int));
            write(log_data, &len, sizeof(int));
            write(log_data, (char *)id, ip_total * sizeof(ip_data));
            flock(log_data, LOCK_UN);
            close(log_data);
        }
        if(log_top != -1)
        {
            int i, j;
            flock(log_top, LOCK_EX);
            for(i = 0; i < 6; i++)
            {
                top_total = ipcount_get_top_ip(db->ict, top_type[i], td, 100);
                snprintf(buf, sizeof(buf), "%s %u\n\0", top_str[i], top_total);
                write(log_top, &buf, strlen(buf));
                for(j = 0; j < top_total; j++)
                {
                    ip_2_str(td[j].ip, ip_str);
                    snprintf(buf, sizeof(buf), "%s %lu\n\0", ip_str, td[j].val);
                    write(log_top, &buf, strlen(buf));
                }
            }
            snprintf(buf, sizeof(buf), "end\n\0");
            write(log_top, &buf, strlen(buf));
            flock(log_top, LOCK_UN);
            close(log_top);
        }
        if(log_attack)
        {
            int i, j;
            static struct tm *date = NULL;
            unsigned char date_str[64];
            flock(log_attack, LOCK_EX);

            for(i = 0; i < db->attack_count; i++)
            {
                attack_event *attack = &db->attack[i];
                ip_2_str(attack->ip, ip_str);
                date = localtime(&attack->attack_begin);
                snprintf(date_str, sizeof(date_str), "%d-%02d-%02d %02d:%02d:%02d",
                            date->tm_year+1900, date->tm_mon+1, date->tm_mday, date->tm_hour, date->tm_min, date->tm_sec);
                snprintf(buf, sizeof(buf), "%s %s %s %lu %lu %lu %lu %s %lu\n\0", ip_str, attack->attack_over ? "over" : "attacking", attack->attack_name,
                            attack->attack_cur_pps, attack->attack_cur_bps, attack->attack_max_pps, attack->attack_max_bps, date_str,
                            attack->attack_over ? (attack->attack_over - attack->attack_begin) : (time(NULL) - attack->attack_begin));
                write(log_attack, &buf, strlen(buf));
            }
            snprintf(buf, sizeof(buf), "end\n\0");
            write(log_attack, &buf, strlen(buf));
            flock(log_attack, LOCK_UN);
            close(log_attack);
        }
        sleep(1);
    }

    if(id)
        free(id);
    if(td)
        free(td);

    return 0;
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

        if(now - ip_log_time >= 1000000)
        {
            int i = db->ili;
            int j = db->ilj;
            int max_log = (i >= j) ? (DATABASE_MAX_LOG - i + j - 1) : (j - i - 1);
            max_log = max_log / 2;
            db->ip_total = ipcount_get_all_ip(db->ict, ip_detail, DATABASE_MAX_IP);
            i = 0;
            while((i < db->ip_total) && max_log)
            {
                char *log_buf = db->ip_log[db->ili].str;
                int log_len = 0;

                memcpy(&log_buf[log_len], log_header, log_header_len); log_len += log_header_len;
                memcpy(&log_buf[log_len], db->name, strlen(db->name)); log_len += strlen(db->name);
                log_buf[log_len++] = ' ';
                memcpy(&log_buf[log_len], "{\"ip_info\":[", 12); log_len += 12;
                j = 0;
                while((log_len < 1000) && (i < db->ip_total))
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

                    memcpy(&log_buf[log_len], "\"session_timeout\":\"", 19); log_len += 19;
                    log_len += num_2_str(ip_detail[i].session_timeout, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = ',';

                    memcpy(&log_buf[log_len], "\"session_http\":\"", 16); log_len += 16;
                    log_len += num_2_str(ip_detail[i].http_session, &log_buf[log_len]);
                    log_buf[log_len++] = '"';log_buf[log_len++] = '}';
                    i++;j++;
                }
                log_buf[log_len++] = ']';log_buf[log_len++] = '}';log_buf[log_len] = 0;
                db->ip_log[db->ili].len = log_len;
                db->ili = (db->ili + 1 == DATABASE_MAX_LOG) ? 0 : (db->ili + 1);
                max_log--;
            }
            ip_log_time = now;
        }

        if(1)
        {
            #if 1
            int i, j, max_log, max_normal_detail, max_timeout_detail;
            i = db->sli;
            j = db->slj;
            max_log = (i >= j) ? (DATABASE_MAX_LOG - i + j - 1) : (j - i - 1);
            i = db->rii;
            j = db->rij;
            max_normal_detail = (j >= i) ? (DATABASE_MAX_REPORT - j + i - 1) : (i - j - 1);
            i = db->rti;
            j = db->rtj;
            max_timeout_detail = (j >= i) ? (DATABASE_MAX_REPORT - j + i - 1) : (i - j - 1);
            max_log = max_log / 2;
            max_normal_detail = max_normal_detail / 2;
            max_timeout_detail = max_timeout_detail / 2;
            if((!max_normal_detail && !max_timeout_detail) || !max_log)
            {
                usleep(0);
                continue;
            }
            while((max_normal_detail || max_timeout_detail) && max_log)
            {
                char *session_log;
                int log_len = 0;
                http_info *detail = NULL;

                if(max_timeout_detail)
                {
                    db->rtj = (db->rtj + 1 == DATABASE_MAX_REPORT) ? 0 : (db->rtj + 1);
                    detail = db->ri_timeout[db->rtj];
                    max_timeout_detail--;
                }
                else
                {
                    db->rij = (db->rij + 1 == DATABASE_MAX_REPORT) ? 0 : (db->rij + 1);
                    detail = db->ri[db->rij];
                    max_normal_detail--;
                }
                if(!detail)
                {
                    fprintf(stderr, "error in collecter detail is null!\n");
                    g_run = 0;
                    usleep(0);
                    continue;
                }
                session_log = db->session_log[db->sli].str;
                memcpy(&session_log[log_len], log_header, log_header_len); log_len += log_header_len;
                memcpy(&session_log[log_len], db->name, strlen(db->name)); log_len += strlen(db->name);
                session_log[log_len++] = ' ';

                memcpy(&session_log[log_len], "{\"session_info\":{", 17); log_len += 17;
                memcpy(&session_log[log_len], "\"sip\":\"", 7); log_len += 7;
                log_len += ip_2_str(detail->sip, &session_log[log_len]);
                session_log[log_len++] = '"'; session_log[log_len++] = ',';

                memcpy(&session_log[log_len], "\"dip\":\"", 7); log_len += 7;
                log_len += ip_2_str(detail->dip, &session_log[log_len]);
                session_log[log_len++] = '"'; session_log[log_len++] = ',';

                memcpy(&session_log[log_len], "\"sport\":\"", 9); log_len += 9;
                log_len += num_2_str(detail->sport, &session_log[log_len]);
                session_log[log_len++] = '"'; session_log[log_len++] = ',';

                memcpy(&session_log[log_len], "\"dport\":\"", 9); log_len += 9;
                log_len += num_2_str(detail->dport, &session_log[log_len]);
                session_log[log_len++] = '"'; session_log[log_len++] = ',';

                switch(detail->stats)
                {
                    case TCP_STAT_FIN:
                        memcpy(&session_log[log_len], "\"stats\":\"normal\",", 17);
                        log_len += 17;
                        break;
                    case TCP_STAT_ERROR:
                        memcpy(&session_log[log_len], "\"stats\":\"error\",", 16);
                        log_len += 16;
                        break;
                    case TCP_STAT_TIMEOUT:
                        memcpy(&session_log[log_len], "\"stats\":\"timeout\",", 18);
                        log_len += 18;
                        break;
                    default:;
                }

                detail->conn_time = (detail->syn_time && (detail->first_ack_time > detail->syn_time)) ? (detail->first_ack_time - detail->syn_time) : 0;
                detail->tran_time = detail->last_ack_time - detail->first_ack_time;
                detail->close_time = (detail->last_ack_time && (detail->fin_time > detail->last_ack_time)) ? (detail->fin_time - detail->last_ack_time) : 0;

                memcpy(&session_log[log_len], "\"conn_time\":\"", 13); log_len += 13;
                log_len += num_2_str(detail->conn_time/1000, &session_log[log_len]);
                session_log[log_len++] = '"'; session_log[log_len++] = ',';

                memcpy(&session_log[log_len], "\"tran_time\":\"", 13); log_len += 13;
                log_len += num_2_str(detail->tran_time/1000, &session_log[log_len]);
                session_log[log_len++] = '"'; session_log[log_len++] = ',';

                memcpy(&session_log[log_len], "\"close_time\":\"", 14); log_len += 14;
                log_len += num_2_str(detail->close_time/1000, &session_log[log_len]);
                session_log[log_len++] = '"'; session_log[log_len++] = ',';

                memcpy(&session_log[log_len], "\"send\":\"", 8); log_len += 8;
                log_len += num_2_str(detail->max_seq - detail->min_seq, &session_log[log_len]);
                session_log[log_len++] = '"'; session_log[log_len++] = ',';
                memcpy(&session_log[log_len], "\"recv\":\"", 8); log_len += 8;
                log_len += num_2_str(detail->max_ack - detail->min_ack, &session_log[log_len]);
                session_log[log_len++] = '"'; session_log[log_len++] = ',';

                switch(detail->protocol)
                {
                    case SESSION_PROTO_HTTP:
                        memcpy(&session_log[log_len], "\"type\":\"http\",", 14);
                        log_len += 14;
                        memcpy(&session_log[log_len], "\"url\":\"", 7); log_len += 7;
                        if(detail->url_len)
                            memcpy(&session_log[log_len], detail->url, detail->url_len); log_len += detail->url_len;
                        session_log[log_len++] = '"'; session_log[log_len++] = '}';
                        break;
                    default:
                        memcpy(&session_log[log_len], "\"type\":\"unknow\"}", 16);
                        log_len += 16;
                }
                session_log[log_len++] = '}'; session_log[log_len] = 0;
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
    char buf[32];
    unsigned long time;

    if(1)
	{
        unsigned long mask = 1;
		mask = mask << (g_reader_num + g_worker_num + db->id);
		sched_setaffinity(0, sizeof(mask), &mask);
	}
    while(g_run)
    {
        int i = 0, j = 0, max_send = 0, send_len = 0, ret = 0;

        time = base_time;
        send_total = 0;

        for(i = 0; i < g_log_target_num; i++)
        {
            if(g_log_target[i]->log_type && (g_log_target[i]->net_type == LOG_NET_TCP) && !g_log_target[i]->conn && (time - g_log_target[i]->last_reply > LOG_NET_TIMEOUT))
            {
                if(db->log_fd[i] > 0)
                    close(db->log_fd[i]);
                db->log_fd[i] = socket(AF_INET, SOCK_STREAM, 0);
                if(-1 != db->log_fd[i])
                {
                    if(-1 != fcntl(db->log_fd[i], F_SETFL, O_NONBLOCK))
                    {
                        connect(db->log_fd[i], (void *)&g_log_target[i]->target, sizeof(g_log_target[i]->target));
                        g_log_target[i]->conn = 1;
                        g_log_target[i]->last_reply = time;
                    }
                    else
                        close(db->log_fd[i]);
                }
            }
        }

        i = db->ili;
        j = db->ilj;
        max_send = (j >= i) ? (DATABASE_MAX_LOG - j + i - 1) : (i - j - 1);
        max_send = max_send / 2;
        if(max_send)
        {
            for(i = 0; i < g_log_target_num; i++)
            {
                log_target *target = g_log_target[i];
                if(target->log_type & LOG_TYPE_IP)
                {
                    unsigned int ilj = db->ilj;
                    unsigned char *log_str = NULL;
                    unsigned int log_len = 0;
                    for(j = 0; (j < max_send) && (target->conn); j++)
                    {
                        ilj = (ilj + 1 == DATABASE_MAX_LOG) ? 0 : (ilj + 1);
                        log_str = db->ip_log[ilj].str;
                        log_len = db->ip_log[ilj].len;
                        send_len = 0;
                        while(send_len < log_len)
                        {
                            if(target->net_type == LOG_NET_TCP)
                                ret = send(db->log_fd[i], &(log_str[send_len]), log_len - send_len, 0);
                            else
                                ret = sendto(db->log_fd[i], &(log_str[send_len]), log_len - send_len, 0, &(target->target), sizeof(struct sockaddr_in));
                            if(ret > 0)
                                send_len += ret;
                            else if(ret < 0)
                            {
                                if((target->net_type == LOG_NET_TCP) && (errno != EAGAIN) && (errno != EINTR))
                                {
                                    target->conn = 0;
                                    break;
                                }
                            }
                            else if(ret == 0)
                            {
                                if(target->net_type == LOG_NET_TCP)
                                {
                                    target->conn = 0;
                                    break;
                                }
                            }
                        }
                        send_total++;
                    }
                }
            }
            db->ilj = (db->ilj + max_send) % DATABASE_MAX_LOG;
        }

        i = db->sli;
        j = db->slj;
        max_send = (j >= i) ? (DATABASE_MAX_LOG - j + i - 1) : (i - j - 1);
        max_send = max_send / 2;
        if(max_send)
        {
            for(i = 0; i < g_log_target_num; i++)
            {
                log_target *target = g_log_target[i];
                if(target->log_type & LOG_TYPE_SESSION)
                {
                    unsigned int slj = db->slj;
                    unsigned char *log_str = NULL;
                    unsigned int log_len = 0;
                    for(j = 0; (j < max_send) && (target->conn); j++)
                    {
                        slj = (slj + 1 == DATABASE_MAX_LOG) ? 0 : (slj + 1);
                        log_str = db->session_log[slj].str;
                        log_len = db->session_log[slj].len;
                        send_len = 0;
                        while(send_len < log_len)
                        {
                            if(target->net_type == LOG_NET_TCP)
                                ret = send(db->log_fd[i], &(log_str[send_len]), log_len - send_len, 0);
                            else
                                ret = sendto(db->log_fd[i], &(log_str[send_len]), log_len - send_len, 0, &(target->target), sizeof(struct sockaddr_in));
                            if(ret > 0)
                                send_len += ret;
                            else if(ret < 0)
                            {
                                if((target->net_type == LOG_NET_TCP) && (errno != EAGAIN) && (errno != EINTR))
                                {
                                    target->conn = 0;
                                    break;
                                }
                            }
                            else if(ret == 0)
                            {
                                if(target->net_type == LOG_NET_TCP)
                                {
                                    target->conn = 0;
                                    break;
                                }
                            }
                        }
                        send_total++;
                    }
                }
            }
            db->slj = (db->slj + max_send) % DATABASE_MAX_LOG;
        }


        for(i = 0; i < g_log_target_num; i++)
        {
            if(g_log_target[i]->log_type && (g_log_target[i]->net_type == LOG_NET_TCP) && g_log_target[i]->conn)
            {
                ret = recv(db->log_fd[i], buf, sizeof(buf), 0);
                if((ret > 0) && !memcmp(buf, "ok", 2))
                {
                    buf[0] = buf[1] = buf[2] = 0;
                    g_log_target[i]->last_reply = time;
                }
                else if(time - g_log_target[i]->last_reply > LOG_NET_TIMEOUT)
                {
                    g_log_target[i]->conn = 0;
                }
            }
        }

        if(!send_total)
            usleep(0);
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
        fprintf(stderr, "get database opera err!\n");
		shmctl(id, IPC_RMID, NULL);
		opera = NULL;
	}
	else
	{
        opera->id = id;
    }
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
        if(db->attack)
            free(db->attack);
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
    ipcount_set_attack_cbk(db->ict, attack_process);
    db->pool = session_pool_init();
    db->opera = get_opera(name);
    db->attack = (attack_event *)malloc(MAX_ATTACK_EVENT * sizeof(attack_event));
    db->ti = (http_info *)malloc(DATABASE_MAX_SESSION * sizeof(http_info));
    db->pti = (http_info **)malloc(DATABASE_MAX_SESSION * sizeof(http_info *));
    db->ri = (http_info **)malloc(DATABASE_MAX_REPORT * sizeof(http_info *));
    db->ri_timeout = (http_info **)malloc(DATABASE_MAX_REPORT * sizeof(http_info *));
    db->ip_log = (log_content *)malloc(DATABASE_MAX_LOG * sizeof(log_content));
    db->session_log = (log_content *)malloc(DATABASE_MAX_LOG * sizeof(log_content));
    memset(db->attack, 0, MAX_ATTACK_EVENT * sizeof(attack_event));
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
    db->ilj = db->slj = DATABASE_MAX_LOG - 1;
    snprintf(db->name, sizeof(db->name), "%s\0", name);
    for(j = 0; j < g_log_target_num; j++)
    {
        if(g_log_target[j]->net_type == LOG_NET_UDP)
        {
            db->log_fd[j] = socket(AF_INET, SOCK_DGRAM, 0);
            if(-1 == db->log_fd[j])
                goto err;
            if(-1 == fcntl(db->log_fd[j], F_SETFL, O_NONBLOCK))
                goto err;
        }
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

static log_target *init_logger(char *net, char *ip, unsigned short port, unsigned char type)
{
    log_target *lt = (log_target *)malloc(sizeof(log_target));
    if(lt)
    {
        memset(lt, 0, sizeof(log_target));
        lt->target.sin_family = AF_INET;
        lt->target.sin_port = htons(port);
        lt->target.sin_addr.s_addr = str_2_ip(ip);
        if(!memcmp(net, "tcp", 3))
            lt->net_type = LOG_NET_TCP;
        else
        {
            lt->net_type = LOG_NET_UDP;
            lt->conn = 1;
        }
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
        char net[32];
        char type[32];
		char ip[32];
		char port[32];

        i = 1;
    build_log_target:
        conf_getval(cd, "log", net, sizeof(net), i++);
        conf_getval(cd, "log", ip, sizeof(ip), i++);
        conf_getval(cd, "log", port, sizeof(port), i++);
        conf_getval(cd, "log", type, sizeof(type), i++);

        if(strlen(ip))
        {
            if(!strlen(net) || !strlen(port) || !strlen(type))
                goto end;
            if(memcmp(net, "tcp", 3) && memcmp(net, "udp", 3))
                goto end;
            if(!str_2_ip(ip))
                goto end;
            if(atoi(port) < 0 || atoi(port) > 0xffff)
                goto end;
            if(atoi(type) < 0 || atoi(type) > (LOG_TYPE_IP | LOG_TYPE_SESSION))
                goto end;

            fprintf(stderr, "log %s %s %s %s\n", net, ip, port, type);

            g_log_target[g_log_target_num++] = init_logger(net, ip, atoi(port), atoi(type));
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
        signal(SIGPIPE, SIG_IGN);
        g_run = 1;
        for(i = 0; i < g_reader_num; i++)
            pthread_create(&(g_reader[i]->thread), NULL, read, (void *)(g_reader[i]));
        for(i = 0; i < g_worker_num; i++)
            pthread_create(&(g_worker[i]->thread), NULL, work, (void *)(g_worker[i]));
        for(i = 0; i < g_database_num; i++)
        {
            pthread_create(&(g_db[i]->recorder), NULL, db_recorder, (void *)(g_db[i]));
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
            pthread_join(g_db[i]->recorder, NULL);
            fprintf(stderr, "recorder over!\n");
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

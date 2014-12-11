#include <efext.h>
#include <efnet.h>
#include <efio.h>
#include <pthread.h>
#include <stdio.h>

#define MAX_COUNT_BUF       1024
#define IP_EACH_BUF_SIZE    1000000
#define IP_HASH_SIZE        0x1000000

#define MIN_UDP_IN          50000000
#define MIN_ICMP_IN         10000000
#define MIN_DNS_IN          10000
#define MIN_NEW_SESSION     1000
#define MIN_NEW_CONN        1000
#define MIN_NEW_HTTP        1000

#define CHECK_TYPE_FLOW     1
#define CHECK_TYPE_SOURCE   2

#define TOP_N                   10
#define MAX_DETAIL_VALUE        10
#define DETAIL_VALUE_PKG        0
#define DETAIL_VALUE_FLOW       1
#define DETAIL_VALUE_TCP        2
#define DETAIL_VALUE_UDP        3
#define DETAIL_VALUE_ICMP       4
#define DETAIL_VALUE_HTTP       5
#define DETAIL_VALUE_SESSION    6
#define DETAIL_VALUE_CONN       7
#define DETAIL_VALUE_ACK        8
#define DETAIL_VALUE_DNS        9

typedef struct _detail_value
{
    unsigned long in, out;
    unsigned long in_last, out_last;
    unsigned long in_ps, out_ps;
    unsigned long in_normal, out_normal;
    unsigned long in_avg, out_avg;
    unsigned char in_top, out_top;
    unsigned long hold_time, ab_time;
}detail_value;

typedef struct _check_info
{
    unsigned char chk;
    unsigned char type;
    unsigned long min;
    unsigned long attack_type;
}check_info;


typedef struct _ip_info
{
    unsigned int ip;
    unsigned long attack;
    unsigned long last_time;
    detail_value detail[MAX_DETAIL_VALUE];
    struct _ip_info *prev, *next, *prev_use, *next_use, *next_alive;
}ip_info;

typedef struct _top_info
{
    ip_info *key;
    unsigned int ip;
    unsigned long val;
}top_info;

struct _ip_count_t
{
    ip_info *buf[MAX_COUNT_BUF];
    ip_info *cur, *alive, *use_head, *use_tail, *max;
    ip_info **table, **end;
    top_info top[MAX_DETAIL_VALUE * 2][TOP_N + 1];
    volatile unsigned int stats;
    unsigned int buf_total, use_total, max_level;
    unsigned char lock, add_lock, del_lock, top_lock;
    unsigned char *table_lock;
    unsigned long time;
    unsigned long max_pps;
    void *attack_cbk;
    check_info ip_chk[MAX_DETAIL_VALUE];
    pthread_t counter;
    pthread_t timer;
};

int max_level = 0;

extern unsigned long base_time;



static void lock(char *lock)
{
    while(__sync_lock_test_and_set(lock, 1));
}

static void trylock(char *lock)
{
    return !(__sync_lock_test_and_set(lock, 1));
}

static void unlock(char *lock)
{
    __sync_lock_test_and_set(lock, 0);
}

int ipcount_lock(ip_count_t *ict)
{
    if(!ict)
        return 0;
    while(__sync_lock_test_and_set(&(ict->lock), 1));
}

int ipcount_unlock(ip_count_t *ict)
{
    if(!ict)
        return 0;
    __sync_lock_test_and_set(&(ict->lock), 0);
}

static void count_timer(void *arg)
{
    ip_count_t *ict = (ip_count_t *)arg;
    struct timeval now;
	while(ict->stats)
	{
		gettimeofday(&now, NULL);
		ict->time = now.tv_sec * 1000000 + now.tv_usec;
		usleep(0);
	}
}

static void top_down(top_info *top, int pos)
{
    int t1 = 0, t2 = 0, cmp = 0;
    ip_info *key;
    unsigned int ip;
    unsigned long val;

    while(1)
    {
        t1 = 2 * pos; ///左孩子(存在的话)
        t2 = t1 + 1;    ///右孩子(存在的话)
        if(t1 > TOP_N)    ///无孩子节点
            break;
        else
        {
            if(t2 > TOP_N)  ///只有左孩子
                cmp = t1;
            else
                cmp = top[t1].val > top[t2].val ? t2 : t1;

            if(top[pos].val > top[cmp].val) ///pos保存在子孩子中，数值较小者的位置
            {
                key = top[pos].key; top[pos].key = top[cmp].key;
                ip = top[pos].ip; top[pos].ip = top[cmp].ip;
                val = top[pos].val; top[pos].val = top[cmp].val;
                top[cmp].key = key; top[cmp].ip = ip; top[cmp].val = val;
                pos = cmp;
            }
            else
                break;
        }
    }
}

static void top_up(top_info *top, int pos)
{
    int father;
    ip_info *key;
    unsigned int ip;
    unsigned long val;
    while(pos != 1)
    {
        father = pos >> 1;
        if(top[pos].val < top[father].val)
        {
            key = top[pos].key; top[pos].key = top[father].key;
            ip = top[pos].ip; top[pos].ip = top[father].ip;
            val = top[pos].val; top[pos].val = top[father].val;
            top[father].key = key; top[father].ip = ip; top[father].val = val;
            pos = father;
        }
        else
            break;
    }
}

#if 1
static void count_thread(void *arg)
{
    int i, j;
    ip_count_t *ict = (ip_count_t *)arg;
    ip_info *use = NULL, *head = NULL, *tail = NULL;
    detail_value *detail = NULL;
    check_info *check = NULL;
    top_info *top = NULL;
    unsigned long tmp_in, tmp_out;
    unsigned char abnormal = 0;

    while(ict->stats)
    {
        lock(&(ict->add_lock));
        head = ict->use_head;
        tail = ict->use_tail;
        unlock(&(ict->add_lock));
        use = head;
        ict->time = base_time;
        //lock(&(ict->del_lock));

        while(use)
        {
            if(ict->time - use->last_time >= 1000000)
            {
                #if 0
                if(use->detail[DETAIL_VALUE_PKG].in_ps > ict->top[TOP_PPS_IN][1].val)
                {
                    detail->in_top = ict->time;
                    ict->top[TOP_PPS_IN][1].ip = use->ip;
                    ict->top[TOP_PPS_IN][1].val = use->detail[DETAIL_VALUE_PKG].in_ps;
                    top_repeat(ict->top[TOP_PPS_IN]);
                }
                if(use->detail[DETAIL_VALUE_PKG].out_ps > ict->top[TOP_PPS_OUT][1].val)
                {
                    use->in_top[TOP_PPS_OUT] = ict->time;
                    ict->top[TOP_PPS_OUT][1].ip = use->ip;
                    ict->top[TOP_PPS_OUT][1].val = use->detail[DETAIL_VALUE_PKG].out_ps;
                    top_repeat(ict->top[TOP_PPS_OUT]);
                }
                if(use->detail[DETAIL_VALUE_FLOW].in_ps > ict->top[TOP_BPS_IN][1].val)
                {
                    use->in_top[TOP_BPS_IN] = ict->time;
                    ict->top[TOP_BPS_IN][1].ip = use->ip;
                    ict->top[TOP_BPS_IN][1].val = use->detail[DETAIL_VALUE_FLOW].in_ps;
                    top_repeat(ict->top[TOP_BPS_IN]);
                }
                if(use->detail[DETAIL_VALUE_FLOW].out_ps > ict->top[TOP_BPS_OUT][1].val)
                {
                    use->in_top[TOP_BPS_OUT] = 1;
                    ict->top[TOP_BPS_OUT][1].ip = use->ip;
                    ict->top[TOP_BPS_OUT][1].val = use->detail[DETAIL_VALUE_FLOW].out_ps;
                    top_repeat(ict->top[TOP_BPS_OUT]);
                }
                if(use->detail[DETAIL_VALUE_SESSION].in_ps > ict->top[TOP_NEW_SESSION][1].val)
                {
                    use->in_top[TOP_NEW_SESSION] = 1;
                    ict->top[TOP_NEW_SESSION][1].ip = use->ip;
                    ict->top[TOP_NEW_SESSION][1].val = use->detail[DETAIL_VALUE_SESSION].in_ps;
                    top_repeat(ict->top[TOP_NEW_SESSION]);
                }
                if(use->detail[DETAIL_VALUE_HTTP].in_ps > ict->top[TOP_NEW_HTTP][1].val)
                {
                    use->in_top[TOP_NEW_HTTP] = 1;
                    ict->top[TOP_NEW_HTTP][1].ip = use->ip;
                    ict->top[TOP_NEW_HTTP][1].val = use->detail[DETAIL_VALUE_HTTP].in_ps;
                    top_repeat(ict->top[TOP_NEW_HTTP]);
                }
                #endif
                for(i = 0; i < MAX_DETAIL_VALUE; i++)
                {
                    detail = &use->detail[i];
                    check = &ict->ip_chk[i];
                    tmp_in = detail->in;
                    tmp_out = detail->out;
                    detail->in_ps = tmp_in - detail->in_last;
                    detail->out_ps = tmp_out - detail->out_last;
                    detail->in_last = tmp_in;
                    detail->out_last = tmp_out;

                    top = ict->top[i * 2];
                    if(detail->in_top)
                    {
                        lock(&(ict->top_lock));
                        for(j = 1; j <= TOP_N; j++)
                        {
                            if(top[j].ip == use->ip)
                            {
                                if(detail->in_ps > top[j].val)
                                {
                                    top[j].val = detail->in_ps;
                                    top_down(top, j);
                                }
                                else if(detail->in_ps < top[j].val)
                                {
                                    top[j].val = detail->in_ps;
                                    top_up(top, j);
                                }
                                break;
                            }
                        }
                        unlock(&(ict->top_lock));
                    }
                    else if(detail->in_ps > top[1].val)
                    {
                        lock(&(ict->top_lock));
                        if(top[1].key)
                            top[1].key->detail[i].in_top = 0;
                        detail->in_top = 1;
                        top[1].key = use;
                        top[1].ip = use->ip;
                        top[1].val = detail->in_ps;
                        top_down(top, 1);
                        unlock(&(ict->top_lock));
                    }
                    top = ict->top[i * 2 + 1];
                    if(detail->out_top)
                    {
                        lock(&(ict->top_lock));
                        for(j = 1; j <= TOP_N; j++)
                        {
                            if(top[j].ip == use->ip)
                            {
                                if(detail->out_ps > top[j].val)
                                {
                                    top[j].val = detail->out_ps;
                                    top_down(top, j);
                                }
                                else if(detail->out_ps < top[j].val)
                                {
                                    top[j].val = detail->out_ps;
                                    top_up(top, j);
                                }
                                break;
                            }
                        }
                        unlock(&(ict->top_lock));
                    }
                    else if(detail->out_ps > top[1].val)
                    {
                        lock(&(ict->top_lock));
                        if(top[1].key)
                            top[1].key->detail[i].out_top = 0;
                        detail->out_top = 1;
                        top[1].key = use;
                        top[1].ip = use->ip;
                        top[1].val = detail->out_ps;
                        top_down(top, 1);
                        unlock(&(ict->top_lock));
                    }

                    if(check->chk)
                    {
                        abnormal = 0;
                        if((detail->hold_time < 30) || ((detail->in_avg << 3) > detail->in_ps))
                        {
                            detail->hold_time++;
                            detail->in_normal += detail->in_ps;
                            detail->out_normal += detail->out_ps;
                            detail->in_avg = detail->in_normal / detail->hold_time;
                            detail->out_avg = detail->out_normal / detail->hold_time;
                        }
                        else if(detail->in_top)
                        {
                            if(check->type == CHECK_TYPE_FLOW)
                            {
                                if(detail->in_ps > check->min)
                                    abnormal = 1;
                            }
                            else
                            {
                                float scale;
                                float scale_avg;
                                if(detail->out_ps)
                                    scale = (float)(detail->in_ps) / (float)(detail->out_ps);
                                else
                                    scale = (float)(detail->in_ps);
                                if(detail->out_avg)
                                    scale_avg = (float)(detail->in_avg) / (float)(detail->out_avg);
                                else
                                    scale_avg = (float)(detail->in_avg);
                                if(scale_avg * 10 < scale)
                                    abnormal = 1;
                            }
                        }
                        if(abnormal)
                        {
                            if(!(use->attack & check->attack_type))
                            {
                                detail->ab_time++;
                                if(detail->ab_time > 10)
                                    use->attack |= check->attack_type;
                            }
                        }
                        else
                        {
                            if(detail->ab_time)
                                detail->ab_time--;
                        }
                        if(use->attack & check->attack_type)
                        {
                            if(ict->attack_cbk)
                            {
                                ipcount_attack_cbk callback = (ipcount_attack_cbk)ict->attack_cbk;
                                if(detail->ab_time)
                                    callback(ict, use->ip, check->attack_type, 1, use->detail[DETAIL_VALUE_PKG].in_ps, use->detail[DETAIL_VALUE_FLOW].in_ps);
                                else
                                {
                                    use->attack &= ~(check->attack_type);
                                    callback(ict, use->ip, check->attack_type, 0, 0, 0);
                                }
                            }
                        }
                    }
                }
                use->last_time = ict->time;
            }
            if(use == tail)
                break;
            else
                use = use->next_use;
        }

        usleep(0);
        //unlock(&(ict->del_lock));
    }
}
#endif

static int ip_count_expand(ip_count_t *ict)
{
    ip_info *buf = NULL;
    if(ict->buf_total >= MAX_COUNT_BUF)
        return 0;
    buf = (ip_info *)malloc(IP_EACH_BUF_SIZE * sizeof(ip_info));
    if(buf)
    {
        int i;
        memset(buf, 0, IP_EACH_BUF_SIZE * sizeof(ip_info));
        for(i = 0; i < IP_EACH_BUF_SIZE; i++)
        {
            if(ict->alive)
                ict->alive->next_alive = &buf[i];
            ict->alive = &buf[i];
        }
        if(!ict->cur)
            ict->cur = buf;
        ict->buf[ict->buf_total++] = buf;
        return 1;
    }
    return 0;
}

ip_count_t *ipcount_init()
{
    ip_count_t *ict = NULL;
    ict = (ip_count_t *)malloc(sizeof(ip_count_t));
    if(ict)
    {
        memset(ict, 0, sizeof(ip_count_t));
        if(ip_count_expand(ict))
        {
            ict->stats = 1;
            {
                ict->ip_chk[DETAIL_VALUE_UDP].chk = 1;
                ict->ip_chk[DETAIL_VALUE_UDP].type = CHECK_TYPE_FLOW;
                ict->ip_chk[DETAIL_VALUE_UDP].min = MIN_UDP_IN;
                ict->ip_chk[DETAIL_VALUE_UDP].attack_type = IPCOUNT_ATTACK_UDP_FLOOD;
                ict->ip_chk[DETAIL_VALUE_ICMP].chk = 1;
                ict->ip_chk[DETAIL_VALUE_ICMP].type = CHECK_TYPE_FLOW;
                ict->ip_chk[DETAIL_VALUE_ICMP].min = MIN_ICMP_IN;
                ict->ip_chk[DETAIL_VALUE_ICMP].attack_type = IPCOUNT_ATTACK_ICMP_FLOOD;
                ict->ip_chk[DETAIL_VALUE_SESSION].chk = 1;
                ict->ip_chk[DETAIL_VALUE_SESSION].type = CHECK_TYPE_SOURCE;
                ict->ip_chk[DETAIL_VALUE_SESSION].min = MIN_NEW_SESSION;
                ict->ip_chk[DETAIL_VALUE_SESSION].attack_type = IPCOUNT_ATTACK_TCP_FLOOD;
                ict->ip_chk[DETAIL_VALUE_CONN].chk = 1;
                ict->ip_chk[DETAIL_VALUE_CONN].type = CHECK_TYPE_SOURCE;
                ict->ip_chk[DETAIL_VALUE_CONN].min = MIN_NEW_CONN;
                ict->ip_chk[DETAIL_VALUE_CONN].attack_type = IPCOUNT_ATTACK_SYN_FLOOD;
                ict->ip_chk[DETAIL_VALUE_HTTP].chk = 1;
                ict->ip_chk[DETAIL_VALUE_HTTP].type = CHECK_TYPE_SOURCE;
                ict->ip_chk[DETAIL_VALUE_HTTP].min = MIN_NEW_HTTP;
                ict->ip_chk[DETAIL_VALUE_HTTP].attack_type = IPCOUNT_ATTACK_HTTP_FLOOD;
                ict->ip_chk[DETAIL_VALUE_ACK].chk = 1;
                ict->ip_chk[DETAIL_VALUE_ACK].type = CHECK_TYPE_SOURCE;
                ict->ip_chk[DETAIL_VALUE_ACK].min = 0;
                ict->ip_chk[DETAIL_VALUE_ACK].attack_type = IPCOUNT_ATTACK_ACK_FLOOD;
                ict->ip_chk[DETAIL_VALUE_DNS].chk = 1;
                ict->ip_chk[DETAIL_VALUE_DNS].type = CHECK_TYPE_FLOW;
                ict->ip_chk[DETAIL_VALUE_DNS].min = MIN_DNS_IN;
                ict->ip_chk[DETAIL_VALUE_DNS].attack_type = IPCOUNT_ATTACK_DNS_FLOOD;
            }
            ict->table = (ip_info **)malloc(IP_HASH_SIZE * sizeof(ip_info *));
            ict->end = (ip_info **)malloc(IP_HASH_SIZE * sizeof(ip_info *));
            ict->table_lock = (unsigned char *)malloc(IP_HASH_SIZE * sizeof(char));
            memset(ict->table, 0, IP_HASH_SIZE * sizeof(ip_info *));
            memset(ict->table_lock, 0, IP_HASH_SIZE * sizeof(char));
            //pthread_create(&(ict->timer), NULL, count_timer, (void *)ict);
            pthread_create(&(ict->counter), NULL, count_thread, (void *)ict);
        }
        else
        {
            free(ict);
            ict = NULL;
        }
    }
    return ict;
}

int ipcount_tini(ip_count_t *ict)
{
    if(ict)
    {
        int i;
        ict->stats = 0;
        //pthread_join(ict->timer, NULL);
        pthread_join(ict->counter, NULL);
        for(i = 0; i < ict->buf_total; i++)
            free(ict->buf[i]);
        if(ict->table)
            free(ict->table);
        if(ict->table_lock)
            free(ict->table_lock);
        free(ict);
        return 1;
    }
    return 0;
}

int ipcount_set_attack_cbk(ip_count_t *ict, void *cbk)
{
    if(ict && ict->stats)
    {
        ict->attack_cbk = cbk;
        return 1;
    }
    return 0;
}

int ipcount_add_ip(ip_count_t *ict, unsigned int ip)
{
    int ret = 0;
    if(ict && ict->stats && ip)
    {
        //unsigned int key = ip % IP_HASH_SIZE;
        unsigned int key = ((ip & 0x0fffffff) >> 4) % IP_HASH_SIZE;
        ip_info *find = NULL, *prev = NULL;

        lock(&(ict->table_lock[key]));
        find = ict->table[key];
        while(find)
        {
            if(find->ip == ip)
                break;
            prev = find;
            find = find->next;
        }
        if(!find)
        {
            lock(&(ict->add_lock));
            if(!ict->cur)
                ip_count_expand(ict);
            if(ict->cur)
            {
                find = ict->cur;
                ict->cur = ict->cur->next_alive;
                memset(find, 0, sizeof(ip_info));
                find->ip = ip;
                if(prev)
                {
                    prev->next = find;
                    find->prev = prev;
                }
                else
                    ict->table[key] = find;
                ict->end[key] = find;
                if(ict->use_tail)
                {
                    ict->use_tail->next_use = find;
                    find->prev_use = ict->use_tail;
                }
                ict->use_tail = find;
                if(!ict->use_head)
                    ict->use_head = find;
                ict->use_total++;
                ret = 1;
            }
            unlock(&(ict->add_lock));
        }
        unlock(&(ict->table_lock[key]));
    }
    return ret;
}

int ipcount_del_ip(ip_count_t *ict, unsigned int ip)      // think about the data link!!
{
    int ret = 0;
    if(0)//ict && ict->stats && ip)
    {
        //unsigned int key = ip % IP_HASH_SIZE;
        unsigned int key = ((ip & 0x0fffffff) >> 4) % IP_HASH_SIZE;
        ip_info *find = NULL;

        lock(&(ict->table_lock[key]));
        find = ict->table[key];
        while(find)
        {
            if(find->ip == ip)
                break;
            find = find->next;
        }
        if(find)
        {
            lock(&(ict->del_lock));
            if(ict->use_head == find)
            {
                ict->use_head = find->next_use;
                if(ict->use_head)
                    ict->use_head->prev_use = NULL;
            }
            if(ict->use_tail == find)
            {
                ict->use_tail = find->prev_use;
                if(ict->use_tail)
                    ict->use_tail->next_use = NULL;
            }
            if(find->prev_use)
            {
                find->prev_use->next_use = find->next_use;
                if(find->next_use)
                    find->next_use->prev_use = find->prev_use;
            }
            if(find->prev)
            {
                find->prev->next = find->next;
                if(find->next)
                    find->next->prev = find->prev;
            }
            else
            {
                ict->table[key] = find->next;
                if(find->next)
                    find->next->prev = NULL;
            }
            if(ict->alive)
                ict->alive->next_alive = find;
            ict->alive = find;
            if(!ict->cur)
                ict->cur = find;
            ict->use_total--;
            ret = 1;
            unlock(&(ict->del_lock));
        }
        unlock(&(ict->table_lock[key]));
    }
    return ret;
}

int ipcount_add_pkg(ip_count_t *ict, void *pkg, unsigned int len, unsigned char add_ip_flag, unsigned int session_type)
{
    int ret = 0;
    int find_level = 0;
    if(ict && ict->stats && pkg && IF_IP(pkg) && len)
    {
        unsigned int skey, dkey;
        unsigned int sip = GET_IP_SIP(pkg);
        unsigned int dip = GET_IP_DIP(pkg);
        ip_info *sfind = NULL, *dfind = NULL, *find = NULL;
        unsigned long time = base_time;

    finding:
        //skey = sip % IP_HASH_SIZE;
        //dkey = dip % IP_HASH_SIZE;
        skey = ((sip & 0x0fffffff) >> 4) % IP_HASH_SIZE;
        dkey = ((dip & 0x0fffffff) >> 4) % IP_HASH_SIZE;
        sfind = ict->table[skey];
        dfind = ict->table[dkey];
        if(!add_ip_flag || (add_ip_flag & (IPCOUNT_ADD_FLAG_SIP)))
        {
            //lock(&(ict->table_lock[skey]));
            while(!find && sfind)
            {
                if(sfind->ip == sip)
                {
                    find = sfind;
                    find->detail[DETAIL_VALUE_PKG].out++;
                    find->detail[DETAIL_VALUE_FLOW].out += len;
                    if(IF_TCP(pkg))
                    {
                        find->detail[DETAIL_VALUE_TCP].out += len;
                        if(IF_ACK(pkg))
                            find->detail[DETAIL_VALUE_ACK].out++;
                    }
                    else if(IF_UDP(pkg))
                        find->detail[DETAIL_VALUE_UDP].out += len;
                    else if(IF_ICMP(pkg))
                        find->detail[DETAIL_VALUE_ICMP].out += len;
                    ret = 1;
                    break;
                }
                if(sfind == ict->end[skey])
                    break;
                sfind = sfind->next;
                find_level++;
            }
            //unlock(&(ict->table_lock[skey]));
        }
        if(find_level > ict->max_level)
            ict->max_level = find_level;
        if(!add_ip_flag || (add_ip_flag & (IPCOUNT_ADD_FLAG_DIP)))
        {
            //lock(&(ict->table_lock[dkey]));
            while(!find && dfind)
            {
                if(dfind->ip == dip)
                {
                    find = dfind;
                    find->detail[DETAIL_VALUE_PKG].in++;
                    find->detail[DETAIL_VALUE_FLOW].in += len;
                    if(IF_TCP(pkg))
                    {
                        find->detail[DETAIL_VALUE_TCP].in += len;
                        if(IF_ACK(pkg))
                            find->detail[DETAIL_VALUE_ACK].in++;
                    }
                    else if(IF_UDP(pkg))
                    {
                        find->detail[DETAIL_VALUE_UDP].in += len;
                        if(GET_IP_DPORT(pkg) == 0x3500)
                            find->detail[DETAIL_VALUE_DNS].in++;
                    }
                    else if(IF_ICMP(pkg))
                        find->detail[DETAIL_VALUE_ICMP].in += len;
                    ret = 1;
                    break;
                }
                if(dfind == ict->end[dkey])
                    break;
                dfind = dfind->next;
                find_level++;
            }
            //unlock(&(ict->table_lock[dkey]));
        }
        if(find_level > ict->max_level)
            ict->max_level = find_level;
        if(!find && add_ip_flag)
        {
            //if(add_ip_flag & (IPCOUNT_ADD_FLAG_SIP))
                //ipcount_add_ip(ict, sip);
            if(add_ip_flag & (IPCOUNT_ADD_FLAG_DIP))
                ipcount_add_ip(ict, dip);
        }
    }
    return ret;
}

int ipcount_add_session(ip_count_t *ict, unsigned int sip, unsigned int dip, unsigned int session_type, unsigned int session_flow)
{
    int ret = 0;
    if(ict && ict->stats && sip && dip)
    {
        unsigned int skey, dkey;
        ip_info *sfind = NULL, *dfind = NULL, *find = NULL;
    finding:
        skey = ((sip & 0x0fffffff) >> 4) % IP_HASH_SIZE;
        dkey = ((dip & 0x0fffffff) >> 4) % IP_HASH_SIZE;
        sfind = ict->table[skey];
        dfind = ict->table[dkey];
        lock(&(ict->table_lock[skey]));
        while(!find && sfind)
        {
            if(sfind->ip == sip)
            {
                find = sfind;
                switch(session_type)
                {
                    case IPCOUNT_SESSION_TYPE_NEW:
                        break;
                    case IPCOUNT_SESSION_TYPE_CLOSE:
                        break;
                    case IPCOUNT_SESSION_TYPE_TIMEOUT:
                        break;
                    case IPCOUNT_SESSION_TYPE_HTTP:
                        break;
                    case IPCOUNT_SESSION_TYPE_UNKNOW:
                        break;
                    default:;
                }
                ret = 1;
                break;
            }
            sfind = sfind->next;
        }
        unlock(&(ict->table_lock[skey]));
        lock(&(ict->table_lock[dkey]));
        while(!find && dfind)
        {
            if(dfind->ip == dip)
            {
                find = dfind;
                switch(session_type)
                {
                    case IPCOUNT_SESSION_TYPE_NEW:
                        find->detail[DETAIL_VALUE_CONN].in++;
                        break;
                    case IPCOUNT_SESSION_TYPE_CONN:
                        find->detail[DETAIL_VALUE_CONN].out++;
                        find->detail[DETAIL_VALUE_SESSION].in++;
                        find->detail[DETAIL_VALUE_HTTP].out++;
                        break;
                    case IPCOUNT_SESSION_TYPE_CLOSE:
                        //find->detail[DETAIL_VALUE_HTTP].out++;
                        find->detail[DETAIL_VALUE_SESSION].out += session_flow;
                        break;
                    case IPCOUNT_SESSION_TYPE_TIMEOUT:
                        find->detail[DETAIL_VALUE_SESSION].out += session_flow;
                        break;
                    case IPCOUNT_SESSION_TYPE_HTTP:
                        find->detail[DETAIL_VALUE_HTTP].in++;
                        break;
                    case IPCOUNT_SESSION_TYPE_UNKNOW:
                        break;
                    default:;
                }
                ret = 1;
                break;
            }
            dfind = dfind->next;
        }
        unlock(&(ict->table_lock[dkey]));
    }
    return ret;
}

int ipcount_get_ip(ip_count_t *ict, ip_data *id)
{
    int ret = 0;
    if(ict && ict->stats && id && id->ip)
    {
        unsigned int key = ((id->ip & 0x0fffffff) >> 4) % IP_HASH_SIZE;
        ip_info *find = NULL;
        lock(&(ict->table_lock[key]));
        find = ict->table[key];
        while(find)
        {
            if(find->ip == id->ip)
                break;
            find = find->next;
        }
        unlock(&(ict->table_lock[key]));
        if(find)
        {
            id->recv = find->detail[DETAIL_VALUE_PKG].in;
            id->send = find->detail[DETAIL_VALUE_PKG].out;
            id->inflow = find->detail[DETAIL_VALUE_FLOW].in;
            id->outflow = find->detail[DETAIL_VALUE_FLOW].out;
            id->tcp_flow = find->detail[DETAIL_VALUE_TCP].in + find->detail[DETAIL_VALUE_TCP].out;
            id->udp_flow = find->detail[DETAIL_VALUE_UDP].in + find->detail[DETAIL_VALUE_UDP].out;
            id->icmp_flow = find->detail[DETAIL_VALUE_ICMP].in + find->detail[DETAIL_VALUE_ICMP].out;
            id->session_total = find->detail[DETAIL_VALUE_SESSION].in + find->detail[DETAIL_VALUE_HTTP].out;
            id->http_session = find->detail[DETAIL_VALUE_HTTP].in + find->detail[DETAIL_VALUE_HTTP].out;
            ret = 1;
        }
    }
    return ret;
}

int ipcount_get_ip_total(ip_count_t *ict)
{
    if(ict && ict->stats)
        return ict->use_total;
    return 0;
}

int ipcount_get_top_ip(ip_count_t *ict, int top_flag, top_data *td, unsigned int total)
{
    unsigned int ret = total;
    int i;
    top_info *top = NULL;
    switch(top_flag)
    {
        case IPCOUNT_TOP_PPS_IN:
            top = ict->top[DETAIL_VALUE_PKG * 2];
            break;
        case IPCOUNT_TOP_PPS_OUT:
            top = ict->top[DETAIL_VALUE_PKG * 2 + 1];
            break;
        case IPCOUNT_TOP_BPS_IN:
            top = ict->top[DETAIL_VALUE_FLOW * 2];
            break;
        case IPCOUNT_TOP_BPS_OUT:
            top = ict->top[DETAIL_VALUE_FLOW * 2 + 1];
            break;
        case IPCOUNT_TOP_NEW_SESSION:
            top = ict->top[DETAIL_VALUE_SESSION * 2];
            break;
        case IPCOUNT_TOP_NEW_HTTP:
            top = ict->top[DETAIL_VALUE_HTTP * 2];
            break;
        default:;
    }
    if(ict && ict->stats && td && total && top)
    {
        lock(&(ict->top_lock));
        for(i = 1; i <= TOP_N; i++)
        {
            if(top[i].ip)
            {
                td->ip = top[i].ip;
                td->val = top[i].val;
                td++;
                total--;
            }
        }
        unlock(&(ict->top_lock));
    }
    return ret - total;
}

int ipcount_get_all_ip(ip_count_t *ict, ip_data *id, unsigned int total)
{
    unsigned int ret = total;
    if(ict && ict->stats && id && total)
    {
        ip_info *use = NULL, *head = NULL, *tail = NULL;
        lock(&(ict->add_lock));
        head = ict->use_head;
        tail = ict->use_tail;
        unlock(&(ict->add_lock));
        use = head;
        //lock(&(ict->del_lock));
        while(use && total)
        {
            id->ip = use->ip;
            id->recv = use->detail[DETAIL_VALUE_PKG].in;
            id->send = use->detail[DETAIL_VALUE_PKG].out;
            id->inflow = use->detail[DETAIL_VALUE_FLOW].in;
            id->outflow = use->detail[DETAIL_VALUE_FLOW].out;
            id->tcp_flow = use->detail[DETAIL_VALUE_TCP].in + use->detail[DETAIL_VALUE_TCP].out;
            id->udp_flow = use->detail[DETAIL_VALUE_UDP].in + use->detail[DETAIL_VALUE_UDP].out;
            id->icmp_flow = use->detail[DETAIL_VALUE_ICMP].in + use->detail[DETAIL_VALUE_ICMP].out;
            id->session_total = use->detail[DETAIL_VALUE_SESSION].in + use->detail[DETAIL_VALUE_HTTP].out;
            id->http_session = use->detail[DETAIL_VALUE_HTTP].in + use->detail[DETAIL_VALUE_HTTP].out;
            id++;
            total--;
            if(use == tail)
                break;
            else
                use = use->next_use;
        }
        //unlock(&(ict->del_lock));
    }
done:
    return ret - total;
}

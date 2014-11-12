#include <efext.h>
#include <efnet.h>
#include <efio.h>
#include <pthread.h>
#include <stdio.h>

#define MAX_COUNT_BUF       1024
#define IP_EACH_BUF_SIZE    1000000
#define IP_HASH_SIZE        0x1000000


typedef struct _ip_info
{
    unsigned int ip;
    unsigned long recv, send, inflow, outflow, pps_in, pps_out, bps_in, bps_out;
    unsigned long last_recv, last_send, last_inflow, last_outflow, last_time;
    unsigned long tcp_flow, udp_flow, icmp_flow, http_flow;
    unsigned long session_total, session_close, http_session;
    struct _ip_info *prev, *next, *prev_use, *next_use, *next_alive;
}ip_info;


struct _ip_count_t
{
    ip_info *buf[MAX_COUNT_BUF];
    ip_info *cur, *alive, *use_head, *use_tail;
    ip_info **table;
    volatile unsigned int stats;
    unsigned int buf_total, use_total;
    unsigned char lock, add_lock, del_lock;
    unsigned char *table_lock;
    unsigned long time;
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

#if 1
static void count_thread(void *arg)
{
    ip_count_t *ict = (ip_count_t *)arg;
    ip_info *use = NULL, *head = NULL, *tail = NULL;
    while(ict->stats)
    {
        lock(&(ict->add_lock));
        head = ict->use_head;
        tail = ict->use_tail;
        unlock(&(ict->add_lock));
        use = head;
        ict->time = base_time;
        lock(&(ict->del_lock));
        while(use)
        {
            if(ict->time - use->last_time > 1000000)
            {
                unsigned long time = ict->time;
                unsigned long recv = use->recv;
                unsigned long send = use->send;
                unsigned long inflow = use->inflow;
                unsigned long outflow = use->outflow;

                if(time - use->last_time < 2000000)    //take attention!     take what attention?
                {
                    use->pps_in = recv - use->last_recv;
                    use->pps_out = send - use->last_send;
                    use->bps_in = inflow - use->last_inflow;
                    use->bps_out = outflow - use->last_outflow;
                }
                else
                {
                    unsigned long seconds = (time - use->last_time) / 1000000;
                    use->pps_in = (recv - use->last_recv) / seconds;
                    use->pps_out = (send - use->last_send) / seconds;
                    use->bps_in = (inflow - use->last_inflow) / seconds;
                    use->bps_out = (outflow - use->last_outflow) / seconds;
                }
                use->last_recv = recv;
                use->last_send = send;
                use->last_inflow = inflow;
                use->last_outflow = outflow;
                use->last_time = time;
            }
            if(use == tail)
                break;
            else
                use = use->next_use;
        }
        unlock(&(ict->del_lock));
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
            ict->table = (ip_info **)malloc(IP_HASH_SIZE * sizeof(ip_info *));
            ict->table_lock = (unsigned char *)malloc(IP_HASH_SIZE * sizeof(char));
            memset(ict->table, 0, IP_HASH_SIZE * sizeof(ip_info *));
            memset(ict->table_lock, 0, IP_HASH_SIZE * sizeof(char));
            //pthread_create(&(ict->timer), NULL, count_timer, (void *)ict);
            //pthread_create(&(ict->counter), NULL, count_thread, (void *)ict);
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
        //pthread_join(ict->counter, NULL);
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
    if(ict && ict->stats && ip)
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

int ipcount_add_pkg(ip_count_t *ict, void *pkg, unsigned int len, unsigned char add_ip_flag)
{
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
        lock(&(ict->table_lock[skey]));
        while(!find && sfind)
        {
            if(sfind->ip == sip)
            {
                find = sfind;
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
                break;
            }
            dfind = dfind->next;
        }
        unlock(&(ict->table_lock[dkey]));
        if(find_level > max_level)
            max_level = find_level;
        if(find)
        {
            if(find == sfind)
            {
                find->send++;
                find->outflow += len;
            }
            else
            {
                find->recv++;
                find->inflow += len;
            }
            if(IF_TCP(pkg))
                find->tcp_flow += len;
            else if(IF_UDP(pkg))
                find->udp_flow += len;
            else if(IF_ICMP(pkg))
                find->icmp_flow += len;
            #if 0
            if(time - find->last_time >= 1000000)
            {
                unsigned long seconds = (time - find->last_time) / 1000000;
                find->pps_in = (find->recv - find->last_recv) / seconds;
                find->pps_out = (find->send - find->last_send) / seconds;
                find->bps_in = (find->inflow - find->last_inflow) / seconds;
                find->bps_out = (find->outflow - find->last_outflow) / seconds;
                find->last_recv = find->recv;
                find->last_send = find->send;
                find->last_inflow = find->inflow;
                find->last_outflow = find->outflow;
                find->last_time = time;
            }
            #endif
            return 1;
        }
        else if(add_ip_flag)
        {
            if(add_ip_flag & IPCOUNT_ADD_FLAG_SIP)
                ipcount_add_ip(ict, sip);
            if(add_ip_flag & IPCOUNT_ADD_FLAG_DIP)
                ipcount_add_ip(ict, dip);
        }
    }
    return 0;
}

int ipcount_add_session(ip_count_t *ict, unsigned int sip, unsigned int dip,
                        unsigned int session_flag, unsigned int session_type, unsigned int session_flow)
{
    if(ict && ict->stats && sip && dip && session_flag)
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
                break;
            }
            dfind = dfind->next;
        }
        unlock(&(ict->table_lock[dkey]));
        if(find)
        {
            if(session_flag == IPCOUNT_SESSION_FLAG_ADD)
                find->session_total++;
            else if(session_flag == IPCOUNT_SESSION_FLAG_CLOSE)
            {
                find->session_close++;
                if(session_type == IPCOUNT_SESSION_TYPE_HTTP)
                {
                    find->http_flow += session_flow;
                    find->http_session++;
                }
            }
            return 1;
        }
    }
    return 0;
}

int ipcount_get_ip(ip_count_t *ict, ip_data *id)
{
    if(ict && ict->stats && id && id->ip)
    {
        unsigned int key = ((id->ip & 0x0fffffff) >> 4) % IP_HASH_SIZE;
        ip_info *find = NULL;
        lock(&(ict->add_lock));
        lock(&(ict->del_lock));
        find = ict->table[key];
        while(find)
        {
            if(find->ip == id->ip)
                break;
            find = find->next;
        }
        unlock(&(ict->del_lock));
        unlock(&(ict->del_lock));
        if(find)
        {
            id->recv = find->recv;
            id->send = find->send;
            id->inflow = find->inflow;
            id->outflow = find->outflow;
            id->tcp_flow = find->tcp_flow;
            id->udp_flow = find->udp_flow;
            id->icmp_flow = find->icmp_flow;
            id->http_flow = find->http_flow;
            id->session_total = find->session_total;
            id->session_close = find->session_close;
            id->http_session = find->http_session;
            return 1;
        }
    }
    return 0;
}

int ipcount_get_ip_total(ip_count_t *ict)
{
    if(ict && ict->stats)
        return ict->use_total;
    return 0;
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
        lock(&(ict->del_lock));
        while(use && total)
        {
            id->ip = use->ip;
            id->recv = use->recv;
            id->send = use->send;
            id->inflow = use->inflow;
            id->outflow = use->outflow;
            id->tcp_flow = use->tcp_flow;
            id->udp_flow = use->udp_flow;
            id->icmp_flow = use->icmp_flow;
            id->http_flow = use->http_flow;
            id->session_total = use->session_total;
            id->session_close = use->session_close;
            id->http_session = use->http_session;
            id++;
            total--;
            if(use == tail)
                break;
            else
                use = use->next_use;
        }
        unlock(&(ict->del_lock));
    }
done:
    return ret - total;
}

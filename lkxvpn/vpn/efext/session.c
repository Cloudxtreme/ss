#include <efext.h>
#include <efnet.h>
#include <efio.h>
#include <stdio.h>
#include <time.h>
#include <pthread.h>
#include <unistd.h>
#include <sys/types.h>
#include <fcntl.h>
#include <sys/mman.h>
#include <errno.h>


#define SESSION_TIMEOUT_DEFAUT		(30 * 1000000)

/*
typedef struct _link_info
{
	unsigned char 	protocol;
	unsigned int 	sip;
	unsigned int 	dip;
	unsigned short 	sport;
	unsigned short 	dport;
}link_info;
*/
typedef struct _link_info
{
	unsigned int 	sip;
	unsigned short 	sport;
	unsigned int 	dip;
	unsigned short 	dport;
	unsigned short  protocol;
}link_info;

typedef struct _session_slot
{
    struct _session_slot *prev;
    struct _session_slot *next;
    struct _session_slot *next_alive;
    unsigned int key;
    session *s;
    link_info li;
}session_slot;

struct _session
{
    struct _session *prev;
	struct _session *next;
	struct _session *next_alive;

    session_slot *master;
    session_slot *other;
    session_pool *pool;

    unsigned char   stats;
	unsigned char   lock;
	unsigned long   pkg, flow;
	unsigned long   timeout, last_active;

    unsigned int type;
	void *timeout_cbk;
	void *detail;
};


#define POOL_MAX_BUF			1024
#define POOL_EACH_BUF_SIZE		1000000
#define SESSION_HASH_SIZE		0x100000 //0x10000000
struct _session_pool
{
    session *session_buf[POOL_MAX_BUF];
    session_slot *slot_buf[POOL_MAX_BUF];
	session *session_cur, *session_alive, *session_use_head, *session_use_tail;
	session_slot *slot_cur, *slot_alive;
    //session_slot *table[SESSION_HASH_SIZE];
    session_slot **table;
    pthread_t recover;
	unsigned int buf_total;
    unsigned char stats;
    unsigned char pool_lock;
	//unsigned char table_lock[SESSION_HASH_SIZE];
	unsigned char *table_lock;
};


static unsigned char g_lock;
static unsigned int g_pool_total = 0;
static unsigned long g_session_time = 0;
static pthread_t g_timer;

extern unsigned long base_time;

unsigned int max_deep = 0;


static void timer()
{
    struct timeval now;
	while(g_pool_total)
	{
		gettimeofday(&now, NULL);
		g_session_time = now.tv_sec * 1000000 + now.tv_usec;
		usleep(0);
	}
}

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



    #define POLY 0x01101 // CRC20生成多项式x^20+x^12+x^8+1即:01101 CRC32:04C11DB7L
    static unsigned int crc_table[256];
    unsigned int get_sum_poly(unsigned char data)
    {
        unsigned int sum_poly = data;
        int j;
        sum_poly <<= 24;
        for(j = 0; j < 8; j++)
        {
            int hi = sum_poly&0x80000000; // 取得reg的最高位
            sum_poly <<= 1;
            if(hi) sum_poly = sum_poly^POLY;
        }
        return sum_poly;
    }
    void create_crc_table(void)  //在使用CRC20_key函数应该先建立crc表
    {
        int i;
        for(i = 0; i < 256; i++)
        {
        crc_table[i] = get_sum_poly(i&0xFF);
        }
    }
    unsigned int CRC20_key(unsigned char* data, int len)
    {
        int i;
        unsigned int reg = 0xFFFFFFFF;// 0xFFFFFFFF，见后面解释
        for(i = 0; i < len; i++)
        {
            reg = (reg<<8) ^ crc_table[(reg>>24)&0xFF ^ data[i]];
        }
        return (reg&0XFFFFF);//得到的reg取后20作为key值
    }



static int get_pkg_info(void *pkg, link_info *li)
{
    unsigned int sip, dip;
    unsigned short sport, dport;
    unsigned char protocol;
    struct iphdr *iph;

    if(!IF_IP(pkg))
        return 0;
    iph = P_IPP(pkg);
    sip = iph->saddr;
    dip = iph->daddr;
    sport = 0;
    dport = 0;
    protocol = iph->protocol;
    if(IF_TCP(pkg) || IF_UDP(pkg))
    {
        if(iph->ttl > 30)
        {
            sport = GET_IP_SPORT(pkg);
            dport = GET_IP_DPORT(pkg);
        }
    }
    else if(IF_ICMP(pkg))
    {
        struct icmphdr *ich = P_ICMPP(pkg);
        if((ich->code == 0) && (ich->type == 8 || ich->type == 0))
        {
            sport = ich->un.echo.id;
            dport = ich->un.echo.id;
        }
        else if((ich->type == 0xb && ich->code == 0) || (ich->type == 3 && ich->code == 3) || (ich->type == 3 && ich->code == 0xa))
        {
            struct iphdr *oiph = (struct iphdr *)((char *)ich + sizeof(struct icmphdr));
            //sip = sip = oiph->saddr;
            //dip = dip = oiph->daddr;
            sip = oiph->daddr;
            dip = oiph->saddr;
            protocol = oiph->protocol;
            if(oiph->protocol == PKT_TYPE_ICMP)
            {
                struct icmphdr *oich = (struct icmphdr *)((char *)oiph + (oiph->ihl<<2));
                sport = oich->un.echo.id;
                dport = oich->un.echo.id;
            }
        }
    }
    else
        return 0;
    li->sip = sip;
    li->dip = dip;
    li->sport = sport;
    li->dport = dport;
    li->protocol = protocol;
    return 1;
}

static session *get_session_from_pool(session_pool *pool)
{
    session *s = pool->session_cur;
    pool->session_cur = pool->session_cur->next_alive;
    return s;
}

static session_slot *get_slot_from_pool(session_pool *pool)
{
    session_slot *slot = pool->slot_cur;
    pool->slot_cur = pool->slot_cur->next_alive;
    return slot;
}

static session *build_session_in_pool(session_pool *pool, link_info *li)
{
    session *s = get_session_from_pool(pool);
    session_slot *master = get_slot_from_pool(pool);
    session_slot *other = get_slot_from_pool(pool);
    session_slot *table = NULL;


    memset(s, 0, sizeof(session));
    memset(master, 0, sizeof(session_slot));
    memset(other, 0, sizeof(session_slot));

    master->s = other->s = s;
    master->li.sip = other->li.dip = li->sip;
    master->li.dip = other->li.sip = li->dip;
    master->li.sport = other->li.dport = li->sport;
    master->li.dport = other->li.sport = li->dport;
    master->li.protocol = other->li.protocol = li->protocol;
    //master->key = (li->sip ^ li->dip ^ ((li->sport << 16) + li->dport)) % SESSION_HASH_SIZE;
    //other->key = (li->sip ^ li->dip ^ ((li->dport << 16) + li->sport)) % SESSION_HASH_SIZE;
    //master->key = ((li->sip ^ li->dip) * li->sport) % SESSION_HASH_SIZE;
    //other->key = ((li->sip ^ li->dip) * li->dport) % SESSION_HASH_SIZE;
    master->key = CRC20_key((unsigned char *)&(master->li), sizeof(link_info));
    other->key = CRC20_key((unsigned char *)&(other->li), sizeof(link_info));

    if(!pool->table[master->key])
        pool->table[master->key] = master;
    else
    {
        table = pool->table[master->key];
        while(table->next)
            table = table->next;
        table->next = master;
        master->prev = table;
    }
    if(!pool->table[other->key])
        pool->table[other->key] = other;
    else
    {
        table = pool->table[other->key];
        while(table->next)
            table = table->next;
        table->next = other;
        other->prev = table;
    }

    s->stats = 1;
    s->pool = pool;
    s->master = master;
    s->other = other;
    s->timeout = SESSION_TIMEOUT_DEFAUT;

    if(s->master && s->other && master->s && other->s)
    {
        if(pool->session_use_tail)
        {
            pool->session_use_tail->next = s;
            s->prev = pool->session_use_tail;
        }
        if(!pool->session_use_head)
            pool->session_use_head = s;
        pool->session_use_tail = s;
    }

    return s;
}

static void release_session_in_pool(session_pool *pool, session *s)
{
    session_slot *master = s->master;
    session_slot *other = s->other;
    if(master->prev)
    {
        master->prev->next = master->next;
        if(master->next)
            master->next->prev = master->prev;
    }
    else
    {
        pool->table[master->key] = master->next;
        if(master->next)
            master->next->prev = NULL;
    }
    if(other->prev)
    {
        other->prev->next = other->next;
        if(other->next)
            other->next->prev = other->prev;
    }
    else
    {
        pool->table[other->key] = other->next;
        if(other->next)
            other->next->prev = NULL;
    }
    if(pool->slot_alive)
        pool->slot_alive->next_alive = master;
    master->next_alive = other;
    pool->slot_alive = other;
    if(!pool->slot_cur)
        pool->slot_cur = master;

    if(pool->session_use_head == s)
    {
        pool->session_use_head = s->next;
        if(pool->session_use_head)
            pool->session_use_head->prev = NULL;
    }
    if(pool->session_use_tail == s)
    {
        pool->session_use_tail = s->prev;
        if(pool->session_use_tail)
            pool->session_use_tail->next = NULL;
    }
    if(s->prev)
    {
        s->prev->next = s->next;
        if(s->next)
            s->next->prev = s->prev;
    }
    if(pool->session_alive)
        pool->session_alive->next_alive = s;
    pool->session_alive = s;
    if(!pool->session_cur)
        pool->session_cur = s;
}

static int pool_expand(session_pool *pool)
{
    session *session_buf = NULL;
    session_slot *slot_buf = NULL;
	if(pool && pool->buf_total < POOL_MAX_BUF)
	{
		session_buf = (session *)malloc(POOL_EACH_BUF_SIZE * sizeof(session));
		slot_buf = (session_slot *)malloc(POOL_EACH_BUF_SIZE * 2 * sizeof(session_slot));
		if(session_buf && slot_buf)
		{
			int i = 0;
			memset(session_buf, 0, POOL_EACH_BUF_SIZE * sizeof(session));
			memset(slot_buf, 0, POOL_EACH_BUF_SIZE * 2 * sizeof(session_slot));
			pool->session_buf[pool->buf_total] = session_buf;
			pool->slot_buf[pool->buf_total] = slot_buf;
			for(i = 0; i < POOL_EACH_BUF_SIZE; i++)
			{
				if(pool->session_alive)
					pool->session_alive->next_alive = &session_buf[i];
				pool->session_alive = &session_buf[i];
			}
			for(i = 0; i < POOL_EACH_BUF_SIZE * 2; i++)
			{
                if(pool->slot_alive)
                    pool->slot_alive->next_alive = &slot_buf[i];
                pool->slot_alive = &slot_buf[i];
			}
			if(!pool->session_cur)
                pool->session_cur = session_buf;
            if(!pool->slot_cur)
                pool->slot_cur = slot_buf;
			pool->buf_total++;
			goto succ;
		}
	}
err:
    if(session_buf)
        free(session_buf);
    if(slot_buf)
        free(slot_buf);
    return 0;
succ:
	return 1;
}

static int pool_recover(void *arg)
{
    session_pool *pool = (session_pool *)arg;
    session *use = NULL, *next_use = NULL, *prev_use = NULL, *head = NULL, *tail = NULL;
    session_timeout_cbk callback;
    int ps = getpagesize();
    char *p = (char *)(((unsigned long)&use + 8) & ~(ps - 1));

    //mprotect(p, ps, PROT_READ);
    //mprotect(p, ps, PROT_READ|PROT_WRITE);
    while(pool->stats)
    {
        lock(&(pool->pool_lock));
        head = pool->session_use_head;
        tail = pool->session_use_tail;
        unlock(&(pool->pool_lock));
        //if(!head)
        {
            //usleep(0);
            //continue;
        }
        //mprotect(p, ps, PROT_READ|PROT_WRITE);
        use = head;
        //mprotect(p, ps, PROT_READ);
        prev_use = next_use = NULL;
        while(use)
        {
            next_use = use->next;
            if((!use->stats) || (use->last_active && (base_time - use->last_active > use->timeout)))
            {
            	lock(&(pool->pool_lock));
            	lock(&(pool->table_lock[use->master->key]));
            	if(use->other->key != use->master->key)
                    lock(&(pool->table_lock[use->other->key]));
				if((!use->stats) || (use->last_active && (base_time - use->last_active > use->timeout)))
				{
					if(use->timeout_cbk)
                	{
                    	callback = (session_timeout_cbk)use->timeout_cbk;
                    	callback(use);
                	}
					release_session_in_pool(pool, use);
				}
				if(use->other->key != use->master->key)
                    unlock(&(pool->table_lock[use->other->key]));
				unlock(&(pool->table_lock[use->master->key]));
				unlock(&(pool->pool_lock));
            }
            prev_use = use;
            //mprotect(p, ps, PROT_READ|PROT_WRITE);
            if(use == tail)
                break;
            else
                use = next_use;
            //mprotect(p, ps, PROT_READ);
        }//while(use != tail);
        usleep(0);
    }
}

session_pool *session_pool_init()
{
    session_pool *sp = NULL;
    int i;

    sp = (session_pool *)malloc(sizeof(session_pool));
    if(!sp)
        goto err;
    memset(sp, 0, sizeof(session_pool));
    sp->table = (session_slot **)malloc(SESSION_HASH_SIZE * sizeof(session_slot *));
    sp->table_lock = (unsigned char *)malloc(SESSION_HASH_SIZE * sizeof(char));
    if(!sp->table || !sp->table_lock)
        goto err;
    memset(sp->table, 0, SESSION_HASH_SIZE * sizeof(session_slot *));
    memset(sp->table_lock, 0, SESSION_HASH_SIZE * sizeof(char));
    lock(&g_lock);
	if(pool_expand(sp))
	{
		sp->stats = 1;
		pthread_create(&sp->recover, NULL, pool_recover, (void *)sp);
		g_pool_total++;
		if(g_pool_total == 1)
            create_crc_table();
		//if(g_pool_total == 1)
            //pthread_create(&g_timer, NULL, timer, NULL);
	}
	unlock(&g_lock);
	goto done;
err:
    if(sp)
    {
        if(sp->table)
            free(sp->table);
        if(sp->table_lock)
            free(sp->table_lock);
        free(sp);
    }
done:
    return sp;
}


int session_pool_tini(session_pool *sp)
{
    int ret = 0;
    if(sp)
    {
        lock(&g_lock);
        if(sp->stats)
        {
            sp->stats = 0;
        	pthread_join(sp->recover, NULL);
        }
		if(sp->buf_total)
        {
        	int i = 0;
			for(i = 0; i < sp->buf_total; i++)
			{
				free(sp->session_buf[i]);
				free(sp->slot_buf[i]);
			}
        }
        if(sp->table)
            free(sp->table);
        if(sp->table_lock)
            free(sp->table_lock);
        g_pool_total--;
        //if(!g_pool_total)
            //pthread_join(g_timer, NULL);
        ret = 1;
        unlock(&g_lock);
    }
    return ret;
}

int session_close(session *s)
{
    if(s && s->stats)
    {
    	s->stats = 0;
		return 1;
    }
    return 0;
}

session *session_get(session_pool *sp, void *pkg, int len)
{
    session_slot *slot = NULL;
    session *s = NULL;
    unsigned int deep = 0;
    if(sp && sp->stats && pkg)
    {
        unsigned int key;
        link_info pkg_link_info;
        if(!get_pkg_info(pkg, &pkg_link_info))
            goto done;
        //key = (pkg_link_info.sip ^ pkg_link_info.dip ^ ((pkg_link_info.sport << 16) + pkg_link_info.dport)) % SESSION_HASH_SIZE;
        //key = ((pkg_link_info.sip ^ pkg_link_info.dip) * pkg_link_info.sport) % SESSION_HASH_SIZE;
        key = CRC20_key((unsigned char *)&pkg_link_info, sizeof(link_info));
		lock(&(sp->table_lock[key]));
        slot = sp->table[key];
        //fprintf(stderr, "find : %u\n", key);
        while(slot)
        {
            link_info *li = &slot->li;
            if( (li->sip == pkg_link_info.sip) && (li->dip == pkg_link_info.dip)
                && (li->sport == pkg_link_info.sport) && (li->dport == pkg_link_info.dport)
                && (li->protocol == pkg_link_info.protocol) )
                break;
            deep++;
            slot = slot->next;
        }
        //fprintf(stderr, "finded!\n");
        if(deep > max_deep)
            max_deep = deep;
		if(slot)
		{
			s = slot->s;
			if(!s->stats)
			{
                s->pkg = s->flow = 0;
                s->timeout = SESSION_TIMEOUT_DEFAUT;
                s->type = 0;
                s->detail = s->timeout_cbk = NULL;
				s->stats = 1;
            }
			s->pkg++;
			s->flow += len;
			s->last_active = base_time;
		}
		unlock(&(sp->table_lock[key]));
        if(!slot)
        {
            lock(&(sp->pool_lock));
            if(!sp->slot_cur || !sp->slot_cur->next_alive || !sp->session_cur)
                pool_expand(sp);
            if(sp->slot_cur && sp->slot_cur->next_alive && sp->session_cur)
            {
                s = build_session_in_pool(sp, &pkg_link_info);
				s->pkg++;
				s->last_active = base_time;
            }
			unlock(&(sp->pool_lock));
        }
    }
done:
    return s;
}

int session_transmit(session *s, void *pkg)
{
    if(s && s->stats)
    {
        return 1;
    }
    return 0;
}

int session_set_type(session *s, unsigned int type)
{
    if(s && s->stats && type)
    {
        s->type = type;
        return 1;
    }
    return 0;
}

unsigned int session_get_type(session *s)
{
    if(s && s->stats)
        return s->type;
    return 0;
}

int session_set_detail(session *s, void *detail)
{
    if(s && s->stats)
    {
        s->detail = detail;
        return 1;
    }
    return 0;
}

void *session_get_detail(session *s)
{
    if(s && s->stats)
        return s->detail;
    return NULL;
}

int session_get_flow(session *s)
{
    if(s && s->stats)
        return s->flow;
    return 0;
}

int session_set_timeout(session *s, unsigned long timeout)
{
    if(s && s->stats)
    {
        s->timeout = timeout;
        return 1;
    }
    return 0;
}

int session_set_timeout_callback(session *s, void *cbk)
{
    if(s && s->stats)
    {
        s->timeout_cbk = cbk;
        return 1;
    }
    return 0;
}



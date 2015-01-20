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
#include <inttypes.h>


#define SESSION_TIMEOUT_CREATE      (30 * 1000000)
#define SESSION_TIMEOUT_CONN        (300 * 1000000)
#define SESSION_TIMEOUT_CLOSE       (5 * 1000000)

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
    link_info li;
    session *s;
    struct _session_slot *prev;
    struct _session_slot *next;
    struct _session_slot *next_alive;
    unsigned long pkg, flow;
    unsigned int key, val;
}session_slot;

#define SLOT_TABLE_SIZE     16
typedef struct _slot_table
{
    session_slot *slot[SLOT_TABLE_SIZE];
}slot_table;

struct _session
{
    unsigned long   create_time, conn_time, close_time;
    unsigned long   pkg, flow;
	unsigned long   timeout, last_active;
    struct _session *prev;
	struct _session *next;
	struct _session *next_alive;
    session_slot *master;
    session_slot *other;
    void *source;
	unsigned char   lock;
    unsigned int type;
	void *detail;
};

typedef struct _pool_source
{
    session *session_buf, *session_alive;
    session_slot *slot_buf, *slot_alive;
    unsigned int alives, use, alive_tmp, lost;
    struct _pool_source *prev, *next;
}pool_source;

#define POOL_EACH_BUF_SIZE		1000000
#define SESSION_HASH_SIZE		0x100000 //0x10000000
struct _session_pool
{
    unsigned char stats;
    unsigned int id, cpu;
    slot_table *table;
    unsigned char *table_lock;
	session *session_use_head, *session_use_tail;
    pool_source *source;
    pthread_t recover;
	unsigned long source_total, source_alive, source_reduce;
    unsigned char pool_lock;
	unsigned int max_deep, expand, reduce;
	void *timeout_cbk;
};

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


uint32_t crc32cHardware64(uint32_t crc, const void* data, size_t length)
{
	const char* p_buf = (const char*) data;
    // alignment doesn't seem to help?
    uint64_t crc64bit = crc;
	size_t i = 0;
    for (i = 0; i < length / sizeof(uint64_t); i++) {
        crc64bit = __builtin_ia32_crc32di(crc64bit, *(uint64_t*) p_buf);
        p_buf += sizeof(uint64_t);
    }

    // This ugly switch is slightly faster for short strings than the straightforward loop
    uint32_t crc32bit = (uint32_t) crc64bit;
    length &= sizeof(uint64_t) - 1;
    /*
    while (length > 0) {
        crc32bit = __builtin_ia32_crc32qi(crc32bit, *p_buf++);
        length--;
    }
    */
	///*
    switch (length) {
        case 7:
            crc32bit = __builtin_ia32_crc32qi(crc32bit, *p_buf++);
        case 6:
            crc32bit = __builtin_ia32_crc32hi(crc32bit, *(uint16_t*) p_buf);
            p_buf += 2;
        // case 5 is below: 4 + 1
        case 4:
            crc32bit = __builtin_ia32_crc32si(crc32bit, *(uint32_t*) p_buf);
            break;
        case 3:
            crc32bit = __builtin_ia32_crc32qi(crc32bit, *p_buf++);
        case 2:
            crc32bit = __builtin_ia32_crc32hi(crc32bit, *(uint16_t*) p_buf);
            break;
        case 5:
            crc32bit = __builtin_ia32_crc32si(crc32bit, *(uint32_t*) p_buf);
            p_buf += 4;
        case 1:
            crc32bit = __builtin_ia32_crc32qi(crc32bit, *p_buf);
            break;
        case 0:
            break;
        default:
            // This should never happen; enable in debug code
            ;//assert(false);
    }
	//*/
    return (crc32bit);
}



static int get_pkg_info(void *pkg, link_info *li)
{
    int ret = 0;
    unsigned int sip, dip;
    unsigned short sport, dport;
    unsigned char protocol;
    struct iphdr *iph;

    if(!IF_IP(pkg))
    {
        //fprintf(stderr, "not ip pkg!\n");
        return ret;
    }
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
        ret = 1;
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
        ret = 1;
    }
    //else
    //{
        //fprintf(stderr, "unknow pkg!\n");
    //}
    li->sip = sip;
    li->dip = dip;
    li->sport = sport;
    li->dport = dport;
    li->protocol = protocol;
    return ret;
}

static int get_source(session_pool *pool, session **s, session_slot **master, session_slot **other)
{
    int ret = 0;
    pool_source *source = pool->source;
    while(source)
    {
        if(source->alives)
        {
            void *tmp;
            *s = source->session_alive;
            *master = source->slot_alive;
            *other = source->slot_alive->next_alive;
            source->session_alive = source->session_alive->next_alive;
            source->slot_alive = source->slot_alive->next_alive->next_alive;
            tmp = (*s)->source;
            memset(*s, 0, sizeof(session));
            memset(*master, 0, sizeof(session_slot));
            memset(*other, 0, sizeof(session_slot));
            (*s)->source = tmp;
            source->alives--;
            pool->source_alive--;
            ret = 1;
            break;
        }
        source = source->next;
    }
    return ret;
}

static int reback_source(session_pool *pool, session *s, session_slot *master, session_slot *other)
{
    pool_source *source = (pool_source *)s->source;
    s->next_alive = source->session_alive;
    source->session_alive = s;
    master->next_alive = other;
    other->next_alive = source->slot_alive;
    source->slot_alive = master;
    source->alives++;
    pool->source_alive++;
    if(source->alives == POOL_EACH_BUF_SIZE)
        pool->source_reduce = 1;
    return 1;
}

static session *build_session_in_pool(session_pool *pool, link_info *li)
{
    session *s = NULL;
    session_slot *master = NULL, *other = NULL;
    slot_table *table = NULL;

    if(!get_source(pool, &s, &master, &other))
        return NULL;
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
    master->key = crc32cHardware64(0, &(master->li), sizeof(link_info)) & (SESSION_HASH_SIZE - 1);//*/CRC20_key((unsigned char *)&(master->li), sizeof(link_info));
    master->val = /*crc32cHardware64(11, &(master->li), sizeof(link_info)) % SLOT_TABLE_SIZE;//*/(li->sip ^ li->dip ^ ((li->sport << 16) + li->dport)) & (SLOT_TABLE_SIZE - 1);
    other->key = crc32cHardware64(0, &(other->li), sizeof(link_info)) & (SESSION_HASH_SIZE - 1);//*/CRC20_key((unsigned char *)&(other->li), sizeof(link_info));
    other->val = /*crc32cHardware64(11, &(other->li), sizeof(link_info)) % SLOT_TABLE_SIZE;//*/(li->dip ^ li->sip ^ ((li->dport << 16) + li->sport)) & (SLOT_TABLE_SIZE - 1);

    #if 1
    if(likely(!pool->table[master->key].slot[master->val]))
    {
        pool->table[master->key].slot[master->val] = master;
    }
    else
    {
        table = &pool->table[master->key];
        master->next = table->slot[master->val];
        table->slot[master->val]->prev = master;
        table->slot[master->val] = master;
    }
    if(likely(!pool->table[other->key].slot[other->val]))
    {
        pool->table[other->key].slot[other->val] = other;
    }
    else
    {
        table = &pool->table[other->key];
        other->next = table->slot[other->val];
        table->slot[other->val]->prev = other;
        table->slot[other->val] = other;
    }
    #endif

    s->master = master;
    s->other = other;

    if(s->master && s->other && master->s && other->s)
    {
        if(likely(pool->session_use_tail))
        {
            pool->session_use_tail->next = s;
            s->prev = pool->session_use_tail;
        }
        if(unlikely(!pool->session_use_head))
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
        pool->table[master->key].slot[master->val] = master->next;
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
        pool->table[other->key].slot[other->val] = other->next;
        if(other->next)
            other->next->prev = NULL;
    }

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
    reback_source(pool, s, master, other);
}

static int pool_reduce(session_pool *pool)
{
    int ret = 0;
    pool_source *source = pool->source;
    pool_source *next = NULL;
    fprintf(stderr, "session reduce!\n");
    while(source && pool->source_alive > (POOL_EACH_BUF_SIZE << 1))
    {
        next = source->next;
        if(source->alives == POOL_EACH_BUF_SIZE)
        {
            if(pool->source == source)
                pool->source = source->next;
            if(source->prev)
                source->prev->next = source->next;
            if(source->next)
                source->next->prev = source->prev;
            free(source->session_buf);
            free(source->slot_buf);
            free(source);
            pool->source_alive -= POOL_EACH_BUF_SIZE;
            pool->source_total--;
            pool->reduce++;
            ret = 1;
            //break;
        }
        source = next;
    }
    pool->source_reduce = 0;
    return ret;
}

static pool_source *create_source()
{
    pool_source *source = NULL;
    session *session_buf = NULL;
    session_slot *slot_buf = NULL;
    source = (pool_source *)malloc(sizeof(pool_source));
	if(source)
	{
        memset(source, 0, sizeof(source));
		session_buf = (session *)malloc(POOL_EACH_BUF_SIZE * sizeof(session));
		slot_buf = (session_slot *)malloc(POOL_EACH_BUF_SIZE * 2 * sizeof(session_slot));
		if(session_buf && slot_buf)
		{
			int i = 0;
			memset(session_buf, 0, POOL_EACH_BUF_SIZE * sizeof(session));
			memset(slot_buf, 0, POOL_EACH_BUF_SIZE * 2 * sizeof(session_slot));
			for(i = 0; i < POOL_EACH_BUF_SIZE; i++)
			{
                session_buf[i].source = source;
				session_buf[i].next_alive = source->session_alive;
				source->session_alive = &session_buf[i];
				slot_buf[i * 2].next_alive = &slot_buf[i * 2 + 1];
				slot_buf[i * 2 + 1].next_alive = source->slot_alive;
				source->slot_alive = &slot_buf[i * 2];
			}
            source->session_buf = session_buf;
            source->slot_buf = slot_buf;
			source->alives = POOL_EACH_BUF_SIZE;
			goto succ;
		}
	}
err:
    if(source)
        free(source);
    if(session_buf)
        free(session_buf);
    if(slot_buf)
        free(slot_buf);
    source = NULL;
succ:
    return source;
}

static int pool_expand(session_pool *pool, pool_source *source)
{
    pool_source *link = pool->source;

    fprintf(stderr, "session expand!\n");
    while(link)
    {
        if(link->next == NULL)
            break;
		link = link->next;
    }
    if(link)
    {
        link->next = source;
        source->prev = link;
    }
    else
    {
        pool->source = source;
    }
    pool->source_alive += POOL_EACH_BUF_SIZE;
    pool->source_total++;
    pool->expand++;
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
    if(1)
	{
        unsigned long mask = 1;
		mask = mask << (pool->cpu);
		fprintf(stderr, "pool cpu : %u\n", pool->cpu);
		sched_setaffinity(0, sizeof(mask), &mask);
	}
    while(pool->stats)
    {
        unsigned int check_num = 0;
        pool_source *source = pool->source;
        lock(&(pool->pool_lock));
        while(source)
        {
            source->alive_tmp = source->alives;
            source = source->next;
        }
        head = pool->session_use_head;
        tail = pool->session_use_tail;
        unlock(&(pool->pool_lock));
        //mprotect(p, ps, PROT_READ|PROT_WRITE);
        use = head;
        //mprotect(p, ps, PROT_READ);
        prev_use = next_use = NULL;
        while(use)
        {
            if(check_num++ > 100)
            {
                usleep(0);
                check_num = 0;
            }
        	source = (pool_source *)use->source;
			source->use++;
            next_use = use->next;
            if((use->last_active && (base_time - use->last_active > use->timeout)))
            {
            	lock(&(pool->pool_lock));
            	lock(&(pool->table_lock[use->master->key]));
            	if(use->other->key != use->master->key)
                    lock(&(pool->table_lock[use->other->key]));
				if((use->last_active && (base_time - use->last_active > use->timeout)))
				{
					if(!use->close_time && pool->timeout_cbk)
                	{
                    	callback = (session_timeout_cbk)pool->timeout_cbk;
                    	callback(pool, use);
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
        source = pool->source;
        while(source)
        {
            source->lost = POOL_EACH_BUF_SIZE - (source->use + source->alive_tmp);
            source->use = 0;
            source = source->next;
        }
        if(pool->source_alive < (POOL_EACH_BUF_SIZE >> 1))
        {
            pool_source *source = create_source();
            if(source)
            {
                lock(&(pool->pool_lock));
                pool_expand(pool, source);
                unlock(&(pool->pool_lock));
			}
        }
        else if(pool->source_alive > (POOL_EACH_BUF_SIZE << 1) && pool->source_reduce)
        {
            lock(&(pool->pool_lock));
            pool_reduce(pool);
            unlock(&(pool->pool_lock));
        }
        usleep(0);
    }
}

session_pool *session_pool_init(unsigned int id, unsigned int cpu, void *timeout_cbk)
{
    session_pool *sp = NULL;
    pool_source *source;

    sp = (session_pool *)malloc(sizeof(session_pool));
    if(!sp)
        goto err;
    memset(sp, 0, sizeof(session_pool));
    sp->id = id;
    sp->cpu = cpu;
    sp->table = (slot_table *)malloc(SESSION_HASH_SIZE * sizeof(slot_table));
    sp->table_lock = (unsigned char *)malloc(SESSION_HASH_SIZE * sizeof(char));
    if(!sp->table || !sp->table_lock)
        goto err;
    memset(sp->table, 0, SESSION_HASH_SIZE * sizeof(slot_table));
    memset(sp->table_lock, 0, SESSION_HASH_SIZE * sizeof(char));
	if((source = create_source()))
	{
        pool_expand(sp, source);
		sp->stats = 1;
		sp->timeout_cbk = timeout_cbk;
		pthread_create(&sp->recover, NULL, pool_recover, (void *)sp);
		goto done;
	}
err:
    if(sp)
    {
        if(sp->table)
            free(sp->table);
        if(sp->table_lock)
            free(sp->table_lock);
        free(sp);
        sp = NULL;
    }
done:
    return sp;
}


int session_pool_tini(session_pool *sp)
{
    int ret = 0;
    if(sp)
    {
        if(sp->stats)
        {
            sp->stats = 0;
        	pthread_join(sp->recover, NULL);
        }
		while(sp->source)
        {
        	pool_source *source = sp->source;
			sp->source = source->next;
			free(source->session_buf);
			free(source->slot_buf);
			free(source);
        }
        if(sp->table)
            free(sp->table);
        if(sp->table_lock)
            free(sp->table_lock);
        ret = 1;
    }
    return ret;
}

int session_close(session *s)
{
    if(s)
    {
    	s->close_time = base_time;
    	s->timeout = SESSION_TIMEOUT_CLOSE;
		return 1;
    }
    return 0;
}

int session_get(session_pool *sp, session **rs, void *pkg, int len)
{
    session_slot *slot = NULL;
    session *s = NULL;
    int session_stat = 0;
    unsigned int deep = 0;
    unsigned long the_time = base_time;
    if(sp && sp->stats && pkg)
    {
        unsigned int key, val;
        link_info pkg_link_info;
        if(!get_pkg_info(pkg, &pkg_link_info))
        {
            //fprintf(stderr, "wrong pkg!\n");
            goto done;
        }
        key = crc32cHardware64(0, &(pkg_link_info), sizeof(link_info)) & (SESSION_HASH_SIZE - 1);//*/CRC20_key((unsigned char *)&pkg_link_info, sizeof(link_info));
        val = /*crc32cHardware64(11, &(pkg_link_info), sizeof(link_info)) % SLOT_TABLE_SIZE;//*/(pkg_link_info.sip ^ pkg_link_info.dip ^ ((pkg_link_info.sport << 16) + pkg_link_info.dport)) & (SLOT_TABLE_SIZE - 1);
        //goto build_session;
        #if 1
		lock(&(sp->table_lock[key]));
        slot = sp->table[key].slot[val];
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
        if(deep > sp->max_deep)
            sp->max_deep = deep;
		if(slot)
		{
			s = slot->s;
			if(s->close_time && ( (IF_TCP(pkg) && IF_SYN(pkg)) || !(IF_TCP(pkg)) ) )
			{
                session_stat |= SESSION_TYPE_CREATE;
                s->create_time = the_time;
                s->conn_time = s->close_time = 0;
                s->pkg = s->flow = s->master->pkg = s->master->flow = s->other->pkg = s->other->flow = 0;
                s->timeout = SESSION_TIMEOUT_CREATE;
                s->type = 0;
                s->detail = NULL;
            }
            if(!s->conn_time)
            {
                if(IF_TCP(pkg))
                {
                    if(IF_ACK(pkg) && !IF_SYN(pkg))
                    {
                        session_stat |= SESSION_TYPE_CONN;
                        s->conn_time = the_time;
                        s->timeout = SESSION_TIMEOUT_CONN;
                    }
                }
                else if(slot != s->master)
                {
                    session_stat |= SESSION_TYPE_CONN;
                    s->conn_time = the_time;
                    s->timeout = SESSION_TIMEOUT_CONN;
                }
            }
            if(!s->close_time && IF_TCP(pkg) && (IF_FIN(pkg) || IF_RST(pkg)))
            {
                if(s->conn_time)
                    session_stat |= SESSION_TYPE_CLOSE;
                else
                    session_stat |= SESSION_TYPE_ERR;
                s->close_time = the_time;
                s->timeout = SESSION_TIMEOUT_CLOSE;
            }
            slot->pkg++;
            slot->flow += len;
			s->pkg++;
			s->flow += len;
			s->last_active = the_time;
		}
		unlock(&(sp->table_lock[key]));
		#endif
    build_session:
        if(!slot && ( (IF_TCP(pkg) && IF_SYN(pkg)) || !(IF_TCP(pkg)) ) )
        {
            lock(&(sp->pool_lock));
            if(sp->source_alive)
            {
                s = build_session_in_pool(sp, &pkg_link_info);
            }
			unlock(&(sp->pool_lock));
			if(s)
            {
                //fprintf(stderr, "create session!\n");
                session_stat |= SESSION_TYPE_CREATE;
                s->create_time = the_time;
                s->timeout = SESSION_TIMEOUT_CREATE;
                s->last_active = the_time;
            }
        }
    }
done:
    if(rs)
    {
        *rs = s;
        return session_stat;
    }
    return 0;
}

int session_set_type(session *s, unsigned int type)
{
    if(s && type)
    {
        s->type = type;
        return 1;
    }
    return 0;
}

unsigned int session_get_type(session *s)
{
    if(s)
        return s->type;
    return 0;
}

int session_set_detail(session *s, void *detail)
{
    if(s)
    {
        //if(s->detail)
            //fprintf(stderr, "already has detail!\n");
        s->detail = detail;
        return 1;
    }
    return 0;
}

void *session_get_detail(session *s)
{
    if(s)
        return s->detail;
    return NULL;
}

unsigned int session_get_sip(session *s)
{
    if(s)
        return s->master->li.sip;
    return 0;
}

unsigned int session_get_dip(session *s)
{
    if(s)
        return s->master->li.dip;
    return 0;
}

unsigned short session_get_sport(session *s)
{
    if(s)
        return s->master->li.sport;
    return 0;
}

unsigned short session_get_dport(session *s)
{
    if(s)
        return s->master->li.dport;
    return 0;
}

int session_if_conn(session *s)
{
    if(s)
        return (s->conn_time && !s->close_time);
    return 0;
}

unsigned long session_get_create_time(session *s)
{
    if(s)
        return s->create_time;
    return 0;
}

unsigned long session_get_conn_time(session *s)
{
    if(s)
        return s->conn_time;
    return 0;
}

unsigned long session_get_close_time(session *s)
{
    if(s)
        return s->close_time;
    return 0;
}

unsigned long session_get_flow(session *s)
{
    if(s)
        return s->flow;
    return 0;
}

unsigned int session_pool_id(session_pool *pool)
{
    if(pool && pool->stats)
        return pool->id;
    return 0;
}

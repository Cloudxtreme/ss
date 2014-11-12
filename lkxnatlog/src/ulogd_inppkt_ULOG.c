/* ulogd_inppkt_ULOG.c - stackable input plugin for ULOG packets -> ulogd2
 *
 * (C) 2004-2005 by Harald Welte <laforge@gnumonks.org>
 */

#include <unistd.h>
#include <stdlib.h>
#include <arpa/inet.h>
#include <string.h>


#include <ulogd/ulogd.h>
#include <libipulog/libipulog.h>

#include <syslog.h>

/****add by efly*****/
#include <signal.h>
#include <unistd.h>
#include <sys/types.h>
#include <pthread.h>
#include <assert.h>

#include <netinet/ip.h>
#include <netinet/ip6.h>
#include <netinet/in.h>
#include <netinet/tcp.h>
#include <netinet/ip_icmp.h>
#include <netinet/icmp6.h>
#include <netinet/udp.h>



#if 1
#define POOL_MAX_BUFSLOTS				1000000
#define POOL_MAX_THREAD					20

#define EFLY_ULOG_BENAT					1
#define EFLY_ULOG_AFNAT					2

#define EFLY_ULOG_DEFAULT_DONUM			1000
#define EFLY_ULOG_MINIUM_DONUM			100
#define EFLY_ULOG_CLK					3

#define EFLY_ULOG_SLOT_LEN				100

#define EFLY_ULOG_MAX_SENDSERVER		10

typedef struct _efly_ulog_t
{
	unsigned short	log_len;
	unsigned char	log_type;
	long			log_time;
	long			log_usec;
}efly_ulog_t;

typedef struct _efly_ulog_ops
{
	int					ef_bufslots;
	int					ef_donum;
	char				ef_thnum;
	int					ef_thclk;
	int					ef_sendport;
	char				ef_sendip[CONFIG_VAL_STRING_LEN];
}efly_ulog_ops;

typedef void (*_process)(unsigned int do_begin, unsigned int do_end, struct tm *buf_time);

typedef struct _worker
{
	unsigned int 	worker_id;
	unsigned int 	work_begin;
	unsigned int 	work_end;
	int 			next_worker;
	int 			prev_worker;
}WORKER;

typedef struct _pool_tbw	//tbw : thread buf write
{
	pthread_mutex_t queue_lock;
	pthread_cond_t queue_ready;

	_process process;
	//int cur_point, ok_point;
	void *buf_benat;
	void *buf_afnat;
	unsigned int 	p_benat;						//before nat point
	unsigned int 	p_afnat;						//after nat point
	unsigned int 	p_done;							//done nat point
	unsigned int 	p_count;						//current nat num , nat = benat + afnat
	unsigned int 	p_donum;						//how much nat num to do job
	unsigned int 	p_begin;						//min of the job doing point
	unsigned int 	p_end;							//max of the job can do
	WORKER			workers[POOL_MAX_THREAD];
	int				worker_head;
	int				worker_tail;
	unsigned int	worker_count;
	//unsigned int buf_cur[POOL_MAX_THREAD];
	//unsigned int buf_do[POOL_MAX_THREAD];
	//unsigned int buf_end[POOL_MAX_THREAD];
	int shutdown;
	int buf_slots;
	int th_num;
	//int th_buf;
	pthread_t *threadid;
	pthread_t control;

}POOL_TBW;

static int pool_control();
void pool_add_buf(void *_buf, unsigned int _len, unsigned char _type, long sec, long usec);
void *thread_routine(void *arg);
void pool_init(int _buf_slots, int _th_num, int _donum);
void pool_destroy();

static POOL_TBW *pool = NULL;
static efly_ulog_ops ef_ops;

int efly_send_srvnum = 0;
int efly_fd[EFLY_ULOG_MAX_SENDSERVER] = {0};
struct sockaddr_in efly_server[EFLY_ULOG_MAX_SENDSERVER] = {0};

int log_level = LOG_LOCAL3 | LOG_DEBUG;

unsigned long long g_benat_count = 0;
unsigned long long g_afnat_count = 0;

#endif

/******************/

#ifndef ULOGD_NLGROUP_DEFAULT
#define ULOGD_NLGROUP_DEFAULT	32
#endif

/* Size of the socket receive memory.  Should be at least the same size as the
 * 'nlbufsiz' module loadtime parameter of ipt_ULOG.o
 * If you have _big_ in-kernel queues, you may have to increase this number.  (
 * --qthreshold 100 * 1500 bytes/packet = 150kB  */
#define ULOGD_RMEM_DEFAULT	131071

/* Size of the receive buffer for the netlink socket.  Should be at least of
 * RMEM_DEFAULT size.  */
#define ULOGD_BUFSIZE_DEFAULT	150000

struct ulog_input {
	struct ipulog_handle *libulog_h;
	unsigned char *libulog_buf;
	struct ulogd_fd ulog_fd;
};

/* configuration entries */

static struct config_keyset libulog_kset = {
	.num_ces = 10,
	.ces = {
	{
		.key 	 = "bufsize",
		.type 	 = CONFIG_TYPE_INT,
		.options = CONFIG_OPT_NONE,
		.u.value = ULOGD_BUFSIZE_DEFAULT,
	},
	{
		.key	 = "nlgroup",
		.type	 = CONFIG_TYPE_INT,
		.options = CONFIG_OPT_NONE,
		.u.value = ULOGD_NLGROUP_DEFAULT,
	},
	{
		.key	 = "rmem",
		.type	 = CONFIG_TYPE_INT,
		.options = CONFIG_OPT_NONE,
		.u.value = ULOGD_RMEM_DEFAULT,
	},
	{
		.key	 = "numeric_label",
		.type	 = CONFIG_TYPE_INT,
		.options = CONFIG_OPT_NONE,
		.u.value = 0,
	},
	{
		.key 	 = "ef_bufslots",
		.type 	 = CONFIG_TYPE_INT,
		.options = CONFIG_OPT_NONE,
		.u.value = POOL_MAX_BUFSLOTS,
	},
	{
		.key 	 = "ef_donum",
		.type 	 = CONFIG_TYPE_INT,
		.options = CONFIG_OPT_NONE,
		.u.value = EFLY_ULOG_DEFAULT_DONUM,
	},
	{
		.key 	 = "ef_thnum",
		.type 	 = CONFIG_TYPE_INT,
		.options = CONFIG_OPT_NONE,
		.u.value = POOL_MAX_THREAD,
	},
	{
		.key 	 = "ef_thclk",
		.type 	 = CONFIG_TYPE_INT,
		.options = CONFIG_OPT_NONE,
		.u.value = EFLY_ULOG_CLK,
	},
	{
		.key 	 = "ef_sendip",
		.type 	 = CONFIG_TYPE_STRING,
		.options = CONFIG_OPT_MANDATORY,
	},
	{
		.key 	 = "ef_sendport",
		.type 	 = CONFIG_TYPE_INT,
		.options = CONFIG_OPT_NONE,
		.u.value = 514,
	},

	}
};
enum ulog_keys {
	ULOG_KEY_RAW_MAC = 0,
	ULOG_KEY_RAW_PCKT,
	ULOG_KEY_RAW_PCKTLEN,
	ULOG_KEY_RAW_PCKTCOUNT,
	ULOG_KEY_OOB_PREFIX,
	ULOG_KEY_OOB_TIME_SEC,
	ULOG_KEY_OOB_TIME_USEC,
	ULOG_KEY_OOB_MARK,
	ULOG_KEY_OOB_IN,
	ULOG_KEY_OOB_OUT,
	ULOG_KEY_OOB_HOOK,
	ULOG_KEY_RAW_MAC_LEN,
	ULOG_KEY_OOB_FAMILY,
	ULOG_KEY_OOB_PROTOCOL,
	ULOG_KEY_RAW_LABEL,
};

static struct ulogd_key output_keys[] = {
	[ULOG_KEY_RAW_MAC] = {
		.type = ULOGD_RET_RAW,
		.flags = ULOGD_RETF_NONE,
		.name = "raw.mac",
		.ipfix = {
			.vendor = IPFIX_VENDOR_IETF,
			.field_id = IPFIX_sourceMacAddress,
		},
	},
	[ULOG_KEY_RAW_PCKT] = {
		.type = ULOGD_RET_RAW,
		.flags = ULOGD_RETF_NONE,
		.name = "raw.pkt",
		.ipfix = {
			.vendor = IPFIX_VENDOR_NETFILTER,
			.field_id = 1,
			},
	},
	[ULOG_KEY_RAW_PCKTLEN] = {
		.type = ULOGD_RET_UINT32,
		.flags = ULOGD_RETF_NONE,
		.name = "raw.pktlen",
		.ipfix = {
			.vendor = IPFIX_VENDOR_IETF,
			.field_id = 1
		},
	},
	[ULOG_KEY_RAW_PCKTCOUNT] = {
		.type = ULOGD_RET_UINT32,
		.flags = ULOGD_RETF_NONE,
		.name = "raw.pktcount",
		.ipfix = {
			.vendor = IPFIX_VENDOR_IETF,
			.field_id = 2
		},
	},
	[ULOG_KEY_OOB_PREFIX] = {
		.type = ULOGD_RET_STRING,
		.flags = ULOGD_RETF_NONE,
		.name = "oob.prefix",
	},
	[ULOG_KEY_OOB_TIME_SEC] = {
		.type = ULOGD_RET_UINT32,
		.flags = ULOGD_RETF_NONE,
		.name = "oob.time.sec",
		.ipfix = {
			.vendor = IPFIX_VENDOR_IETF,
			.field_id = 22
		},
	},
	[ULOG_KEY_OOB_TIME_USEC] = {
		.type = ULOGD_RET_UINT32,
		.flags = ULOGD_RETF_NONE,
		.name = "oob.time.usec",
	},
	[ULOG_KEY_OOB_MARK] = {
		.type = ULOGD_RET_UINT32,
		.flags = ULOGD_RETF_NONE,
		.name = "oob.mark",
	},
	[ULOG_KEY_OOB_IN] = {
		.type = ULOGD_RET_STRING,
		.flags = ULOGD_RETF_NONE,
		.name = "oob.in",
	},
	[ULOG_KEY_OOB_OUT] = {
		.type = ULOGD_RET_STRING,
		.flags = ULOGD_RETF_NONE,
		.name = "oob.out",
	},
	[ULOG_KEY_OOB_HOOK] = {
		.type = ULOGD_RET_UINT8,
		.flags = ULOGD_RETF_NONE,
		.name = "oob.hook",
		.ipfix = {
			.vendor = IPFIX_VENDOR_NETFILTER,
			.field_id = IPFIX_NF_hook,
		},
	},
	[ULOG_KEY_RAW_MAC_LEN] = {
		.type = ULOGD_RET_UINT16,
		.flags = ULOGD_RETF_NONE,
		.name = "raw.mac_len",
	},
	[ULOG_KEY_OOB_FAMILY] = {
		.type = ULOGD_RET_UINT8,
		.flags = ULOGD_RETF_NONE,
		.name = "oob.family",
	},
	[ULOG_KEY_OOB_PROTOCOL] = {
		.type = ULOGD_RET_UINT16,
		.flags = ULOGD_RETF_NONE,
		.name = "oob.protocol",
	},
	[ULOG_KEY_RAW_LABEL] = {
		.type = ULOGD_RET_UINT8,
		.flags = ULOGD_RETF_NONE,
		.name = "raw.label",
	},

};


#if 1

/******add by efly*********/
static int th_arg[POOL_MAX_THREAD] = {0};
void pool_init(int _buf_slots, int _th_num, int _donum)
{
	int i = 0;
	pool = (POOL_TBW *)malloc(sizeof(POOL_TBW));
	memset(pool, 0, sizeof(POOL_TBW));

	pthread_mutex_init(&(pool->queue_lock), NULL);
	pthread_cond_init(&(pool->queue_ready), NULL);

	pool->buf_slots = _buf_slots;
	pool->th_num = _th_num;
	pool->p_donum = _donum;
	pool->p_end = pool->buf_slots - 1;
	pool->p_done = pool->buf_slots - 1;
	pool->buf_benat = malloc(pool->buf_slots * EFLY_ULOG_SLOT_LEN);
	pool->buf_afnat = malloc(pool->buf_slots * EFLY_ULOG_SLOT_LEN);
	memset(pool->buf_benat, 0, pool->buf_slots * EFLY_ULOG_SLOT_LEN);
	memset(pool->buf_afnat, 0, pool->buf_slots * EFLY_ULOG_SLOT_LEN);

	pool->threadid = (pthread_t *)malloc(pool->th_num * sizeof(pthread_t));
	for(i = 0; i < pool->th_num; i++)
	{
		th_arg[i] = i;
		pool->workers[i].worker_id = i;
		pthread_create(&(pool->threadid[i]), NULL, thread_routine, (void *)&th_arg[i]);
	}
    pthread_create(&(pool->control), NULL, pool_control, NULL);
	printf("\nefly pool_init success!------\n");
}

unsigned int debug_pool_add_buf1 = 0, debug_pool_add_buf2 = 0, debug_pool_add_buf3 = 0, debug_pool_add_buf4 = 0, debug_pool_add_buf5 = 0;
unsigned int debug_thread1 = 0, debug_thread2 = 0, debug_thread3 = 0, debug_thread4 = 0;
static int pool_control()
{
    FILE *fp = fopen("/var/log/efly_ulog.log", "w+");
    while(!pool->shutdown)
    {
        if(fp)
        {
            fprintf(fp, "pool[benat:%u afnat:%u done:%u count:%u donum:%u begin:%u end:%u work_head:%d work_tail:%d work_count:%u]\n",
                        pool->p_benat, pool->p_afnat, pool->p_done, pool->p_count, pool->p_donum, pool->p_begin, pool->p_end,
                        pool->worker_head, pool->worker_tail, pool->worker_count);
            fprintf(fp, "debug[%u %u %u %u %u, %u %u %u %u]\n",
                        debug_pool_add_buf1, debug_pool_add_buf2, debug_pool_add_buf3, debug_pool_add_buf4, debug_pool_add_buf5,
                        debug_thread1, debug_thread2, debug_thread3, debug_thread4);
        }
        sleep(1);
    }
    if(fp)
        fclose(fp);
}

void pool_set_process(_process process)
{
	pool->process = process;
}

void pool_wake_thread()
{
	//printf("wake up thread by %s\n", time_tag ? "timer" : "pool");
	pthread_mutex_lock(&(pool->queue_lock));
	pthread_cond_signal(&(pool->queue_ready));
	pthread_mutex_unlock(&(pool->queue_lock));
}

void pool_add_buf(void *_buf, unsigned int _len, unsigned char _type, long _sec, long _usec)
{
	static unsigned long long p_benat_count = 0;
	static unsigned long long p_afnat_count = 0;
	static efly_ulog_t	void_efut = {0};
	efly_ulog_t			efut;
	efly_ulog_t			*be_efut;
	void				*buf;
	unsigned int		cur;

	static int debug = 1;

    debug_pool_add_buf1 = (debug_pool_add_buf1 + 1) % 1000000;
	if((!_buf) || (!_len))
		return;
    debug_pool_add_buf2 = (debug_pool_add_buf2 + 1) % 1000000;
	if(_type == EFLY_ULOG_BENAT)
	{
		buf = pool->buf_benat;
		cur = pool->p_benat;
	}
	else
	{
		buf = pool->buf_afnat;
		cur = pool->p_afnat;
	}
	if(cur == pool->p_end)
	{
        debug_pool_add_buf3 = (debug_pool_add_buf3 + 1) % 1000000;
		pool_wake_thread();
		return;
	}

	efut.log_len = _len;
	efut.log_type = _type;
	efut.log_time = _sec;
	efut.log_usec = _usec;

	if(_type == EFLY_ULOG_AFNAT)
	{
		if(p_benat_count <= p_afnat_count)
			goto lost_benat;

		be_efut = (efly_ulog_t *)(pool->buf_benat + cur * EFLY_ULOG_SLOT_LEN);
		if((efut.log_time != be_efut->log_time) || (efut.log_usec != be_efut->log_usec))
		{
			if(efut.log_time == be_efut->log_time)
			{
				if(efut.log_usec < be_efut->log_usec)
				{
					goto lost_benat;
				}
				else
				{
					goto lost_afnat;
				}
			}
			else
			{
				if(efut.log_time < be_efut->log_time)
				{
					goto lost_benat;
				}
				else
				{
					goto lost_afnat;
				}
			}
		}
	}


	memcpy(buf + cur * EFLY_ULOG_SLOT_LEN, &efut, sizeof(efly_ulog_t));
	memcpy(buf + cur * EFLY_ULOG_SLOT_LEN + sizeof(efly_ulog_t), _buf, _len);
	if(_type == EFLY_ULOG_BENAT)
	{
		pool->p_benat = (cur + 1) % pool->buf_slots;
		p_benat_count++;
	}
	else
	{
		pool->p_done = cur;
		pool->p_afnat = (cur + 1) % pool->buf_slots;
		p_afnat_count++;
		pool->p_count++;
	}

	if(pool->p_count >= pool->p_donum)
	{
		p_benat_count -= pool->p_donum;
		p_afnat_count -= pool->p_donum;
		pool->p_count = 0;
		pool_wake_thread();
	}
	return;

lost_benat:

    debug_pool_add_buf4 = (debug_pool_add_buf4 + 1) % 1000000;
	return;

lost_afnat:
    debug_pool_add_buf5 = (debug_pool_add_buf5 + 1) % 1000000;
	memcpy(buf + cur * EFLY_ULOG_SLOT_LEN, &void_efut, sizeof(efly_ulog_t));
	while(cur != pool->p_benat)
	{
		cur = (cur + 1) % pool->buf_slots;
		be_efut = (efly_ulog_t *)(pool->buf_benat + cur * EFLY_ULOG_SLOT_LEN);
		if((efut.log_time == be_efut->log_time) && (efut.log_usec == be_efut->log_usec))
		{
			memcpy(buf + cur * EFLY_ULOG_SLOT_LEN, &efut, sizeof(efly_ulog_t));
			memcpy(buf + cur * EFLY_ULOG_SLOT_LEN + sizeof(efly_ulog_t), _buf, _len);
			p_afnat_count++;
			pool->p_count++;
			break;
		}
		else
		{
			if(efut.log_time == be_efut->log_time)
			{
				if(efut.log_usec < be_efut->log_usec)
				{
					if(!cur)
						cur = pool->buf_slots - 1;
					else
						--cur;
					break;
				}
			}
			else
			{
				if(efut.log_time < be_efut->log_time)
				{
					if(!cur)
						cur = pool->buf_slots - 1;
					else
						--cur;
					break;
				}
			}
			memcpy(buf + cur * EFLY_ULOG_SLOT_LEN, &void_efut, sizeof(efly_ulog_t));
		}
	}

	if(cur == pool->p_benat)
	{
        pool->p_done = (cur) ? (cur - 1) : (pool->buf_slots - 1);
		pool->p_afnat = cur;
    }
	else
	{
        pool->p_done = cur;
		pool->p_afnat = (cur + 1) % pool->buf_slots;
    }
	return;
}

void pool_destroy()
{
	int i = 0;
	if(pool->shutdown)
		return;
	pool->shutdown = 1;

	pthread_cond_broadcast(&(pool->queue_ready));
	for(i = 0; i < pool->th_num; i++)
	{
		pthread_join(pool->threadid[i], NULL);
	}
	pthread_join(pool->control, NULL);
	free(pool->buf_afnat);
	free(pool->buf_benat);
	free(pool->threadid);

	pthread_mutex_destroy(&(pool->queue_lock));
	pthread_cond_destroy(&(pool->queue_ready));
	free(pool);
	pool = NULL;
	return;
}

void *thread_routine(void *arg)
{
	efly_ulog_t 		*efut;
	unsigned int 		do_begin, do_end;
	int 				prev, next;
	int 				th_num = *(int *)arg;
	struct tm 			buf_time;

	do_begin = 0;
	do_end = 0;
	while(1)
	{
		pthread_mutex_lock(&(pool->queue_lock));
		debug_thread1 = (debug_thread1 + 1) % 1000000;

		//do_begin = pool->p_begin;
		//do_end = pool->p_done;

		if((pool->shutdown == 0))// && (do_begin == do_end))
			pthread_cond_wait(&(pool->queue_ready), &(pool->queue_lock));
        debug_thread2 = (debug_thread2 + 1) % 1000000;

		if(pool->shutdown)
		{
			pthread_mutex_unlock(&(pool->queue_lock));
			pthread_exit(NULL);
		}

		//printf("work %d wake up!begin:%d end:%d\n", th_num, do_begin, do_end);
		//if(do_begin == do_end)
		{
			do_begin = pool->p_begin;
			do_end = pool->p_done;
		}

		if(do_begin != ((do_end + 1) % pool->buf_slots))
		{
			pool->workers[th_num].work_begin = do_begin;
			pool->workers[th_num].work_end = do_end;
			if(pool->worker_count == 0)
			{
				pool->worker_head = th_num;
				pool->workers[th_num].prev_worker = -1;
			}
			else
			{
				pool->workers[th_num].prev_worker = pool->worker_tail;
				pool->workers[pool->worker_tail].next_worker = th_num;
			}
			pool->worker_tail = th_num;
			pool->workers[th_num].next_worker = -1;
			pool->p_begin = (do_end + 1) % pool->buf_slots;
			pool->worker_count++;

			efut = (efly_ulog_t *)(pool->buf_benat + do_begin * EFLY_ULOG_SLOT_LEN);
			memcpy(&buf_time, localtime(&efut->log_time), sizeof(struct tm));
		}
		pthread_mutex_unlock(&(pool->queue_lock));

		if(do_begin == ((do_end + 1) % pool->buf_slots))
			continue;

		//printf("th_num:%d point:%d do_cur:%u do_end:%u buf_end:%u\n", th_num, ok_point, do_begin, do_end, pool->buf_end[ok_point]);
		////printf("work begin:%d end:%d\n", do_begin, do_end);
		debug_thread3 = (debug_thread3 + 1) % 1000000;
		if(do_begin != ((do_end + 1) % pool->buf_slots))
		{
			pool->process(do_begin, do_end, &buf_time);
		}

		pthread_mutex_lock(&(pool->queue_lock));
		debug_thread4 = (debug_thread4 + 1) % 1000000;
		if(pool->worker_count)
		{
			prev = pool->workers[th_num].prev_worker;
			next = pool->workers[th_num].next_worker;
			if(pool->worker_count == 1)
			{
				pool->p_end = pool->workers[th_num].work_end;
				pool->worker_head = -1;
				pool->worker_tail = -1;
			}
			else if(th_num == pool->worker_head)
			{
				pool->worker_head = next;
				if(next != -1)
					pool->workers[next].prev_worker = -1;
				pool->p_end = pool->workers[th_num].work_end;
			}
			else if(th_num == pool->worker_tail)
			{
				pool->worker_tail = prev;
				if(prev != -1)
				{
                    pool->workers[prev].work_end = pool->workers[th_num].work_end;
					pool->workers[prev].next_worker = -1;
                }
			}
			else
			{
				if(prev != -1)
				{
                    pool->workers[prev].work_end = pool->workers[th_num].work_end;
					pool->workers[prev].next_worker = next;
                }
				if(next != -1)
					pool->workers[next].prev_worker = prev;
			}
			pool->workers[th_num].work_begin = 0;
			pool->workers[th_num].work_end = 0;
			pool->workers[th_num].next_worker = -1;
			pool->workers[th_num].prev_worker = -1;
			pool->worker_count--;
		}
		pthread_mutex_unlock(&(pool->queue_lock));
	}
}

/************************/
#endif

static int interp_packet(struct ulogd_pluginstance *ip, ulog_packet_msg_t *pkt)
{
	struct ulogd_key *ret = ip->output.keys;

	if (pkt->mac_len) {
		okey_set_ptr(&ret[ULOG_KEY_RAW_MAC], pkt->mac);
		okey_set_u16(&ret[ULOG_KEY_RAW_MAC_LEN], pkt->mac_len);
	}

	okey_set_u8(&ret[ULOG_KEY_RAW_LABEL], ip->config_kset->ces[3].u.value);

	/* include pointer to raw ipv4 packet */
	okey_set_ptr(&ret[ULOG_KEY_RAW_PCKT], pkt->payload);
	okey_set_u32(&ret[ULOG_KEY_RAW_PCKTLEN], pkt->data_len);
	okey_set_u32(&ret[ULOG_KEY_RAW_PCKTCOUNT], 1);

	okey_set_ptr(&ret[ULOG_KEY_OOB_PREFIX], pkt->prefix);

	/* god knows why timestamp_usec contains crap if timestamp_sec == 0
	 * if (pkt->timestamp_sec || pkt->timestamp_usec) { */
	if (pkt->timestamp_sec) {
		okey_set_u32(&ret[ULOG_KEY_OOB_TIME_SEC], pkt->timestamp_sec);
		okey_set_u32(&ret[ULOG_KEY_OOB_TIME_USEC], pkt->timestamp_usec);
	} else {
		ret[ULOG_KEY_OOB_TIME_SEC].flags &= ~ULOGD_RETF_VALID;
		ret[ULOG_KEY_OOB_TIME_USEC].flags &= ~ULOGD_RETF_VALID;
	}

	okey_set_u32(&ret[ULOG_KEY_OOB_MARK], pkt->mark);
	okey_set_ptr(&ret[ULOG_KEY_OOB_IN], pkt->indev_name);
	okey_set_ptr(&ret[ULOG_KEY_OOB_OUT], pkt->outdev_name);

	okey_set_u8(&ret[ULOG_KEY_OOB_HOOK], pkt->hook);

	/* ULOG is IPv4 only */
	okey_set_u8(&ret[ULOG_KEY_OOB_FAMILY], AF_INET);
	/* Undef in ULOG but necessary */
	okey_set_u16(&ret[ULOG_KEY_OOB_PROTOCOL], 0);

	ulogd_propagate_results(ip);
	return 0;
}


/*********************   add by efly   *************************************/
char *mon_str[] = {"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"};
char day_str[32][4] = {0};
char *non_str[] = {"None"};
char ip_str[256][4] = {0};
char po_str[65536][8] = {0};
char ts_str[60][4] = {0};


int parse_iphdr(struct iphdr *iph, char *proto, char *src_ip, char *dst_ip, int *srcp, int *dstp)
{
	int ret = 0;
	int i;
	int len;
	struct udphdr *uph;
	struct tcphdr *tph;
	char *srcip, *dstip;
	unsigned char *addr;

	///*
	len = 0;
	addr = (unsigned char *)&(iph->saddr);
	for(i = 0; i < 4; i++)
	{
		memcpy(src_ip + len, ip_str[addr[i]], strlen(ip_str[addr[i]]));
		len += strlen(ip_str[addr[i]]);
		if(i < 3)
			src_ip[len++] = '.';
	}
	while(len < 15)
		src_ip[len++] = ' ';
	src_ip[len] = 0;
	len = 0;
	addr = (unsigned char *)&(iph->daddr);
	for(i = 0; i < 4; i++)
	{
		memcpy(dst_ip + len, ip_str[addr[i]], strlen(ip_str[addr[i]]));
		len += strlen(ip_str[addr[i]]);
		if(i < 3)
			dst_ip[len++] = '.';
	}
	while(len < 15)
		dst_ip[len++] = ' ';
	dst_ip[len] = 0;
	//*/
	//inet_ntoa(*(struct in_addr *)&(iph->saddr));
	//inet_ntoa(*(struct in_addr *)&(iph->daddr));

	*srcp = 0;
	*dstp = 0;
	switch(iph->protocol)
	{
		case IPPROTO_TCP:
			len = strlen("TCP   ");
			memcpy(proto, "TCP   ", len);
			proto[len] = 0;
			tph = (struct tcphdr *)((u_int32_t *)iph + iph->ihl);
			*srcp = ntohs(tph->source);
			*dstp = ntohs(tph->dest);
			break;
		case IPPROTO_UDP:
			len = strlen("UDP   ");
			memcpy(proto, "UDP   ", len);
			proto[len] = 0;
			uph = (struct udphdr *)((u_int32_t *)iph + iph->ihl);
			*srcp = ntohs(uph->source);
			*dstp = ntohs(uph->dest);
			break;
		case IPPROTO_ICMP:
			len = strlen("ICMP  ");
			memcpy(proto, "ICMP  ", len);
			proto[len] = 0;
			break;
		default:
		{
			char *error_data = (char *)iph;
			ret = -1;
			len = strlen("Unknow");
			memcpy(proto, "Unknow", len);
			proto[len] = 0;
		}
	}
	return ret;
}

int efly_localtime(struct tm *ts, long b, long e)
{
	long t = e - b;
	int year;

	if(t <= 0)
		return 0;
	ts->tm_sec += t;
	if(ts->tm_sec < 60)
		goto retc;
	ts->tm_min += ts->tm_sec / 60;
	ts->tm_sec %= 60;
	if(ts->tm_min < 60)
		goto retc;
	ts->tm_hour += ts->tm_min / 60;
	ts->tm_min %= 60;
	if(ts->tm_hour < 24)
		goto retc;
	ts->tm_mday += ts->tm_hour / 24;
	ts->tm_hour %= 24;
	year = ts->tm_year + 1900;
	if(ts->tm_mon == 1)
	{
		if((year % 400 == 0) || ((year % 4 == 0) && (year % 100 != 0)))
		{
			ts->tm_mon += ts->tm_mday / 29;
			ts->tm_mday %= 29;
		}
		else
		{
			ts->tm_mon += ts->tm_mday / 28;
			ts->tm_mday %= 28;
		}
	}
	else
	{
		if(ts->tm_mon % 2 == 0)
		{
			ts->tm_mon += ts->tm_mday / 31;
			ts->tm_mday %= 31;
		}
		else
		{
			ts->tm_mon += ts->tm_mday / 30;
			ts->tm_mday %= 30;
		}
	}
	ts->tm_year += ts->tm_mon / 12;
	ts->tm_mon %= 12;
retc:
	return 1;
}

int totl_send[POOL_MAX_THREAD][POOL_MAX_THREAD] = {0};
int ulog_write_cbk(unsigned int do_begin, unsigned int do_end, struct tm *buf_time)
{
#define LEN_PROTO		6
#define LEN_IP			15
#define LEN_PORT		5
#define LEN_MON			3
#define LEN_TIME		2

	int					p_do;
	void				*buf_benat;
	void				*buf_afnat;
	char				send_str[2048] = {0};//"<7>Dec 28 19:00:00 localhost kernel: IN=eth1 OUT= MAC=00:0c";
	char				*str_info[8] = {"<7>", "PROTOCOL=", "SrcIP=", "DstIP=", "SrcPORT=", "DstPORT=", "NatIP=", "NatPORT="};
	unsigned char		*str_posi[8] = {0};
	char				nat_tag = 0;
	char				nat_ipstr[64] = {0};
	char				nat_postr[64] = {0};
	int					nat_sum = 0;
	int					pkt_sum = 0;
	int					src_pkt_sum = 0;
	int					nat_pkt_sum = 0;
	int					be_bad_count = 0, af_bad_count = 0, ab_bad_count = 0;

	efly_ulog_t 		*efut, *be_efut, *af_efut;
	size_t 				addr_len = sizeof(struct sockaddr_in);

	struct iphdr 		*iph;

	char 				proto[32] = {0};
	char 				src_ip[32] = {0};
	char 				dst_ip[32] = {0};
	char 				nat_src_ip[32] = {0};
	char 				nat_dst_ip[32] = {0};

	int 				src_port, dst_port;
	int 				nat_src_port, nat_dst_port;

	unsigned int 		s_ip = 0, d_ip = 0, ns_ip = 0, nd_ip = 0;
	char 				*nat_ip;
	int 				nat_port;

	struct tm 			struTmNow;
	long 				cur_time;
	int 				i;

	int					done;


	//printf("begin:0x%x end:0x%x\n", buf_begin, buf_end);
	strcpy(send_str, "<7>xxx xx xx:xx:xx localhost kernel: eflyNAT:PROTOCOL=xxxxxx SrcIP=xxx.xxx.xxx.xxx DstIP=xxx.xxx.xxx.xxx SrcPORT=xxxxx DstPORT=xxxxx xNatIP=xxx.xxx.xxx.xxx xNatPORT=xxxxx\n");
	for(i = 0; i < 8; i++)
	{
		str_posi[i] = strstr(send_str, str_info[i]) + strlen(str_info[i]);
	}
	for(i = 0; i < strlen(send_str); i++)
	{
		if(send_str[i] == 'x')
			send_str[i] = ' ';
	}

	done = 0;
	p_do = do_begin;
	buf_benat = (pool->buf_benat + p_do * EFLY_ULOG_SLOT_LEN);
	buf_afnat = (pool->buf_afnat + p_do * EFLY_ULOG_SLOT_LEN);
	efut = (efly_ulog_t *)buf_benat;
	cur_time = efut->log_time;
	while(1)
	{

		if(p_do == do_end)
			done = 1;

		be_efut = (efly_ulog_t *)buf_benat;
		af_efut = (efly_ulog_t *)buf_afnat;

		if(!(be_efut->log_len)) { be_bad_count++; goto err;}
		if(!(af_efut->log_len)) { af_bad_count++; goto err;}
		if((be_efut->log_time != af_efut->log_time) || (be_efut->log_usec != af_efut->log_usec))
		{ ab_bad_count++; goto err;}

		iph = (struct iphdr *)(buf_benat + sizeof(efly_ulog_t));
		pkt_sum++;
		src_pkt_sum++;
		s_ip = iph->saddr;
		d_ip = iph->daddr;
		if(parse_iphdr(iph, proto, src_ip, dst_ip, &src_port, &dst_port))
		{
			//syslog(log_level, "benat parse_iphdr error: buf len:%d\n", efut->log_len);
		}

		iph = (struct iphdr *)(buf_afnat + sizeof(efly_ulog_t));
		pkt_sum++;
		nat_pkt_sum++;
		ns_ip = iph->saddr;
		nd_ip = iph->daddr;
		if(parse_iphdr(iph, proto, nat_src_ip, nat_dst_ip, &nat_src_port, &nat_dst_port))
		{
			//syslog(log_level, "afnat parse_iphdr error: buf len:%d\n", efut->log_len);
		}

		nat_ipstr[0] = 0;
		nat_postr[0] = 0;

		if(s_ip != ns_ip)
		{
			nat_tag = 1;
			nat_ip = nat_src_ip;
			*(str_posi[6] - 7) = 'S';
		}
		else if(d_ip != nd_ip)
		{
			nat_tag = 1;
			nat_ip = nat_dst_ip;
			*(str_posi[6] - 7) = 'D';
		}

		if(src_port != nat_src_port)
		{
			nat_tag = 1;
			nat_port = nat_src_port;
			*(str_posi[7] - 9) = 'S';
		}
		else if(dst_port != nat_dst_port)
		{
			nat_tag = 1;
			nat_port = nat_dst_port;
			*(str_posi[7] - 9) = 'D';
		}

		if(nat_tag)
		{
			efut = (efly_ulog_t *)buf_afnat;
			efly_localtime(buf_time, cur_time, efut->log_time);
			cur_time = efut->log_time;

			memcpy(str_posi[0], mon_str[buf_time->tm_mon], LEN_MON);
			memcpy(str_posi[1], proto, LEN_PROTO);
			memcpy(str_posi[2], src_ip, LEN_IP); memcpy(str_posi[3], dst_ip, LEN_IP);
			memcpy(str_posi[4], po_str[src_port], LEN_PORT); memcpy(str_posi[5], po_str[dst_port], LEN_PORT);
			memcpy(str_posi[6], nat_ip, LEN_IP);
			memcpy(str_posi[7], po_str[nat_port], LEN_PORT);
			memcpy(str_posi[0] + 4, ts_str[buf_time->tm_mday], LEN_TIME);
			memcpy(str_posi[0] + 7, ts_str[buf_time->tm_hour], LEN_TIME);
			memcpy(str_posi[0] + 10, ts_str[buf_time->tm_min], LEN_TIME);
			memcpy(str_posi[0] + 13, ts_str[buf_time->tm_sec], LEN_TIME);

			//struTmNow = *localtime(&efut->log_time);
			/*
			sprintf(send_str,
				"<7>%s %2d %02d:%02d:%02d localhost kernel: eflyNAT:PROTOCOL=%s SrcIP=%s DstIP=%s SrcPORT=%d DstPORT=%d %s %s\0",
				mon_str[struTmNow.tm_mon], struTmNow.tm_mday, struTmNow.tm_hour, struTmNow.tm_min, struTmNow.tm_sec,
				proto,
				src_ip, dst_ip, src_port, dst_port,
				nat_ipstr, nat_postr);
				*/
			nat_sum++;
		}
err:
		s_ip = 0; d_ip = 0;
		ns_ip = 0; nd_ip = 0;
		nat_port = 0;

		if(done)
			break;
		p_do++;
		if(p_do == pool->buf_slots)
		{
			p_do = 0;
			buf_benat = pool->buf_benat;
			buf_afnat = pool->buf_afnat;
		}
		else
		{
			buf_benat += EFLY_ULOG_SLOT_LEN;
			buf_afnat += EFLY_ULOG_SLOT_LEN;
		}

		if(nat_tag)
		{
			int n;
			//if(strlen(send_str) > sendto(efly_fd, send_str, strlen(send_str), 0, (struct sockaddr *)&efly_server, addr_len))
				//printf("efly ulog send error\n");
			for(n = 0; n < efly_send_srvnum; n++)
				sendto(efly_fd[n], send_str, strlen(send_str), 0, (struct sockaddr *)&(efly_server[n]), addr_len);
			//fprintf(stderr, "send_str : %s\n\n", send_str);
			nat_tag = 0;
		}
	}

	////fprintf(stderr, "efly ulog finish send %d , %d + %d = %d!!ab_bad:%d, be_bad:%d af_bad:%d\n\n",
		////nat_sum, src_pkt_sum, nat_pkt_sum, pkt_sum, ab_bad_count, be_bad_count, af_bad_count);
}


pthread_t efly_timer_thread;
void efly_timer(void *ef_clk)
{
	int clock = *(int *)ef_clk;
	while(1)
	{
		sleep(clock);
		////printf("wait %d second time's up\n", clock);
		//printf("[benat_count : %lld] -- [afnat_count : %lld]\n", g_benat_count, g_afnat_count);
		pool_wake_thread();
	}
}

int efly_str_init()
{
	int i;

	ip_str[0][0] = '0';
	for(i = 0; i < 256; i++)
	{
		int ip = i;
		int jj = 0;
		int nn = 100;
		while(nn)
		{
			if((ip / nn) || jj)
				ip_str[i][jj++] = '0' + ip / nn;
			ip -= ip / nn * nn;
			nn /= 10;
		}
	}

	po_str[0][0] = '0';
	for(i = 0; i < 65536; i++)
	{
		int ip = i;
		int jj = 0;
		int nn = 10000;
		while(nn)
		{
			if((ip / nn) || jj)
				po_str[i][jj++] = '0' + ip / nn;
			ip -= ip / nn * nn;
			nn /= 10;
		}
		while(jj < 5)
			po_str[i][jj++] = ' ';
	}

	for(i = 0; i < 32; i++)
	{
		if(i >= 10)
			ts_str[i][0] = '0' + i / 10;
		else
			ts_str[i][0] = ' ';
		ts_str[i][1] = '0' + i % 10;
	}

	for(i = 0; i < 60; i++)
	{
		ts_str[i][0] = '0' + i / 10;
		ts_str[i][1] = '0' + i % 10;
	}
}

int efly_fd_init(const char *ip, int port)
{
	int server_ip;
	int ip_num = 0;
	char *p = NULL;

	if(!strcmp(ip, "ef_sendip"))
	{
		printf("ef_sendip set error!\n");
		exit(1);
	}
	p = strtok(ip, ";");
	while(p)
	{
		if ((efly_fd[ip_num]=socket(AF_INET, SOCK_DGRAM, 0))==-1)
		{
			printf("socket() error\n");
			exit(1);
		}

		if(-1 == fcntl(efly_fd[ip_num], F_SETFL, O_NONBLOCK))
		{
			printf("fcntl error\n");
			exit(1);
		}

		bzero(&(efly_server[ip_num]), sizeof(struct sockaddr_in));
		efly_server[ip_num].sin_family = AF_INET;
		efly_server[ip_num].sin_port = htons(port);
		inet_pton(AF_INET, p, (void*)&server_ip);
		efly_server[ip_num].sin_addr.s_addr = server_ip;

		ip_num++;
		p = strtok(NULL, ";");
	}
	efly_send_srvnum = ip_num;
}

int efly_nf_init(efly_ulog_ops *ef_ops)
{
	printf("\n***********\t\t\t***********\n");
	printf(" *\tsend to ip\t: %s\n *\tsend to port\t: %d\n *\tbuf slots\t: %d\n *\tthread num\t: %d\n *\tdo num\t\t: %d\n *\twake clock\t: %d second",
		ef_ops->ef_sendip, ef_ops->ef_sendport,
		ef_ops->ef_bufslots, ef_ops->ef_thnum, ef_ops->ef_donum, ef_ops->ef_thclk);
	printf("\n***********\t\t\t***********\n");

	efly_fd_init(ef_ops->ef_sendip, ef_ops->ef_sendport);
	efly_str_init();
	//pool_init(ef_ops->ef_thnum, ef_ops->ef_thbuf);
	pool_init(ef_ops->ef_bufslots, ef_ops->ef_thnum, ef_ops->ef_donum);
	pool_set_process(ulog_write_cbk);
	if(ef_ops->ef_thclk)
	{
		pthread_create(&efly_timer_thread, NULL, efly_timer, (void *)&(ef_ops->ef_thclk));
	}
}

int efly_nf_uninit(efly_ulog_ops *ef_ops)
{
	if(ef_ops->ef_thclk)
	{
		pthread_cancel(efly_timer_thread);
		pthread_join(efly_timer_thread, NULL);
	}
	pool_destroy();
}

static int ulog_read_cb(int fd, unsigned int what, void *param)
{
	struct ulogd_pluginstance *upi = (struct ulogd_pluginstance *)param;
	struct ulogd_pluginstance *npi = NULL;
	struct ulog_input *u = (struct ulog_input *) &upi->private;
	ulog_packet_msg_t *upkt;
	int len;
	static int work_begin = 0;

	if (!(what & ULOGD_FD_READ))
		return 0;

	#if 1
	while ((len = ipulog_read(u->libulog_h, u->libulog_buf,
				 upi->config_kset->ces[0].u.value))) {
		if (len <= 0) {
			if (errno == EAGAIN)
				break;
			/* this is not supposed to happen */
			ulogd_log(ULOGD_ERROR, "ipulog_read = %d! "
				  "ipulog_errno = %d (%s), "
				  "errno = %d (%s)\n",
				  len, ipulog_errno,
				  ipulog_strerror(ipulog_errno),
				  errno, strerror(errno));
			break;
		}
		#if 1
		while ((upkt = ipulog_get_packet(u->libulog_h,
						 u->libulog_buf, len))) {
			/* since we support the re-use of one instance in
			 * several different stacks, we duplicate the message
			 * to let them know */
			///llist_for_each_entry(npi, &upi->plist, plist)
				///interp_packet(npi, upkt);
			///interp_packet(upi, upkt);
			#if 1
			//printf("%s %ld\n", upkt->prefix, upkt->timestamp_usec);
			if(strncmp(upkt->prefix, "efly_afnat", strlen("efly_afnat")) == 0)
			{
				if(work_begin)
					pool_add_buf(upkt->payload, 60, EFLY_ULOG_AFNAT, upkt->timestamp_sec, upkt->timestamp_usec);
				//g_afnat_count++;
			}
			else if(strncmp(upkt->prefix, "efly_benat", strlen("efly_benat")) == 0)
			{
				pool_add_buf(upkt->payload, 60, EFLY_ULOG_BENAT, upkt->timestamp_sec, upkt->timestamp_usec);
				work_begin = 1;
				//g_benat_count++;
			}
			#endif
		}
		#endif
	}
	#endif
	return 0;
}

static int configure(struct ulogd_pluginstance *upi,
		     struct ulogd_pluginstance_stack *stack)
{
	return config_parse_file(upi->id, upi->config_kset);
}
static int init(struct ulogd_pluginstance *upi)
{
	struct ulog_input *ui = (struct ulog_input *) &upi->private;

	memset(&ef_ops, 0, sizeof(efly_ulog_ops));
	ef_ops.ef_bufslots = upi->config_kset->ces[4].u.value;
	ef_ops.ef_donum = upi->config_kset->ces[5].u.value;
	ef_ops.ef_thnum = upi->config_kset->ces[6].u.value;
	ef_ops.ef_thclk = upi->config_kset->ces[7].u.value;
	strcpy(ef_ops.ef_sendip, upi->config_kset->ces[8].u.string);
	ef_ops.ef_sendport = upi->config_kset->ces[9].u.value;
	efly_nf_init(&ef_ops);

	ui->libulog_buf = malloc(upi->config_kset->ces[0].u.value);
	if (!ui->libulog_buf) {
		ulogd_log(ULOGD_ERROR, "Out of memory\n");
		goto out_buf;
	}

	ui->libulog_h = ipulog_create_handle(
				ipulog_group2gmask(upi->config_kset->ces[1].u.value),
				upi->config_kset->ces[2].u.value);
	if (!ui->libulog_h) {
		ulogd_log(ULOGD_ERROR, "Can't create ULOG handle\n");
		goto out_handle;
	}

	ui->ulog_fd.fd = ipulog_get_fd(ui->libulog_h);
	ui->ulog_fd.cb = &ulog_read_cb;
	ui->ulog_fd.data = upi;
	ui->ulog_fd.when = ULOGD_FD_READ;

	ulogd_register_fd(&ui->ulog_fd);

	return 0;

out_handle:
	free(ui->libulog_buf);
out_buf:
	return -1;
}

static int fini(struct ulogd_pluginstance *pi)
{
	struct ulog_input *ui = (struct ulog_input *)pi->private;

	efly_nf_uninit(&ef_ops);
	ulogd_unregister_fd(&ui->ulog_fd);

	return 0;
}

struct ulogd_plugin libulog_plugin = {
	.name = "ULOG",
	.input = {
		.type = ULOGD_DTYPE_SOURCE,
		.keys = NULL,
		.num_keys = 0,
	},
	.output = {
		.type = ULOGD_DTYPE_RAW,
		.keys = output_keys,
		.num_keys = ARRAY_SIZE(output_keys),
	},
	.configure = &configure,
	.start = &init,
	.stop = &fini,
	.config_kset = &libulog_kset,
	.version = VERSION,
};

void __attribute__ ((constructor)) initializer(void)
{
	ulogd_register_plugin(&libulog_plugin);
}

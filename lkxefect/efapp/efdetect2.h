#ifndef _EF_DETECT_
#define _EF_DETECT_

#include <efio.h>
#include <efnet.h>
#include <efext.h>
#include <pthread.h>
#include <assert.h>
#include <netinet/ip.h>
#include <netinet/ip6.h>
#include <netinet/in.h>
#include <netinet/tcp.h>
#include <netinet/udp.h>
#include <fcntl.h>
#include <errno.h>

#define SESSION_PROTO_HTTP      1

#define HTTP_METHOD_GET         0x20544547          //"GET "
#define HTTP_METHOD_POST        0x54534f50          //"POST"
#define HTTP_METHOD_HEAD        0x44414548          //"HEAD"
#define HTTP_METHOD_PUT         0x20545550          //"PUT "
#define HTTP_METHOD_OPTIONS     0x20534E4F4954504F  //"OPTIONS "
#define HTTP_METHOD_DELETE1     0x454c4544          //"DELE"
#define HTTP_METHOD_DELETE2     0x4554              //"TE"
#define HTTP_METHOD_TRACE1      0x43415254          //"TRAC"
#define HTTP_METHOD_TRACE2      0x45                //"E"
#define HTTP_METHOD_CONNECT     0x205443454E4E4F43  //"CONNECT "

#define HTTP_URL_LEN            2048

typedef struct _http_info
{
    unsigned char use;
	unsigned int url_len;
	unsigned char url[HTTP_URL_LEN];
}http_info;

typedef struct _report_info
{
    unsigned int sip, dip;
    unsigned short sport, dport;
    unsigned long conn_time, tran_time, flow;
    unsigned int stats, type;
    void *detail;
}report_info;

typedef struct _attack_event
{
    unsigned int ip, attack_type;
    unsigned long attack_begin, attack_over;
    unsigned long attack_cur_pps, attack_cur_bps, attack_max_pps, attack_max_bps;
    unsigned char attack_name[32];
    struct _attack_event *next;
}attack_event;

#define MAX_DATABASE                8
#define MAX_READER                  16
#define MAX_WORKER                  64

#define READER_FLAG_INBOUND         IPCOUNT_ADD_FLAG_DIP
#define READER_FLAG_OUTBOUND        IPCOUNT_ADD_FLAG_SIP
#define READER_FLAG_ALL             (IPCOUNT_ADD_FLAG_SIP | IPCOUNT_ADD_FLAG_DIP)
#define READER_MAX_SLOT             10240

#define DATABASE_MAX_HTTP           1000000
#define DATABASE_MAX_REPORT         1024000
#define DATABASE_MAX_LOG            102400
#define DATABASE_LOG_LENGTH         2048
#define DATABASE_MAX_IP             102400

#define LOG_MAX_TARGET              16
#define LOG_NET_TCP                 1
#define LOG_NET_UDP                 2
#define LOG_NET_TIMEOUT             (9 * 1000000)
#define LOG_TYPE_IP                 1 << 0
#define LOG_TYPE_SESSION            1 << 1

#define OPERA_ADD_IP                1
#define OPERA_DEL_IP                2
#define OPERA_GET_IP                3
#define OPERA_GET_ALL               4
#define OPERA_GET_TOP_PPS_IN        500
#define OPERA_GET_TOP_PPS_OUT       600
#define OPERA_GET_TOP_BPS_IN        700
#define OPERA_GET_TOP_BPS_OUT       800
#define OPERA_GET_TOP_NEW_SESSION   900
#define OPERA_GET_TOP_NEW_HTTP      1000
typedef struct _detect_opera
{
    int *id;
    unsigned int code, arg, result;
    ip_data ip[DATABASE_MAX_IP];
    top_data top[100];
}detect_opera;


typedef struct _log_target
{
    unsigned char net_type;
    unsigned char log_type;
    unsigned char conn;
    unsigned long last_reply;
    struct sockaddr_in target;
}log_target;

typedef struct _log_content
{
    unsigned char str[DATABASE_LOG_LENGTH];
    unsigned int len;
}log_content;


typedef struct _database
{
    int id;
    ip_count_t *ict;
    session_pool *pool;
    detect_opera *opera;
    attack_event *attack_head, *attack_tail;
    http_info *hi, **phi;
    report_info *ri, *ri_timeout;
    unsigned int rii, rij, rti, rtj, phi_cur, phi_rec, sli, slj, ili, ilj;
    unsigned long ip_total, in_pps, out_pps, in_bps, out_bps;
    unsigned char detail_lock, attack_lock;
    log_content *ip_log;
    log_content *session_log;
    unsigned int log_fd[LOG_MAX_TARGET];
    unsigned char name[256];
    pthread_t collecter, sender, recorder;
}database;

typedef struct _reader
{
    int id;
    int fd;
    int flag;//    inbound outbound or in&out
    ef_slot slot[READER_MAX_SLOT];
    unsigned long pkg, flow, l_pkg, l_flow;
    database *db;
    unsigned char dev[64];
    pthread_t thread;
}reader_t;

typedef struct _worker
{
    int id;
    reader_t *reader;
    ef_slot *slot[READER_MAX_SLOT];
    int i, j, get;
    unsigned long total, finish;
    pthread_t thread;
}worker_t;


#endif

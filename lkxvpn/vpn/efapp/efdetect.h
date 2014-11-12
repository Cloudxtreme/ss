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


#define TCP_STAT_CREAT          1
#define TCP_STAT_CONN           2
#define TCP_STAT_FIN            3
#define TCP_STAT_ERROR          4
#define TCP_STAT_TIMEOUT        5
#define TCP_TIMEOUT             (360 * 1000000)

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

#define HTTP_URL_LEN            128

typedef struct _http_info
{
    unsigned int db_id;
    unsigned char use;
    unsigned long syn_time, first_ack_time, last_ack_time, fin_time, conn_time, tran_time, close_time;
    unsigned int min_seq, max_seq;
    unsigned int min_ack, max_ack;
    unsigned char stats;
    unsigned char protocol;
    unsigned int sip, dip;
    unsigned short sport, dport;
	unsigned int url_len;
	unsigned char url[HTTP_URL_LEN];
}http_info;


#define MAX_DATABASE                8
#define MAX_READER                  16
#define MAX_WORKER                  64

#define READER_FLAG_INBOUND         IPCOUNT_ADD_FLAG_DIP
#define READER_FLAG_OUTBOUND        0//IPCOUNT_ADD_FLAG_SIP
#define READER_FLAG_ALL             0
#define READER_MAX_SLOT             10240
#define LINE_LENGTH                 (MAX_READER * READER_MAX_SLOT)

#define DATABASE_MAX_SESSION        10000000
#define DATABASE_MAX_REPORT         1024000
#define DATABASE_MAX_LOG            102400
#define DATABASE_LOG_LENGTH         2048
#define DATABASE_MAX_IP             102400

#define LOG_MAX_TARGET              16
#define LOG_TYPE_IP                 1 << 0
#define LOG_TYPE_SESSION            1 << 1

#define OPERA_ADD_IP    1
#define OPERA_DEL_IP    2
#define OPERA_GET_IP    3
#define OPERA_GET_ALL   4
typedef struct _detect_opera
{
    int *id;
    unsigned int code, arg, result;
    ip_data ip[DATABASE_MAX_IP];
}detect_opera;


typedef struct _log_target
{
    unsigned char log_type;
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
    http_info *ti, **pti, **ri, **ri_timeout;
    unsigned int rii, rij, rti, rtj, pti_cur, pti_rec, sli, slj;
    unsigned long long ili, ilj, ip_log_total, ip_total;
    unsigned char lock;
    log_content *ip_log;
    log_content *session_log;
    unsigned int log_fd[LOG_MAX_TARGET];
    unsigned char name[256];
    pthread_t collecter, sender;
}database;

typedef struct _reader
{
    int id;
    int fd;
    int flag;//    inbound outbound or in&out
    ef_slot slot[READER_MAX_SLOT];
    unsigned int cur, fin;
    database *db;
    unsigned char dev[64];
    pthread_t thread;
}reader_t;

typedef struct _box
{
    int reader_id, pos;
}box_t;

typedef struct _work_line
{
    box_t boxs[LINE_LENGTH];
    int cur, fin, alive;
}work_line;

typedef struct _worker
{
    int id;
    work_line line[MAX_READER];
    pthread_t thread;
}worker_t;


#endif

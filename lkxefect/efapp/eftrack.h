#ifndef _EF_TRACK_
#define _EF_TRACK_

#define TCP_STAT_CREAT          1
#define TCP_STAT_CONN           2
#define TCP_STAT_FIN            3
#define TCP_STAT_ERROR          4
#define TCP_STAT_TIMEOUT        5
#define TCP_TIMEOUT             (360 * 1000000)

#define SESSION_PROTO_HTTP      1

#define MAX_TCP_SESSION         5000000
#define MAX_SLOT	            10000
#define MAX_REPORT	            1000000
#define MAX_IP_NUM              100000
#define MAX_URL_LEN             128

#define MAX_LOG_SERVER          10
#define LOG_TYPE_IP             1 << 1
#define LOG_TYPE_SESSION        1 << 2

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




typedef struct _http_info
{
    unsigned char use;
    unsigned long syn_time, first_ack_time, last_ack_time, fin_time, conn_time, tran_time, close_time;
    unsigned int min_seq, max_seq;
    unsigned int min_ack, max_ack;
    unsigned char stats;
    unsigned char protocol;
    unsigned int sip, dip;
    unsigned short sport, dport;
	unsigned int url_len;
	unsigned char url[MAX_URL_LEN];
}http_info;

typedef struct _send_target
{
    unsigned char complete;
    unsigned long flush;
    unsigned int gate;
    unsigned char dmac[6];
    unsigned char smac[6];
    unsigned int sip;
    unsigned int dip;
    short vlan;
    unsigned short sport;
    unsigned short dport;
    unsigned char hdr[46];
    unsigned char hdr_len;
    unsigned char arp_req[100];
    unsigned char arp_len;
}send_target;


#define OPERA_ADD_IP    1
#define OPERA_DEL_IP    2
#define OPERA_GET_IP    3
#define OPERA_GET_ALL   4
typedef struct _track_opera
{
    int *id;
    unsigned int code, arg, result;
    ip_data ip[MAX_IP_NUM];
}track_opera;

#endif

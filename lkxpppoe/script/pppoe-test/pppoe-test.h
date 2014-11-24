#define	IFCFG_PPP	"USERCTL=yes\nBOOTPROTO=dialup\nNAME=DSLppp%d\nDEVICE=ppp%d\nTYPE=xDSL\nONBOOT=no\nPIDFILE=/var/run/pppoe-ads%d.pid\nFIREWALL=NONE\nPING=.\nPPPOE_TIMEOUT=80\nLCP_FAILURE=3\nLCP_INTERVAL=20\nCLAMPMSS=1412\nCONNECT_POLL=6\nCONNECT_TIMEOUT=60\nDEFROUTE=yes\nSYNCHRONOUS=no\nETH=eth0\nPROVIDER=DSLppp%d\nUSER=%s\nPEERDNS=no\nDEMAND=no"

#define	TEMP_FILE_SIZE	1024000
#define USER_FILE_CHAP	"/etc/ppp/chap-secrets"
#define USER_FILE_PAP	"/etc/ppp/pap-secrets"
#define MAX_PPP_NUM		1000
#define DATA_SEND		1024

#define	SENDTO_IP	"192.168.22.199"
#define SENDTO_PORT	8888
#define USLEEP_TIME	10	//micro second

#define	DATAGRAM_PER_SEC	1
#define MAX_DATAGRAM		1000

#define	PPP_ADD_PPPS	"pppoe-test -A -u $username -p $password -n $number"
#define PPP_UP			"pppoe-test -U -n $number"
#define	PPP_DOWN		"pppoe-test -D -n $number"
#define	PPP_SEND		"pppoe-test -S -n $number -d $datagram"

#define	USERNAME_MEAN	"username use to login to pppoe."
#define	PASSWORD_MEAN	"password use to login to pppoe."
#define	NUMBER_MEAN	"number of ppp interfaces."
#define DATAGRAM_MEAN	"number of packets send pre second."

int adduser(char*username,char*password,char*filename);
int addppps(char*username,int number);
int addppp(char*username,int ppp);
int mysystem(char*cmd,char*result,int len);

int ifupall(int number,int progress,int progressnum);
int ifdownall(int number);

void *  sendbyeth(void*arg);
int senddatas(int num,int datagram);

int display();
int result(int first,int last);
void sigint(int signo);
long getUtime(void);

typedef int sem_t;
int Sem_Wait(sem_t semid);
int Sem_Post(sem_t semid);
void DestroySem(sem_t semid);
sem_t CreateSem(int key,int value);



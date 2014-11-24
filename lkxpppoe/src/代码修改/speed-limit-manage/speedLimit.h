//file:speedLimit.h

#define	ADDR_NUM_LIMIT	4000
#define SHM_DIR					"/var/pppoe-shm"
#define MIN_SPEED_LIMIT	131072//1024*128


typedef struct SPEED_LIMIT
{
		char 	addr[20];
		char 	user[20];
		int pid;						//pppoe pid
		int	uploadSpeed:32;
		int	downloadSpeed:32;
		int		next:32;
}speedLimit;

int createDir(char * dir);
speedLimit * get_speedLimit(void);
int get_empty_object(speedLimit * head);
int addr_add(speedLimit*shmArray,speedLimit object);
int addr_change(speedLimit*shmArray,int num,speedLimit object);
int speedLimit_cpy(speedLimit * dst,speedLimit * src);
int find_pre_by_num(speedLimit * head,int num);
int check_addr_exist(speedLimit * head,char * addr);
int check_user_exist(speedLimit * head,char * user);

int addr_init(void);
int addr_set(speedLimit object);
int addr_del(char * addr);
int addr_list(void);
speedLimit * addr_get_limit(char * addr);
int addr_set_pid(char * user , int pid);
int addr_get_pid(char * user);
int kill_user(char*user);
int check_user(char*user);


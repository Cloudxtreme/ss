#include <stdio.h>
#include <sys/sem.h>
#include <sys/ipc.h>
#include <sys/sem.h>
#include <string.h>
#include <stdlib.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <errno.h>

#include "speedLimit.h"

int main(int argc, char *argv[])
{
	int opt;
	char const *options = "IS:LCKu:d:n:";
	int isinit = 0,isset = 0,isdel = 0,islist = 0,iskill = 0,ischeck = 0;
	char addr[20];
	char user[20];
	char uploadLimit[16];
	char downloadLimit[16];
	char temp_argument[1000];
	
	memset(addr,0,20);
	memset(user,0,20);
	memset(uploadLimit,0,16);
	memset(downloadLimit,0,16);
	memset(temp_argument,0,1000);
	
	while((opt = getopt(argc, argv, options)) != -1) 
	{
		switch(opt) 
		{
			case 'I':
				isinit = 1;
				//printf("option init\n");
				break;
			case 'S':
				isset = 1;
				memset(temp_argument,0,1000);
				sprintf(temp_argument,"%s",optarg);
				if(strlen(temp_argument)>=20)
				{
					printf("Error argument:name too long\n");
					return -1;
				}
				strcpy(addr,temp_argument);
				break;
				//printf("option set\n");
				break;
			case 'D':
				isdel = 1;
				//printf("option del\n");
				break;
			case 'L':
				islist = 1;
				//printf("option list\n");
				break;
			case 'K':
				iskill = 1;
				//printf("option list\n");
				break;
			case 'C':
				ischeck = 1;
				//printf("option list\n");
				break;	
			case 'n':
				memset(temp_argument,0,1000);
				sprintf(temp_argument,"%s",optarg);
				if(strlen(temp_argument)>=16)
				{
					printf("Error argument:upload limit wrong\n");
					return -1;
				}
				strcpy(user,temp_argument);
				break;
			
			case 'u':
				memset(temp_argument,0,1000);
				sprintf(temp_argument,"%s",optarg);
				if(strlen(temp_argument)>=16)
				{
					printf("Error argument:upload limit wrong\n");
					return -1;
				}
				strcpy(uploadLimit,temp_argument);
				break;
			case 'd':
				memset(temp_argument,0,1000);
				sprintf(temp_argument,"%s",optarg);
				if(strlen(temp_argument)>=16)
				{
					printf("Error argument:download limit wrong\n");
					return -1;
				}
				strcpy(downloadLimit,temp_argument);
				break;
			default:
				//printf("Error argument:'%c' opt is no support\n",opt);
				return -1;
				break;
		}
	}
	//check input
	if((isinit+isset+isdel+islist+iskill+ischeck)!=1)
	{
		printf("Error arguments\n");
		return -1;
	}

	if(isinit)
	{
		addr_init();
		return 0;
	}
	
	if(isset)
	{
		if(strlen(addr)==0 || strlen(user)== 0 || strlen(uploadLimit) == 0 || strlen(downloadLimit)== 0)
		{
			printf("Error argument:argument miss\n");
			return -1;
		}
		int upload = atoi(uploadLimit);
		int download = atoi(downloadLimit);
		
		if(upload < MIN_SPEED_LIMIT)
		{
			upload = MIN_SPEED_LIMIT;
		}
		
		if(download < MIN_SPEED_LIMIT)
		{
			download = MIN_SPEED_LIMIT;    
		}
		
		speedLimit object;
		memset(&object,0,sizeof(speedLimit));
		strcpy(object.addr,addr);
		strcpy(object.user,user);
		object.uploadSpeed = upload;
		object.downloadSpeed = download;
		addr_set(object);
		return 0;
	}
	
	if(isdel)
	{
		if(strlen(addr)==0)
		{
			printf("Error argument:argument miss\n");
			return -1;
		}
		addr_del(addr);
		return 0;
	}
	
	if(islist)
	{
		addr_list();
		return 0;
	}

	if(iskill)
	{
		if(strlen(user)== 0)
		{
			printf("Error argument:argument miss\n");
			return -1;
		}
		kill_user(user);
	}

	if(ischeck)
	{
		if(strlen(user)== 0)
		{
			printf("Error argument:argument miss\n");
			return -1;
		}
		check_user(user);
	}
}

//***********************************
//function		init the shm
//argument-
//						num:addrs number can be store
//**********************************
int addr_init(void)
{
	key_t key;
  int shmid;
	speedLimit*shmArray;
	char * buffer = "HEAD";
	int shm_size = sizeof(speedLimit)*(ADDR_NUM_LIMIT+1);
	
	createDir(SHM_DIR);
  key = ftok(SHM_DIR,'a');
  
  shmid = shmget(key,shm_size,IPC_CREAT|0777);
  if((int)shmid == -1)
	{
		printf("Error:memory distribute error[%d]\n",errno);
		return -1;
	}
	
	shmArray = (speedLimit*)shmat(shmid,0,0);
  
  //if the shm has been init
  if(strcmp(shmArray[0].addr,buffer) == 0)
  {
  	char result[10];
  	printf("Warning:the SL-user has been Initialized.reinitialize will cover the user datas\nDo you want to continue?(yes/no)");
  	scanf("%s",result);
  	if(strcmp(result,"yes") != 0)
  	{
  		return -1;
  	}
  }
  memset(shmArray,0,shm_size);
  
  memcpy(shmArray[0].addr,buffer,strlen(buffer));
  shmArray[0].next = -1;
  printf("Initialize successfully\n",buffer);
  return 0;
}


//***********************************
//function		add or change a addr`s speed limit
//argument-
//						object:an temp speedLimit ogject use to cppy data
//**********************************
int addr_set(speedLimit object)
{
	speedLimit*shmArray = get_speedLimit();
	if((int)shmArray == -1)
	{
		return -1;
	}
	
	int num = check_addr_exist(shmArray,object.addr);
	if(num == -1)
	{
		//addr_add(shmArray,object);
		printf("Mac addr not in memory,set fail.");
	}
	else
	{
		addr_change(shmArray,num,object);
	}
	
	return 0;
}


//***********************************
//function		add or change a addr`s speed limit
//argument-
//						user:username that wnat to set pid
//						pid:pppoe pid of the user
//**********************************
int addr_set_pid(char * user , int pid)
{
	speedLimit*shmArray = get_speedLimit();
	if((int)shmArray == -1)
	{
		return -1;
	}
	
	int num = check_user_exist(shmArray,user);
	if(num == -1)
	{
		return -1;
	}
	else
	{
		shmArray[num].pid = pid;
	}
	
	return 0;
}

//***********************************
//function		add or change a addr`s speed limit
//argument-
//						user:username that wnat to get pid
//**********************************
int addr_get_pid(char * user)
{
	speedLimit*shmArray = get_speedLimit();
	if((int)shmArray == -1)
	{
		return -1;
	}
	
	int num = check_user_exist(shmArray,user);
	if(num == -1)
	{
		return -1;
	}
	else
	{
		return shmArray[num].pid;
	}
}

//***********************************
//function		add or change a addr`s speed limit
//argument-
//						addr:addr to be found
//**********************************
speedLimit * addr_get_limit(char * addr)
{
	speedLimit*shmArray = get_speedLimit();
	if((int)shmArray == -1)
	{
		return (speedLimit *)-1;
	}
	
	int num = check_addr_exist(shmArray,addr);
	if(num == -1)
	{
		return (speedLimit *)-1;
	}
	else
	{
		return &shmArray[num];
	}
}


//***********************************
//function		init the shm
//argument-
//						addr:Unique identify a addr
//**********************************
int addr_del(char * addr)
{
	speedLimit*shmArray = get_speedLimit();
	if((int)shmArray == -1)
	{
		return -1;
	}
	
	int num = check_addr_exist(shmArray,addr);
	
	if(num == -1)
	{
		printf("Operation error:addr to be delete not in shm,nothing to be done\n");
		return -1;
	}
	
	int pre = find_pre_by_num(shmArray,num);
	
	//delete
	shmArray[pre].next = shmArray[num].next;
	memset(&shmArray[num],0,sizeof(shmArray));
	return 0;
}

//***********************************
//function		print all the speed limit datas
//argument-
//						
//**********************************
int addr_list(void)
{
	speedLimit*shmArray = get_speedLimit();
	if((int)shmArray == -1)
	{
		return -1;
	}
	
	speedLimit * tmp = shmArray;
	printf("%-20s%-20s%-20s%-20s\n","username","pid","uploadLimit","downloadLimit");
	while(tmp->next != -1)
	{
		tmp = &shmArray[tmp->next];
		printf("%-20s%-20d%-20d%-20d\n",tmp->user,tmp->pid,tmp->uploadSpeed,tmp->downloadSpeed);
	}
	return 0;
}

//***********************************
//function		create a directory if dir is not a directory
//argument-
//						dir:path of directory

//**********************************
int createDir(char * dir)
{
	struct stat filestat;
	if(stat(dir,&filestat) != 0 || !S_ISDIR(filestat.st_mode))
	{
		//create dir
		printf("Create directory %s for shared memory use\n");
		char command[40] = "mkdir ";
		strcat(command,dir);
	  system(command);
	}
	return 0;
}


//***********************************
//function		get an object of speedLimit,witch now not in use
//argument-
//						head:the head of the speedLimit linked list

//**********************************
int get_empty_object(speedLimit * head)
{
	int num = 1;
	speedLimit * tmp = head;
	while(tmp->next!=-1 && tmp->next == num)
	{
		tmp = &head[tmp->next];
		num++;
	}
	return num;
}

//***********************************
//function		get the head of the speedLimit from shm
//argument-
//						

//**********************************
speedLimit * get_speedLimit(void)
{
	key_t key;
  int shmid;
	speedLimit*shmArray;
	char * buffer = "HEAD";
	
	int shm_size = sizeof(speedLimit)*(ADDR_NUM_LIMIT+1);
  key = ftok(SHM_DIR,'a');
  shmid = shmget(key,shm_size,IPC_CREAT|0777);
	
	shmArray = (speedLimit*)shmat(shmid,0,0);
	if((int)shmArray == -1)
	{
		printf("Error operation:shm has not be initialized\n");
		return (speedLimit *)-1;
	}
	
	if(strcmp(shmArray->addr,buffer) != 0)
	{
		printf("Error operation:shm has not be initialized\n");
		shmdt(shmArray);
		return (speedLimit *)-1;
	}
	
	return shmArray;
}


//***********************************
//function		check the addr exist or not
//argument-
//						head:head of speedLimit list
//						addr:addr use to check				

//**********************************
int check_addr_exist(speedLimit * head,char * addr)
{
	int num = -1;
	if(head->next == -1)
	{
		return -1;
	}
	speedLimit * tmp = head;
	do
	{
		num = tmp->next;
		tmp = &head[num];
		if(strcmp(tmp->addr,addr) == 0)
		{
			return num;
		}
	}
	while(tmp->next != -1);
	return -1;
}


//***********************************
//function		check the addr exist or not
//argument-
//						head:head of speedLimit list
//						user:user use to check				
//**********************************
int check_user_exist(speedLimit * head,char * user)
{
	int num = -1;
	if(head->next == -1)
	{
		return -1;
	}
	speedLimit * tmp = head;
	do
	{
		num = tmp->next;
		tmp = &head[num];
		if(strcmp(tmp->user,user) == 0)
		{
			return num;
		}
	}
	while(tmp->next != -1);
	return -1;
}


//***********************************
//function		copy an speedLimit object
//argument-
//						dst:object to copy to
//						src:object to copy from	

//**********************************
int speedLimit_cpy(speedLimit * dst,speedLimit * src)
{
	//memset(dst,0,sizeof(speedLimit));
	memcpy(dst->addr,src->addr,20);
	dst->pid = src->pid;
	dst->uploadSpeed = src->uploadSpeed;
	dst->downloadSpeed = src->downloadSpeed;
	return 0;
}


//***********************************
//function		add a addr`s speed limit
//argument-
//						object:an temp speedLimit ogject use to cppy data
//**********************************
int addr_add(speedLimit*shmArray,speedLimit object)
{
	int num;
	
	//get an empty object
	num = get_empty_object(shmArray);
	
	//init data
	memset(&shmArray[num],0,sizeof(speedLimit));
	speedLimit_cpy(&shmArray[num],&object);
	shmArray[num].next = shmArray[num-1].next;
	shmArray[num-1].next = num;
	
	return 0;
}


//***********************************
//function		change a addr`s speed limit
//argument-
//						object:an temp speedLimit ogject use to cppy data
//**********************************
int addr_change(speedLimit*shmArray,int num,speedLimit object)
{
	memcpy(shmArray[num].user,object.user,20);
	shmArray[num].uploadSpeed = object.uploadSpeed;
	shmArray[num].downloadSpeed = object.downloadSpeed;
	return 0;
}

//***********************************
//function		return the num of object,whose next equal to num giving
//argument-
//						head:head of speedLimit list
//						num:addr use to check				

//**********************************
int find_pre_by_num(speedLimit * head,int num)
{
	speedLimit * tmp = head;
	int pre = 0;
	
	if(head->next == -1)
	{
		return -1;
	}
	
	while(tmp->next != -1)
	{	
		if(tmp->next == num)
		{
			return pre;
		}
		pre = tmp->next;
		tmp = &head[pre];
	}
	return -1;
}

int kill_user(char * user)
{
	speedLimit*shmArray = get_speedLimit();
	if((int)shmArray == -1)
	{
		return -1;
	}
	
	int num = check_user_exist(shmArray,user);
	if(num == -1)
	{
		return -1;
		printf("user[%s] not found\n",user);
	}
	else
	{	
		int pid = shmArray[num].pid;
		char command[100];
		sprintf(command,"kill -9 %d",pid);
		if(pid == 0 || pid == -1)
		{
			printf("user is off\n",user);
			return -1;
		}
		int result = system(command);
		if(result == -1 || result == 127)
		{
			//error
			printf("kill user[%s] fail\n",user);
			return -1;
		}
		else
		{
//			printf("execute command:%s\n",command);
			shmArray[num].pid = -1;
		}

	}
	
	return 0;
}

int check_user(char*user)
{
	speedLimit*shmArray = get_speedLimit();
	if((int)shmArray == -1)
	{
		return -1;
	}
	
	int num = check_user_exist(shmArray,user);
	if(num == -1)
	{
		exit(1);
	}
	else
	{	
		int pid = shmArray[num].pid;
		char command[100];
		sprintf(command,"kill -9 %d",pid);
		if(pid == 0 || pid == -1)
		{
			exit(1);
		}
		else
		{	exit(0);}
	}
}

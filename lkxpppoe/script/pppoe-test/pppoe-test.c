#include <stdio.h>
#include <string.h>
#include <memory.h>
#include <unistd.h>
#include "pppoe-test.h"
#include <stdlib.h>
#include <fcntl.h>
#include <net/if.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <signal.h>
#include <stdio.h>
#include <sys/sem.h>
#include <sys/ipc.h>
#include <sys/sem.h>
#include <pthread.h>
#include <math.h>
int alive = 1;
long begin;
int packets[MAX_PPP_NUM];

int main(int argc, char **argv)
{
	int opt;
	char result[TEMP_FILE_SIZE];
	int send=0, add=0,up=0,down=0,hasuser=0,haspass=0,hasnum=0;
	char username[50] = {0};
	char password[50] = {0};
	int number = 0;
	int datagram = DATAGRAM_PER_SEC;

	memset(packets,0,sizeof(packets));
	signal(SIGINT,sigint);
	char *options = "AUDSu:p:n:d:";
	begin = getUtime();
	while((opt = getopt(argc, argv, options)) != -1)
	{
		switch(opt)
		{
			case 'A':
				add = 1;
				break;
			case 'U':
				up = 1;
				break;
			case 'D':
				down = 1;
				break;
			case 'S':
				send = 1;
				break;
			case 'u':
				sprintf(username,"%s",optarg);
				hasuser = 1;
				break;
			case 'p':
				sprintf(password,"%s",optarg);
				haspass = 1;
				break;
			case 'n':
				number = atoi(optarg);
				if(number<1||number>MAX_PPP_NUM)
				{
					printf("number error\n");
					return -1;
				}
				hasnum = 1;
				break;
			case 'd':
				datagram = atoi(optarg);
				if(datagram<DATAGRAM_PER_SEC || datagram>MAX_DATAGRAM)
				{
					datagram = DATAGRAM_PER_SEC;
				}
				break;
			default:
				break;
		}
	}
//	printf("username:%s\n",username);
//	printf("password:%s\n",password);
//	printf("number:%s\n",number);
	if((add+up+down+send) != 1)
	{
		display();
		return -1;
	}

	if(add && hasuser && haspass && hasnum)
	{
		//add user and ppps
		adduser(username,password,USER_FILE_CHAP);
		adduser(username,password,USER_FILE_PAP);
		addppps(username,number);
	}
	else if(up && hasnum)
	{
		//up the ppps
		int i,pid;
		int pids[10];
		for(i = 0;i<10;i++)
		{
			pid = fork();
			if(pid == 0)
			{
				ifupall(number,10,i);
				break;
			}
			else
			{
				pids[i] = pid;
			}
		}
		if(pid>0)
		{
			for(i = 0;i<10;i++)
			{
				waitpid(pids[i],NULL,0);	
			}
		}
	}
	else if(down && hasnum)
	{
		//down the ppps
		ifdownall(number);
	}
	else if(send && hasnum)
	{
		//send data
		int temp[number];
		senddatas(number,datagram);
		//printf("send compete\n");
	}
	else
	{
		printf("argument miss\n");
		return -1;
	}
	return 0;
}

int adduser(char*username,char*password,char*filename)
{
	int fd,size;
	char result[TEMP_FILE_SIZE];
	memset(result,0,TEMP_FILE_SIZE);
	char cmd[1024];
	sprintf(cmd,"sed '/^[\t \"]*%s[\t ]*/'d %s",username,filename);
	size = mysystem(cmd,result,TEMP_FILE_SIZE);
	char line[100];
	sprintf(line,"\n\"%s\"\t*\t\"%s\"",username,password);
	strcat(result,line);
	size = strlen(result); 
	fd = open(filename,O_RDWR|O_TRUNC);
	if(fd == -1)
	{
		printf("can not open file:%s\n",filename);
		return -1;
	}
	write(fd,result,size);
	close(fd);
    sprintf(cmd,"sed -i /^$/d %s",filename);
	system(cmd);
	return 0;
}

int addppps(char*username,int number)
{
	adduser("test","he",USER_FILE_CHAP);
	adduser("test","he",USER_FILE_PAP);
	int i;
	for(i = 0;i<number;i++)
	{
		addppp(username,i);
	}
	printf("Add %d ppp interface successfully\n",number);
	return 0;
}

int addppp(char*username,int ppp)
{
	char content[TEMP_FILE_SIZE];
	char filename[50];
	int fd;
	int size;
	sprintf(content,IFCFG_PPP,ppp,ppp,ppp+1,ppp,username);
	size = strlen(content);
	sprintf(filename,"/etc/sysconfig/network-scripts/ifcfg-ppp%d",ppp);
	fd = open(filename,O_RDWR|O_TRUNC|O_CREAT);
	if(fd == -1)
	{
		printf("can not open file:%s\n",filename);
		return -1;
	}
	write(fd,content,size);
	close(fd);
	return 0;
}

int mysystem(char*cmd,char*result,int len)
{
	int fd[2];
	pid_t pid;
	int n = -1,count;

	memset(result,0,len);
	if(pipe(fd)<0)
	{
		return -1;
	}
	
	pid = fork();
	if(pid<0)
	{
		printf("fork error\n");
		return -1;
	}
	else if(pid>0)
	{
//		printf("parent\n");
		close(fd[1]);
		count = 0;
		while((n = read(fd[0],result+count,len))>0)
		{
//			printf("read byte:%s",result);
			count += n;
			if(count>=len)
			{
				break;
			}
		}
		close(fd[0]);
		wait(0);
//		printf("after wait\n");
	}
	else
	{
			
		close(fd[0]);
		if(fd[1] != STDOUT_FILENO)
		{
			if(dup2(fd[1],STDOUT_FILENO) != STDOUT_FILENO)
			{
				return -1;
			}
			close(fd[1]);
		}
		
		//printf("child\n");
		
		if(execl("/bin/sh","sh","-c",cmd,(char*)0) == -1)
		{
			return -1;
		}
		
	}
	return n;
}

int ifupall(int number,int progress,int progressnum)
{
	int i;
	char cmd[100];
	for(i = 0;i<number;i++)
	{
		if(i%progress != progressnum)
		{
			continue;
		}
		memset(cmd,0,100);
		sprintf(cmd,"/sbin/ifup ppp%d",i);
		system(cmd);
		
		printf("handle ppp%d complete\n",i);
	}
	return 0;
}

int ifdownall(int number)
{
	int i;
	char cmd[100];
	for(i = 0;i<number;i++)
	{
		memset(cmd,0,100);
		sprintf(cmd,"/sbin/ifdown ppp%d",i);
		system(cmd);
		printf("handle ppp%d complete\n",i);
	}
	return 0;
}

void * sendbyeth(void*arg)
{
	int*arguments = (int*)arg;
	int ppp = arguments[0];
	int sleeptime = arguments[1];
	int s,j;
	int n;
	int count = 0;
	struct sockaddr_in server_addr,client_addr;
	char eth[10];
	char buffer[DATA_SEND+1];
	for(j=0;j<DATA_SEND;j++)
	{
		buffer[j] = '0';
	}
	buffer[DATA_SEND] = 0;
	sprintf(eth,"ppp%d",ppp);

	struct ifreq interface;
	char *inf = eth;
	strncpy(interface.ifr_name, inf, IFNAMSIZ);

	memset(&server_addr,0,sizeof(server_addr));
	memset(&client_addr,0,sizeof(client_addr));

	s = socket(AF_INET,SOCK_DGRAM,0);

	n = setsockopt(s, SOL_SOCKET, SO_BINDTODEVICE,(char *)&interface, sizeof(interface));
	if(n == -1)
	{
		printf("setsockopt fail\n");
		pthread_exit(NULL);
	}
	server_addr.sin_family = AF_INET;
	server_addr.sin_addr.s_addr = inet_addr(SENDTO_IP);
	server_addr.sin_port = htons(SENDTO_PORT);

	while(alive)	
	{
		//printf("send data:%d\n",n);
		n = sendto(s,buffer,sizeof(buffer),0,(struct sockaddr*)&server_addr,sizeof(server_addr));
		if(!n)
		{
			printf("%s:sendto error\n",eth);
		}
		else
		{
			count++;
		}
		usleep(sleeptime);
	}
//	printf("ppp%d:%d\n",ppp,count);
	packets[ppp] = count;
	pthread_exit(NULL);
}

int senddatas(int num,int datagram)
{	
	int i,p;
	int pid,key;
	int semid;
	int sleeptime =(int)(1000000/datagram);

	key = ftok("/etc/ppp",'1');
	if(key == -1)
	{
		printf("create key fail\n");
		return -1;
	}
	semid = CreateSem(key,1);
	//int packets[num];
	pthread_t threads[num];
	int pnum = (int)(ceil((float)num/100));
	int ret,users;
	int first,last;
	int pids[pnum];
	for(p=0;p<pnum;p++)
	{
		first = p*100;
		if(pnum == (p+1))
		{
			last = num;
		}
		else
		{
			last = (p+1)*100;
		}
		pids[p] = fork();
		if(pids[p]<0)
		{
			printf("fork error\n");
			return -1;
		}
		else if(pids[p] == 0)
		{
			for(i=first;i<last;i++)
			{
				int arguments[2];
				arguments[0] = i;
				arguments[1] = sleeptime;
		
				ret = pthread_create(&threads[i],NULL,(void*)sendbyeth,arguments);
				if(ret)
				{
					printf("pthread error:%d",ret);
					printf("the limit number of user thread for this machine is %d\n",i);
					break;
				}

				usleep(USLEEP_TIME);

			}
			users = i;
			printf("ppp%d-ppp%d user threads create complete\n",first,users);
			for(i=first;i<users;i++)
			{
				pthread_join(threads[i],NULL);
			}

			Sem_Wait(semid);
			result(first,users);
			Sem_Post(semid);
			exit(0);
		}
		else
		{
			usleep(USLEEP_TIME-10);		
		}
	}	
	
	int m;
	for(m=0;m<pnum;m++)
	{
		int pid = waitpid(pids[m],NULL,0);
	}

	DestroySem(semid);
	return 0;
}

void sigint(int signo)
{
	alive = 0;
}

int display()
{
	printf("***********************************************************\n");
	printf("create interfaces:%s\n",PPP_ADD_PPPS);
	printf("start up interfaces:%s\n",PPP_UP);
	printf("shut down interfaces:%s\n",PPP_DOWN);
	printf("send datas to interfaces:%s\n",PPP_SEND);

	printf("***********************************************************\n");
	printf("$username:%s\n$password:%s\n$number:%s\n$datagram:%s\n",USERNAME_MEAN,PASSWORD_MEAN,NUMBER_MEAN,DATAGRAM_MEAN);
	return 0;
}

int result(int first,int last)
{
	int i;
	for(i=first;i<last;i++)
	{
		printf("ppp%-4d:%-10d",i,packets[i]);
		if(i%4==3)
		{
			printf("\n");
		}
	}
	printf("\n");
	int size = DATA_SEND;
	long end = getUtime();
	long usetime = end - begin;
	printf("Size pre packet :%d Byte",size);
	printf("Total time %ld ms\n\n",usetime);
	return 0;
}


long getUtime(void)
{
	struct timeval tv;
	gettimeofday(&tv, NULL);
	long utime = 1000*tv.tv_sec + (long)(tv.tv_usec/1000);
	return utime;
}


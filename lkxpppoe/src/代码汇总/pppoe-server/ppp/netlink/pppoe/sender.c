#include <sys/stat.h>
#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/socket.h>
#include <sys/types.h>
#include <string.h>
#include <asm/types.h>
#include <linux/netlink.h>
#include <linux/socket.h>
#include <sys/socket.h>
#include <netinet/in.h>

#define BIND_IP "127.0.0.1"
#define BIND_PORT 9999

#define MASK_NUM 24
#define TIME_UPDATE_COUNT 100;
#define MAX_PAYLOAD 1024 /* maximum payload size*/
struct sockaddr_nl src_addr, dest_addr;
struct nlmsghdr *nlh = NULL;
struct iovec iov;
int sock_fd;
struct msghdr msg;

int time_count = 0;
time_t now_time = 0;
time_t lastest_time[(1 << MASK_NUM)+1]={0};
	
int handle_src(unsigned long src);
time_t getTime();
void* handle_query(void *arg);
unsigned long getIndex(unsigned long src);

int main(int argc, char* argv[])
{
	pthread_t pt_sends;
    sock_fd = socket(PF_NETLINK, SOCK_RAW, 21);
    memset(&msg, 0, sizeof(msg));
    memset(&src_addr, 0, sizeof(src_addr));
    src_addr.nl_family = AF_NETLINK;
    src_addr.nl_pid = getpid(); /* self pid */
    src_addr.nl_groups = 0; /* not in mcast groups */
    bind(sock_fd, (struct sockaddr*)&src_addr, sizeof(src_addr));
    memset(&dest_addr, 0, sizeof(dest_addr));
    dest_addr.nl_family = AF_NETLINK;
    dest_addr.nl_pid = 0; /* For Linux Kernel */
    dest_addr.nl_groups = 0; /* unicast */

    nlh=(struct nlmsghdr *)malloc(NLMSG_SPACE(MAX_PAYLOAD));
    /* Fill the netlink message header */
    nlh->nlmsg_len = NLMSG_SPACE(MAX_PAYLOAD);
    nlh->nlmsg_pid = getpid(); /* self pid */
    nlh->nlmsg_flags = 0;
    /* Fill in the netlink message payload */
    strcpy(NLMSG_DATA(nlh), "pid");

    iov.iov_base = (void *)nlh;
    iov.iov_len = nlh->nlmsg_len;
    msg.msg_name = (void *)&dest_addr;
    msg.msg_namelen = sizeof(dest_addr);
    msg.msg_iov = &iov;
    msg.msg_iovlen = 1;

	if(pthread_create(&pt_sends,NULL,(void*)handle_query, NULL) != 0)
    {
    	printf("create pt_sends error");
    	return 0;
    }

    sendmsg(sock_fd, &msg, 0);

    /* Read message from kernel */
    memset(nlh, 0, NLMSG_SPACE(MAX_PAYLOAD));
    while(1)
    {
    	recvmsg(sock_fd, &msg, 0);
    	handle_src(*((unsigned int *)NLMSG_DATA(nlh)));
    }
    
     /* Close Netlink Socket */
    close(sock_fd);
}

unsigned long getIndex(unsigned long src)
{
	return (src & (((1<<MASK_NUM)-1)<<(32-MASK_NUM))) >> (32-MASK_NUM);	
}

int handle_src(unsigned long src)
{
	unsigned long index= getIndex(src);
	time_t now = getTime();
	lastest_time[index] = now;
	//printf("write src:%u\n",src);
	//printf("write index:%u\n",index);
}

time_t getTime()
{
	if(time_count == 0)
	{
		now_time = time(0);
	}
	
	time_count = (time_count+1)%TIME_UPDATE_COUNT;
	return now_time;
}

void* handle_query(void *arg)
{
	struct sockaddr_in bind_addr,client;
	socklen_t sin_size = sizeof(struct sockaddr_in);
	int receiver,sender;
	__be32 sip;
	time_t _now;
	fd_set read_fds;
	struct timeval wait_time;
	char recvmsg[MAX_PAYLOAD + 1];
	char output [100];
	
	bzero(&bind_addr, sin_size);
	bind_addr.sin_addr.s_addr = inet_addr(BIND_IP);
	bind_addr.sin_port = htons(BIND_PORT);
    bind_addr.sin_family = AF_INET;
	
	if ((sender = socket(PF_INET, SOCK_DGRAM, 0)) == -1)
    {
        perror("socket create error!\n");
        exit(1);
    }
	
	if ((receiver = socket(PF_INET, SOCK_DGRAM, 0)) == -1)
    {
        perror("socket create error!\n");
        exit(1);
    }
    
    if (bind(receiver, (struct sockaddr *) &bind_addr, sin_size) != 0)
    {
        perror("bind error\n");
        exit(1);
    }
	
	while(1)
	{
	    //printf("answer come!\n");
	    sin_size = sizeof(struct sockaddr_in);
		memset(recvmsg, 0, sizeof(recvmsg));
		memset(&client, 0,sin_size);
		
        int num = recvfrom(receiver, recvmsg, MAX_PAYLOAD, 0,(struct sockaddr*)&client, &sin_size);
        if(num >0 && num<=MAX_PAYLOAD)
        {
        	recvmsg[num] = 0;
        	int result = sscanf(recvmsg,"%d.%d.%d.%d", &(((unsigned char *)&sip)[0]), &(((unsigned char *)&sip)[1]), &(((unsigned char *)&sip)[2]),&(((unsigned char *)&sip)[3]));
        	if(result != 0)
        	{
        		unsigned long index= getIndex(sip);
        		//printf("read msg:%s\n",recvmsg);
        		time_t now = getTime();
        		time_t latest = lastest_time[index];
			printf("%s -- %d --  %d -- %d\n",recvmsg,index,now,latest);
        		
        		sprintf(output,"%u",now-latest);
        		sendto(receiver, output, strlen(output), 0, (struct sockaddr*)&client, sizeof(struct sockaddr_in));
        	}
        }
        
	}
	return NULL;
}

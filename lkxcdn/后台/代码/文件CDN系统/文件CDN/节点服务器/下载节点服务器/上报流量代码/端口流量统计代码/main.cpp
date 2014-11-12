#include <unistd.h>
#include <assert.h>
#include <ctype.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <arpa/inet.h>
#include <arpa/nameser.h>
#include <netinet/if_ether.h>
#include <sys/time.h>
#include <time.h>
#include <linux/ip.h>
#include <netinet/tcp.h>
#include <pcap.h>

#define PCAP_SNAPLEN 65535
volatile bool bexit = false;

char myhost[20] = {0};
unsigned int port1 = 0, port2 = 0;
unsigned int portrate[65536][2] = {0};

int handle_data(const u_char * pkt, int len)
{
	struct iphdr *ipptr;
	struct tcphdr *tcpptr;
	unsigned short sport, dport;
	
	ipptr = (struct iphdr *)( pkt + sizeof(struct ether_header) );
	if( ipptr->protocol != 6 ) {
		return 0;
	}

	tcpptr = (struct tcphdr *)( (char*)ipptr + ipptr->ihl*4 );
	sport = ntohs(tcpptr->source);
	dport = ntohs(tcpptr->dest);

	if( ipptr->saddr == inet_addr(myhost) && ipptr->daddr == inet_addr(myhost) ) 
	{
		if( sport >= port1 && sport <= port2 ) {
			portrate[sport][0] += len; //out
		}
		
		if( dport >= port1 && dport <= port2 ) {
			portrate[dport][1] += len; //in
		}
	}
	else
	{
		if( ipptr->saddr == inet_addr(myhost) && sport >= port1 && sport <= port2 ) {
			portrate[sport][0] += len; //out
		}
		else if( ipptr->daddr == inet_addr(myhost) && dport >= port1 && dport <= port2 ) {
			portrate[dport][1] += len; //in
		}
	}
	
	return 0;
}

int main(int argn, char **argv)
{
	char errbuf[PCAP_ERRBUF_SIZE];
	pcap_t *pcap = NULL;
	struct bpf_program fp;
	struct pcap_pkthdr header;
	const u_char *packet = NULL;
	struct ether_header *eptr;
	struct timeval tv;
	unsigned int st=0, et=0;
	unsigned int i = 0;
	
	if( argn != 5 ) {
		return 0;		
	}
	
	strcpy(myhost, argv[2]);
	port1 = atoi(argv[3]);
	port2 = atoi(argv[4]);
	if( port1 > port2 || port1 <= 0 ) {
		return 0;
	} 
	
	pcap = pcap_open_live(argv[1], PCAP_SNAPLEN, 0, 1000, errbuf);
	
	memset(&fp, '\0', sizeof(fp));
	int ret = pcap_compile(pcap, &fp, "tcp", 1, 0);
	if( ret < 0 ) {
		goto GOTO_OUT;
	}

	ret = pcap_setfilter(pcap, &fp);
	if( ret < 0 ) {
		goto GOTO_OUT;
	}
	
	pcap_setnonblock(pcap, 1, errbuf);

	while( ! bexit )
	{
		packet = pcap_next(pcap, &header);
		if( ! st )
		{
			gettimeofday(&tv,NULL);
			st = tv.tv_sec * 1000000 + tv.tv_usec;
		}
		gettimeofday(&tv,NULL);
		et = tv.tv_sec * 1000000 + tv.tv_usec;
		if( (et - st) >= 1000000 ) {
			break;
		}

		if( ! packet ) {
			continue;
		}
		
		eptr=(struct ether_header *)packet;
		if( ntohs(eptr->ether_type) != ETHERTYPE_IP ) {
			continue;
		}

		handle_data(packet, header.len);		
	}
	
	for( i = 0; i < 65535; i++ ) 
	{ 
		if( portrate[i][0] || portrate[i][1] ) {
			printf("%d %d %d\n", i, portrate[i][0], portrate[i][1]);
		}
	}

GOTO_OUT:
	
	pcap_close(pcap);
	
	return(0);
}

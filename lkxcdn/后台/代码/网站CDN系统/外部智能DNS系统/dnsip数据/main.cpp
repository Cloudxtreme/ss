#include <unistd.h>
#include <stdio.h>
#include <pcap.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <netinet/if_ether.h>
#include <arpa/inet.h>
#include <linux/ip.h>
//#include <linux/tcp.h>
#include <netinet/tcp.h>
#include <string.h>
#include <stdlib.h>
#include <errno.h>
#include <iostream>
#include <fstream>

#include <map>
#include <string>
typedef std::map<std::string, unsigned int> dnsinfo_t;
dnsinfo_t dnsinfo;

#define PCAP_SNAPLEN	65535

void sync_data(char *szfilename)
{
	dnsinfo_t::iterator it;

	//printf("count = %d \n", dnsinfo.size());
	std::ofstream o_file;
	o_file.open(szfilename);

	for( it = dnsinfo.begin(); it != dnsinfo.end(); it++ ) 
	{
		//printf("%s %d \n", it->first.c_str(), it->second);
		o_file << it->first.c_str() << " " << it->second << std::endl;
	}
	o_file.close();
}

int handle_data(const u_char * pkt, int len)
{
	struct iphdr *ipptr;
	struct in_addr saddr, daddr;
	char sz_saddr[20], sz_daddr[20];

	ipptr = (struct iphdr *)( pkt + sizeof(struct ether_header) );

	saddr.s_addr=ipptr->saddr;
	daddr.s_addr=ipptr->daddr;

	strcpy(sz_saddr, inet_ntoa(saddr));
	strcpy(sz_daddr, inet_ntoa(daddr));

    //printf("%s => %s \n", sz_saddr, sz_daddr);
	unsigned *pcnt = &dnsinfo[sz_saddr];
	(*pcnt)++;

	return 0;
}

int main(int argc, char **argv)
{
	char errbuf[PCAP_ERRBUF_SIZE];
	pcap_t *pcap = NULL;
	struct bpf_program fp;
	struct pcap_pkthdr header;
	const u_char *packet = NULL;
	struct ether_header *eptr;
	int ret;
	unsigned int last_time, now_time;	
	char *host;
	char szcomp[1024];
	int timeout;
	char *szfilename;

	if( argc != 5 ) 
	{
		printf("app interface ip timeout datafilename\n");
		return 0;
	}
	host = argv[2];
	sprintf(szcomp, "udp dst port 53 and dst host %s", host);
	printf("%s\n", szcomp);
	
	timeout = atoi(argv[3]);
	szfilename = argv[4];
	
	pcap = pcap_open_live(argv[1], PCAP_SNAPLEN, 1, 0, errbuf);

	printf("pcap_datalink %d \n", pcap_datalink(pcap)); 

    memset(&fp, '\0', sizeof(fp));
    ret = pcap_compile(pcap, &fp, szcomp, 1, 0); 
    if( ret < 0 ) 
    {   
        printf("pcap_compile failed\n");
        goto GOTO_OUT;
    }   

    ret = pcap_setfilter(pcap, &fp);
    if( ret < 0 ) 
    {   
        printf("pcap_setfilter failed\n");
        goto GOTO_OUT;
    }   

		last_time = time(NULL);

    while( 1 )
    {
        packet = pcap_next(pcap, &header);
		if( ! packet || ! header.len ) {
			continue;
		}

		eptr=(struct ether_header *)packet;
		if( ntohs(eptr->ether_type) != ETHERTYPE_IP ) {
			continue;
		}

        //printf("%d[%d]\n", header.caplen, header.len);
		handle_data(packet, header.len);

		now_time = time(NULL);
		if( now_time - last_time >= timeout )
		{
			sync_data(szfilename);
			last_time = now_time;
		}
    }

GOTO_OUT:
    
    pcap_close(pcap);

	return 0;
}


/*
 *
 * (C) 2005-12 - Luca Deri <deri@ntop.org>
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * VLAN support courtesy of Vincent Magnin <vincent.magnin@ci.unil.ch>
 *
 */

#define _GNU_SOURCE
#include <signal.h>
#include <sched.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <string.h>
#include <unistd.h>
#include <sys/mman.h>
#include <errno.h>
#include <sys/poll.h>
#include <netinet/in_systm.h>
#include <netinet/in.h>
#include <netinet/ip.h>
#include <net/ethernet.h>     /* the L2 protocols */
#include <sys/time.h>
#include <time.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <pcap.h>

#include "pfring.h"

struct packet {
  u_int16_t len;
  u_int64_t ticks_from_beginning;
  char *pkt;
  struct packet *next;
};

struct ip_header {
#if BYTE_ORDER == LITTLE_ENDIAN
  u_int32_t	ihl:4,		/* header length */
    version:4;			/* version */
#else
  u_int32_t	version:4,			/* version */
    ihl:4;		/* header length */
#endif
  u_int8_t	tos;			/* type of service */
  u_int16_t	tot_len;			/* total length */
  u_int16_t	id;			/* identification */
  u_int16_t	frag_off;			/* fragment offset field */
  u_int8_t	ttl;			/* time to live */
  u_int8_t	protocol;			/* protocol */
  u_int16_t	check;			/* checksum */
  u_int32_t saddr, daddr;	/* source and dest address */
};

/*
 * Udp protocol header.
 * Per RFC 768, September, 1981.
 */
struct udp_header {
  u_int16_t	source;		/* source port */
  u_int16_t	dest;		/* destination port */
  u_int16_t	len;		/* udp length */
  u_int16_t	check;		/* udp checksum */
};

struct packet *pkt_head = NULL;
pfring  *pd;
pfring_stat pfringStats;
char *in_dev = NULL;
u_int8_t wait_for_packet = 1, do_shutdown = 0;
u_int64_t num_pkt_good_sent = 0, last_num_pkt_good_sent = 0;
u_int64_t num_bytes_good_sent = 0, last_num_bytes_good_sent = 0;
struct timeval lastTime, startTime;
int reforge_mac = 0;
char mac_address[6];
int send_len = 60;
int if_index = -1;

#define DEFAULT_DEVICE     "eth0"

typedef u_int64_t ticks;

/* *************************************** */
/*
 * The time difference in millisecond
 */
double delta_time (struct timeval * now,
		   struct timeval * before) {
  time_t delta_seconds;
  time_t delta_microseconds;

  /*
   * compute delta in second, 1/10's and 1/1000's second units
   */
  delta_seconds      = now -> tv_sec  - before -> tv_sec;
  delta_microseconds = now -> tv_usec - before -> tv_usec;

  if(delta_microseconds < 0) {
    /* manually carry a one from the seconds field */
    delta_microseconds += 1000000;  /* 1e6 */
    -- delta_seconds;
  }
  return((double)(delta_seconds * 1000) + (double)delta_microseconds/1000);
}

/* *************************************** */

void print_stats() {
  double deltaMillisec, currentThpt, avgThpt, currentThptBytes, avgThptBytes;
  struct timeval now;
  char buf1[64], buf2[64], buf3[64], buf4[64], buf5[64];

  gettimeofday(&now, NULL);
  deltaMillisec = delta_time(&now, &lastTime);
  currentThpt = (double)((num_pkt_good_sent-last_num_pkt_good_sent) * 1000)/deltaMillisec;
  currentThptBytes = (double)((num_bytes_good_sent-last_num_bytes_good_sent) * 1000)/deltaMillisec;
  currentThptBytes /= (1000*1000*1000)/8;

  deltaMillisec = delta_time(&now, &startTime);
  avgThpt = (double)(num_pkt_good_sent * 1000)/deltaMillisec;
  avgThptBytes = (double)(num_bytes_good_sent * 1000)/deltaMillisec;
  avgThptBytes /= (1000*1000*1000)/8;

  fprintf(stdout, "TX rate: [current %s pps/%s Gbps][average %s pps/%s Gbps][total %s pkts]\n",
	  pfring_format_numbers(currentThpt, buf1, sizeof(buf1), 1),
	  pfring_format_numbers(currentThptBytes, buf2, sizeof(buf2), 1),
	  pfring_format_numbers(avgThpt, buf3, sizeof(buf3), 1),
	  pfring_format_numbers(avgThptBytes,  buf4, sizeof(buf4), 1),
	  pfring_format_numbers(num_pkt_good_sent, buf5, sizeof(buf5), 1));

  memcpy(&lastTime, &now, sizeof(now));
  last_num_pkt_good_sent = num_pkt_good_sent, last_num_bytes_good_sent = num_bytes_good_sent;
}

/* ******************************** */

void my_sigalarm(int sig) {
  print_stats();
  alarm(1);
  signal(SIGALRM, my_sigalarm);
}

/* ******************************** */

void sigproc(int sig) {
  static int called = 0;

  fprintf(stdout, "Leaving...\n");
  if(called) return; else called = 1;
  do_shutdown = 1;
  print_stats();
  printf("Sent %llu packets\n", (long long unsigned int)num_pkt_good_sent);
  pfring_close(pd);

  exit(0);
}

/* *************************************** */

void printHelp(void) {
  printf("pfsend - (C) 2011 Deri Luca <deri@ntop.org>\n\n");

  printf("pfsend -i out_dev [-a] [-f <.pcap file>] [-g <core_id>] [-h]\n"
         "       [-l <length>] [-n <num>][-r <rate>] [-m <dst MAC>]\n"
	 "       [-w <TX watermark>] [-v]\n\n");

  printf("-a              Active send retry\n");
#if 0
  printf("-b <cpu %%>      CPU pergentage priority (0-99)\n");
#endif
  printf("-f <.pcap file> Send packets as read from a pcap file\n");
  printf("-g <core_id>    Bind this app to a core (only with -n 0)\n");
  printf("-h              Print this help\n");
  printf("-i <device>     Device name. Use device\n");
  printf("-l <length>     Packet length to send. Ignored with -f\n");
  printf("-n <num>        Num pkts to send (use 0 for infinite)\n");
  printf("-r <rate>       Rate to send (example -r 2.5 sends 2.5 Gbit/sec, -r -1 pcap capture rate)\n");
  printf("-m <dst MAC>    Reforge destination MAC (format AA:BB:CC:DD:EE:FF)\n");
  printf("-b <num>        Number of different IPs (balanced traffic)\n");
  printf("-w <watermark>  TX watermark (low value=low latency) [not effective on DNA]\n");
  printf("-z              Disable zero-copy, if supported [DNA only]\n");
  printf("-x <if index>   Send to the selected interface, if supported\n");
  printf("-v              Verbose\n");
  exit(0);
}

/* *************************************** */

/* Bind this thread to a specific core */

int bind2core(u_int core_id) {
  cpu_set_t cpuset;
  int s;

  CPU_ZERO(&cpuset);
  CPU_SET(core_id, &cpuset);
  if((s = pthread_setaffinity_np(pthread_self(), sizeof(cpu_set_t), &cpuset)) != 0) {
    printf("Error while binding to core %u: errno=%i\n", core_id, s);
    return(-1);
  } else {
    return(0);
  }
}

/* *************************************** */

static __inline__ ticks getticks(void)
{
  u_int32_t a, d;
  // asm("cpuid"); // serialization
  asm volatile("rdtsc" : "=a" (a), "=d" (d));
  return (((ticks)a) | (((ticks)d) << 32));
}

/* ******************************************* */

/*
 * Checksum routine for Internet Protocol family headers (C Version)
 *
 * Borrowed from DHCPd
 */

static u_int32_t in_cksum(unsigned char *buf,
			  unsigned nbytes, u_int32_t sum) {
  uint i;

  /* Checksum all the pairs of bytes first... */
  for (i = 0; i < (nbytes & ~1U); i += 2) {
    sum += (u_int16_t) ntohs(*((u_int16_t *)(buf + i)));
    /* Add carry. */
    if(sum > 0xFFFF)
      sum -= 0xFFFF;
  }

  /* If there's a single byte left over, checksum it, too.   Network
     byte order is big-endian, so the remaining byte is the high byte. */
  if(i < nbytes) {
#ifdef DEBUG_CHECKSUM_VERBOSE
    debug ("sum = %x", sum);
#endif
    sum += buf [i] << 8;
    /* Add carry. */
    if(sum > 0xFFFF)
      sum -= 0xFFFF;
  }

  return sum;
}

/* ******************************************* */

static u_int32_t wrapsum (u_int32_t sum) {
  sum = ~sum & 0xFFFF;
  return htons(sum);
}

/* ******************************************* */

static void forge_udp_packet(char *buffer, u_int idx) {
  int i;
  struct ip_header *ip_header;
  struct udp_header *udp_header;
  u_int32_t src_ip = (0x0A000000 + idx) % 0xFFFFFFFF /* from 10.0.0.0 */;
  u_int32_t dst_ip =  0xC0A80001 /* 192.168.0.1 */;
  u_int16_t src_port = 2012, dst_port = 3000;

  /* Reset packet */
  memset(buffer, 0, sizeof(buffer));

  for(i=0; i<12; i++) buffer[i] = i;
  buffer[12] = 0x08, buffer[13] = 0x00; /* IP */
  if(reforge_mac) memcpy(buffer, mac_address, 6);

  ip_header = (struct ip_header*) &buffer[sizeof(struct ether_header)];
  ip_header->ihl = 5;
  ip_header->version = 4;
  ip_header->tos = 0;
  ip_header->tot_len = htons(send_len-sizeof(struct ether_header));
  ip_header->id = htons(2012);
  ip_header->ttl = 64;
  ip_header->frag_off = htons(0);
  ip_header->protocol = IPPROTO_UDP;
  ip_header->daddr = htonl(dst_ip);
  ip_header->saddr = htonl(src_ip);
  ip_header->check = wrapsum(in_cksum((unsigned char *)ip_header,
				      sizeof(struct ip_header), 0));

  udp_header = (struct udp_header*)(buffer + sizeof(struct ether_header) + sizeof(struct ip_header));
  udp_header->source = htons(src_port);
  udp_header->dest = htons(dst_port);
  udp_header->len = htons(send_len-sizeof(struct ether_header)-sizeof(struct ip_header));
  udp_header->check = 0; /* It must be 0 to compute the checksum */

  /*
    http://www.cs.nyu.edu/courses/fall01/G22.2262-001/class11.htm
    http://www.ietf.org/rfc/rfc0761.txt
    http://www.ietf.org/rfc/rfc0768.txt
  */

  i = sizeof(struct ether_header) + sizeof(struct ip_header) + sizeof(struct udp_header);
  udp_header->check = wrapsum(in_cksum((unsigned char *)udp_header, sizeof(struct udp_header),
                                       in_cksum((unsigned char *)&buffer[i], send_len-i,
						in_cksum((unsigned char *)&ip_header->saddr,
							 2*sizeof(ip_header->saddr),
							 IPPROTO_UDP + ntohs(udp_header->len)))));
}

/* *************************************** */

int main(int argc, char* argv[]) {
  char c, *pcap_in = NULL;
  int i, verbose = 0, active_poll = 0, disable_zero_copy = 0;
  int use_zero_copy_tx = 0;
  u_int mac_a, mac_b, mac_c, mac_d, mac_e, mac_f;
  char buffer[9000];
  u_int32_t num_to_send = 0;
  int bind_core = -1;
  u_int16_t cpu_percentage = 0;
  double gbit_s = 0, td, pps;
  ticks tick_start = 0, tick_delta = 0;
  ticks hz = 0;
  struct packet *tosend;
  u_int num_tx_slots = 0;
  int num_balanced_pkts = 1, watermark = 0;
  u_int num_pcap_pkts = 0;

  while((c = getopt(argc,argv,"b:hi:n:g:l:af:r:vm:w:zx:"
#if 0
		    "b:"
#endif
		    )) != -1) {
    switch(c) {
    case 'b':
      num_balanced_pkts = atoi(optarg);
      break;
    case 'h':
      printHelp();
      break;
    case 'i':
      in_dev = strdup(optarg);
      break;
    case 'f':
      pcap_in = strdup(optarg);
      break;
    case 'n':
      num_to_send = atoi(optarg);
      break;
    case 'g':
      bind_core = atoi(optarg);
      break;
    case 'l':
      send_len = atoi(optarg);
      break;
    case 'x':
      if_index = atoi(optarg);
      break;
    case 'v':
      verbose = 1;
      break;
    case 'a':
      active_poll = 1;
      break;
    case 'r':
      sscanf(optarg, "%lf", &gbit_s);
      break;
#if 0
    case 'b':
      cpu_percentage = atoi(optarg);
#endif
      break;

    case 'm':
      if(sscanf(optarg, "%02X:%02X:%02X:%02X:%02X:%02X", &mac_a, &mac_b, &mac_c, &mac_d, &mac_e, &mac_f) != 6) {
	printf("Invalid MAC address format (XX:XX:XX:XX:XX:XX)\n");
	return(0);
      } else {
	reforge_mac = 1;
	mac_address[0] = mac_a, mac_address[1] = mac_b, mac_address[2] = mac_c;
	mac_address[3] = mac_d, mac_address[4] = mac_e, mac_address[5] = mac_f;
      }
      break;
    case 'w':
      watermark = atoi(optarg);

      if(watermark < 1) watermark = 1;
      break;
    case 'z':
      disable_zero_copy = 1;
      break;
    }
  }

  if((in_dev == NULL) || (num_balanced_pkts < 1))
    printHelp();

  printf("Sending packets on %s\n", in_dev);

  pd = pfring_open(in_dev, 1500, 0 /* PF_RING_PROMISC */);
  if(pd == NULL) {
    printf("pfring_open %s error [%s]\n", in_dev, strerror(errno));
    return(-1);
  } else {
    u_int32_t version;

    pfring_set_application_name(pd, "pfdnasend");
    pfring_version(pd, &version);

    printf("Using PF_RING v.%d.%d.%d\n", (version & 0xFFFF0000) >> 16,
	   (version & 0x0000FF00) >> 8, version & 0x000000FF);
  }

  if (!pd->send && pd->send_ifindex && if_index == -1) {
    printf("Please use -x <if index>\n");
    return -1;
  }

  if(watermark > 0) {
    int rc;

    if((rc = pfring_set_tx_watermark(pd, watermark)) < 0)
      printf("pfring_set_tx_watermark() failed [rc=%d]\n", rc);
  }

  signal(SIGINT, sigproc);
  signal(SIGTERM, sigproc);
  signal(SIGINT, sigproc);

  if(send_len < 60)
    send_len = 60;

  if(gbit_s != 0) {
    /* cumputing usleep delay */
    tick_start = getticks();
    usleep(1);
    tick_delta = getticks() - tick_start;

    /* cumputing CPU freq */
    tick_start = getticks();
    usleep(1001);
    hz = (getticks() - tick_start - tick_delta) * 1000 /*kHz -> Hz*/;
    printf("Estimated CPU freq: %lu Hz\n", (long unsigned int)hz);
  }

  if(pcap_in) {
    char ebuf[256];
    u_char *pkt;
    struct pcap_pkthdr *h;
    pcap_t *pt = pcap_open_offline(pcap_in, ebuf);
    struct timeval beginning = { 0, 0 };
    int avg_send_len = 0;

    if(pt) {
      struct packet *last = NULL;

      while(1) {
	struct packet *p;
	int rc = pcap_next_ex(pt, &h, (const u_char**)&pkt);

	if(rc <= 0) break;

	if (num_pcap_pkts == 0) {
	  beginning.tv_sec = h->ts.tv_sec;
	  beginning.tv_usec = h->ts.tv_usec;
	}

	p = (struct packet*)malloc(sizeof(struct packet));
	if(p) {
	  p->len = h->caplen;
	  p->ticks_from_beginning = (((h->ts.tv_sec - beginning.tv_sec) * 1000000) + (h->ts.tv_usec - beginning.tv_usec)) * hz / 1000000;
	  p->next = NULL;
	  p->pkt = (char*)malloc(p->len);

	  if(p->pkt == NULL) {
	    printf("Not enough memory\n");
	    break;
	  } else {
	    memcpy(p->pkt, pkt, p->len);
	    if(reforge_mac) memcpy(p->pkt, mac_address, 6);
	  }

	  if(last) {
	    last->next = p;
	    last = p;
	  } else
	    pkt_head = p, last = p;
	} else {
	  printf("Not enough memory\n");
	  break;
	}

	if(verbose)
	  printf("Read %d bytes packet from pcap file %s [%lu.%lu Secs =  %lu ticks@%luhz from beginning]\n",
		 p->len, pcap_in, h->ts.tv_sec - beginning.tv_sec, h->ts.tv_usec - beginning.tv_usec,
		 (long unsigned int)p->ticks_from_beginning,
		 (long unsigned int)hz);

	avg_send_len += p->len;
	num_pcap_pkts++;
      } /* while */
      avg_send_len /= num_pcap_pkts;

      pcap_close(pt);
      printf("Read %d packets from pcap file %s\n",
	     num_pcap_pkts, pcap_in);
      last->next = pkt_head; /* Loop */
      send_len = avg_send_len;
    } else {
      printf("Unable to open file %s\n", pcap_in);
      pfring_close(pd);
      return(-1);
    }
  } else {
    struct packet *p = NULL, *last = NULL;

    for (i = 0; i < num_balanced_pkts; i++) {

      forge_udp_packet(buffer, i);

      p = (struct packet *) malloc(sizeof(struct packet));
      if(p) {
	if (i == 0) pkt_head = p;

        p->len = send_len;
        p->ticks_from_beginning = 0;
        p->next = pkt_head;
        p->pkt = (char*)malloc(p->len);

	if (p->pkt == NULL) {
	  printf("Not enough memory\n");
	  break;
	}

        memcpy(p->pkt, buffer, send_len);

	if (last != NULL) last->next = p;
	last = p;
      } else { 
	/* oops, couldn't allocate memory */
	fprintf(stderr, "Unable to allocate memory requested (%s)\n", strerror(errno));
	return (-1);
      }      
    }
  }

  if(gbit_s > 0) {
    /* computing max rate */
    pps = ((gbit_s * 1000000000) / 8 /*byte*/) / (8 /*Preamble*/ + send_len + 4 /*CRC*/ + 12 /*IFG*/);

    td = (double)(hz / pps);
    tick_delta = (ticks)td;

    printf("Number of %d-byte Packet Per Second at %.2f Gbit/s: %.2f\n", (send_len + 4 /*CRC*/), gbit_s, pps);
  }

  if(bind_core >= 0)
    bind2core(bind_core);

  if(wait_for_packet && (cpu_percentage > 0)) {
    if(cpu_percentage > 99) cpu_percentage = 99;
    pfring_config(cpu_percentage);
  }

  if(!verbose) {
    signal(SIGALRM, my_sigalarm);
    alarm(1);
  }

  gettimeofday(&startTime, NULL);
  memcpy(&lastTime, &startTime, sizeof(startTime));

  pfring_set_socket_mode(pd, send_only_mode);

  if(pfring_enable_ring(pd) != 0) {
    printf("Unable to enable ring :-(\n");
    pfring_close(pd);
    return(-1);
  }

  use_zero_copy_tx = 0;

  if((!disable_zero_copy)
     && (pd->dna_get_num_tx_slots != NULL)
     && (pd->dna_get_next_free_tx_slot != NULL)
     && (pd->dna_copy_tx_packet_into_slot != NULL)) {
    tosend = pkt_head;

    num_tx_slots = pd->dna_get_num_tx_slots(pd);

    if(num_tx_slots > 0
       && (((num_to_send > 0) && (num_to_send <= num_tx_slots))
        || ( pcap_in && (num_pcap_pkts     <= num_tx_slots) && (num_tx_slots % num_pcap_pkts     == 0))
        || (!pcap_in && (num_balanced_pkts <= num_tx_slots) && (num_tx_slots % num_balanced_pkts == 0)))) {
      int ret;
      u_int first_free_slot = pd->dna_get_next_free_tx_slot(pd);

      for(i=0; i<num_tx_slots; i++) {
	ret = pfring_copy_tx_packet_into_slot(pd, (first_free_slot+i)%num_tx_slots, tosend->pkt, tosend->len);
	if(ret < 0)
	  break;

	tosend = tosend->next;
      }

      use_zero_copy_tx = 1;
      printf("Using zero-copy TX\n");
    } else {
      printf("NOT using zero-copy: TX ring size (%u) is not a multiple of the number of unique packets to send (%u)\n", num_tx_slots, pcap_in ? num_pcap_pkts : num_balanced_pkts);
    }
  } else {
    if (!disable_zero_copy)
      printf("NOT using zero-copy: not supported by the driver\n");
  }

  tosend = pkt_head;
  i = 0;

  if(gbit_s != 0)
    tick_start = getticks();

  while((num_to_send == 0) 
	|| (i < num_to_send)) {
    int rc;

  redo:

    if (if_index != -1)
      rc = pfring_send_ifindex(pd, tosend->pkt, tosend->len, gbit_s < 0 ? 1 : 0 /* Don't flush (it does PF_RING automatically) */, if_index);
    else if(use_zero_copy_tx)
      /* We pre-filled the TX slots */
      rc = pfring_send(pd, NULL, tosend->len, gbit_s < 0 ? 1 : 0 /* Don't flush (it does PF_RING automatically) */);
    else
      rc = pfring_send(pd, tosend->pkt, tosend->len, gbit_s < 0 ? 1 : 0 /* Don't flush (it does PF_RING automatically) */);

    if(verbose)
      printf("[%d] pfring_send(%d) returned %d\n", i, tosend->len, rc);

    if(rc == PF_RING_ERROR_INVALID_ARGUMENT) {
      printf("Attempting to send invalid packet [len: %u][MTU: %u]%s\n",
	     tosend->len, pd->mtu_len,
      	     if_index != -1 ? " or using a wrong interface id" : "");
    } else if(rc < 0) {
      /* Not enough space in buffer */
      if(!active_poll) {
#if 1
	usleep(1);
#else
        if(bind_core >= 0)
	  usleep(1);
	else
	  pfring_poll(pd, 0); //sched_yield();
#endif
      }
      goto redo;
    }

    num_pkt_good_sent++;
    num_bytes_good_sent += tosend->len + 24 /* 8 Preamble + 4 CRC + 12 IFG */;

    tosend = tosend->next;

    if (use_zero_copy_tx
	&& (num_pkt_good_sent == num_tx_slots))
      tosend = pkt_head;

    if(gbit_s > 0) {
      /* rate set */
      while((getticks() - tick_start) < (num_pkt_good_sent * tick_delta)) ;
    } else if (gbit_s < 0) {
      /* real pcap rate */
      if (tosend->ticks_from_beginning == 0)
        tick_start = getticks(); /* first packet, resetting time */
      while((getticks() - tick_start) < tosend->ticks_from_beginning) ;
    }

    if(num_to_send > 0) i++;
  } /* for */

  print_stats(0);
  pfring_close(pd);

  return(0);
}

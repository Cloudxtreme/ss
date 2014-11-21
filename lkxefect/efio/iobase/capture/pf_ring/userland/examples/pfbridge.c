/*
 *
 * (C) 2012 - Luca Deri <deri@ntop.org>
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
#include <sys/time.h>
#include <time.h>
#include <sys/socket.h>

#include "pfring.h"

u_int32_t num_sent = 0;

/* ****************************************************** */

void printHelp(void) {
  printf("pfbridge - Forwards traffic from -a -> -b device using vanilla PF_RING (no DNA)\n\n");
  printf("-h              [Print help]\n");
  printf("-v              [Verbose]\n");
  printf("-p              [Use pfring_send() instead of bridge]\n");
  printf("-a <device>     [First device name]\n");
  printf("-b <device>     [Second device name]\n");
  printf("-g <core_id>    Bind this app to a core\n");
}

/* *************************************** */

/* Bind this thread to a specific core */

int bind2core(u_int core_id) {
  cpu_set_t cpuset;
  int s;

  CPU_ZERO(&cpuset);
  CPU_SET(core_id, &cpuset);
  if((s = pthread_setaffinity_np(pthread_self(), sizeof(cpu_set_t), &cpuset)) != 0) {
    fprintf(stderr, "Error while binding to core %u: errno=%i\n", core_id, s);
    return(-1);
  } else {
    return(0);
  }
}

/* ******************************** */

void my_sigalarm(int sig) {
  char buf[32];

  pfring_format_numbers((double)num_sent, buf, sizeof(buf), 0),
  printf("%s pps\n", buf);
  num_sent = 0;
  alarm(1);
  signal(SIGALRM, my_sigalarm);
}

/* ****************************************************** */

int main(int argc, char* argv[]) {
  pfring *a_ring, *b_ring;
  char *a_dev = NULL, *b_dev = NULL, c;
  u_int8_t verbose = 0, use_pfring_send = 0;
  int a_ifindex, b_ifindex;
  int bind_core = -1;

  while((c = getopt(argc,argv, "ha:b:c:fvpg:")) != -1) {
    switch(c) {
      case 'h':
	printHelp();
	return 0;
	break;
      case 'a':
	a_dev = strdup(optarg);
	break;
      case 'b':
	b_dev = strdup(optarg);
	break;
      case 'p':
	use_pfring_send = 1;
	break;
      case 'v':
	verbose = 1;
	break;
      case 'g':
        bind_core = atoi(optarg);
        break;
    }
  }  

  if ((!a_dev) || (!b_dev)) {
    printf("You must specify two devices!\n");
    return -1;
  }

  if(strcmp(a_dev, b_dev) == 0) {
    printf("Bridge devices must be different!\n");
    return -1;
  }

  /* open devices */
  if((a_ring = pfring_open(a_dev, 1500, PF_RING_PROMISC|PF_RING_LONG_HEADER)) == NULL) {
    printf("pfring_open error for %s [%s]\n", a_dev, strerror(errno));
    return(-1);
  } else {
    pfring_set_application_name(a_ring, "pfbridge-a");
    pfring_set_direction(a_ring, rx_only_direction);
    pfring_set_socket_mode(a_ring, recv_only_mode);
    pfring_get_bound_device_ifindex(a_ring, &a_ifindex);
  }

  if((b_ring = pfring_open(b_dev, 1500, PF_RING_PROMISC|PF_RING_LONG_HEADER)) == NULL) {
    printf("pfring_open error for %s [%s]\n", b_dev, strerror(errno));
    pfring_close(a_ring);
    return(-1);
  } else {
    pfring_set_application_name(b_ring, "pfbridge-b");
    pfring_set_socket_mode(b_ring, send_only_mode);
    pfring_get_bound_device_ifindex(b_ring, &b_ifindex);
  }
  
  /* Enable rings */
  if (pfring_enable_ring(a_ring) != 0) {
    printf("Unable enabling ring 'a' :-(\n");
    pfring_close(a_ring);
    pfring_close(b_ring);
    return(-1);
  }

  if(use_pfring_send) {
    if (pfring_enable_ring(b_ring)) {
      printf("Unable enabling ring 'b' :-(\n");
      pfring_close(a_ring);
      pfring_close(b_ring);
      return(-1);
    }
  } else {
    pfring_close(b_ring);
  }

  signal(SIGALRM, my_sigalarm);
  alarm(1);

  if(bind_core >= 0)
    bind2core(bind_core);

  while(1) {
    u_char *buffer;
    struct pfring_pkthdr hdr;
    
    if(pfring_recv(a_ring, &buffer, 0, &hdr, 1) > 0) {
      int rc;
      
      if(use_pfring_send) {
	rc = pfring_send(b_ring, (char*)buffer, hdr.caplen, 1);

	if(rc < 0)
	  printf("pfring_send() error %d\n", rc);
	else if(verbose)
	  printf("Forwarded %d bytes packet\n", hdr.len);	
      } else {
	rc = pfring_send_last_rx_packet(a_ring, b_ifindex);
	
	if(rc < 0)
	  printf("pfring_send_last_rx_packet() error %d\n", rc);
	else if(verbose)
	  printf("Forwarded %d bytes packet\n", hdr.len);
      }

      if(rc >= 0) num_sent++;
	
    }
  }

  pfring_close(a_ring);
  if(use_pfring_send) pfring_close(b_ring);
  
  return(0);
}

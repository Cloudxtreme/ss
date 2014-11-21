/*
 *
 * (C) 2005-12 - Luca Deri <deri@ntop.org>
 *               Alfredo Cardigliano <cardigliano@ntop.org>
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lessed General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 *
 * This code includes contributions courtesy of
 * - Fedor Sakharov <fedor.sakharov@gmail.com>
 *
 */


#include "pfring.h"
#include "pfring_utils.h"

#include <linux/if.h>

#ifdef ENABLE_HW_TIMESTAMP
#include <linux/net_tstamp.h>
#endif

/* ******************************* */

int pfring_enable_hw_timestamp(pfring* ring, char *device_name, u_int8_t enable_rx, u_int8_t enable_tx) {
#ifdef ENABLE_HW_TIMESTAMP
  struct hwtstamp_config hwconfig;
  struct ifreq ifr;
  int rc, sock_fd;

  sock_fd = socket(PF_PACKET, SOCK_RAW, htons(ETH_P_ALL));
  if(sock_fd <= 0) return(-1);

  memset(&hwconfig, 0, sizeof(hwconfig));

  hwconfig.rx_filter = (enable_rx ? HWTSTAMP_FILTER_ALL : HWTSTAMP_FILTER_NONE);
  hwconfig.tx_type   = (enable_tx ? HWTSTAMP_TX_ON      : HWTSTAMP_TX_OFF);

  memset(&ifr, 0, sizeof(ifr));
  strncpy(ifr.ifr_name, device_name, sizeof(ifr.ifr_name)-1);
  ifr.ifr_data = (void *)&hwconfig;

  rc = ioctl(sock_fd, SIOCSHWTSTAMP, &ifr);
  if(rc < 0)
    rc = errno;
  else
    rc = 0;

#ifdef RING_DEBUG
  printf("pfring_enable_hw_timestamp(%s) returned %d\n",
	 device_name, rc);
#endif

  close(sock_fd);
  return(rc);
#else
  return(-1);
#endif
}

/* ******************************* */

static u_int32_t pfring_hash_pkt(struct pfring_pkthdr *hdr) {
  if (hdr->extended_hdr.parsed_pkt.tunnel.tunnel_id == NO_TUNNEL_ID) {
    return
      hdr->extended_hdr.parsed_pkt.vlan_id +
      hdr->extended_hdr.parsed_pkt.l3_proto +
      hdr->extended_hdr.parsed_pkt.ip_src.v6.s6_addr32[0] +
      hdr->extended_hdr.parsed_pkt.ip_src.v6.s6_addr32[1] +
      hdr->extended_hdr.parsed_pkt.ip_src.v6.s6_addr32[2] +
      hdr->extended_hdr.parsed_pkt.ip_src.v6.s6_addr32[3] +
      hdr->extended_hdr.parsed_pkt.ip_dst.v6.s6_addr32[0] +
      hdr->extended_hdr.parsed_pkt.ip_dst.v6.s6_addr32[1] +
      hdr->extended_hdr.parsed_pkt.ip_dst.v6.s6_addr32[2] +
      hdr->extended_hdr.parsed_pkt.ip_dst.v6.s6_addr32[3] +
      hdr->extended_hdr.parsed_pkt.l4_src_port + 
      hdr->extended_hdr.parsed_pkt.l4_dst_port;
  } else {
    return
      hdr->extended_hdr.parsed_pkt.vlan_id +
      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto +
      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_src.v6.s6_addr32[1] +
      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_src.v6.s6_addr32[2] +
      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_src.v6.s6_addr32[3] +
      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_dst.v6.s6_addr32[0] +
      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_dst.v6.s6_addr32[1] +
      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_dst.v6.s6_addr32[2] +
      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_dst.v6.s6_addr32[3] +
      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_l4_src_port +
      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_l4_dst_port;
  }  
}

/* ******************************* */

static int __pfring_parse_tunneled_pkt(u_char *pkt, struct pfring_pkthdr *hdr, u_int16_t ip_version, u_int16_t tunnel_offset) {
  u_int32_t ip_len = 0;
  u_int16_t fragment_offset = 0;

  if(ip_version == 4 /* IPv4 */ ) {
    struct iphdr *tunneled_ip;

    if(hdr->caplen < (tunnel_offset+sizeof(struct iphdr)))
      return 0;

    tunneled_ip = (struct iphdr *) (&pkt[tunnel_offset]);

    hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto = tunneled_ip->protocol;
    hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_src.v4 = ntohl(tunneled_ip->saddr);
    hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_dst.v4 = ntohl(tunneled_ip->daddr);

    fragment_offset = tunneled_ip->frag_off & htons(IP_OFFSET); /* fragment, but not the first */
    ip_len = tunneled_ip->ihl*4;
    tunnel_offset += ip_len;

  } else if(ip_version == 6 /* IPv6 */ ) {
    struct ipv6hdr *tunneled_ipv6;

    if(hdr->caplen < (tunnel_offset+sizeof(struct ipv6hdr))) 
      return 0;

    tunneled_ipv6 = (struct ipv6hdr *) (&pkt[tunnel_offset]);

    hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto = tunneled_ipv6->nexthdr;
    
    /* Values of IPv6 addresses are stored as network byte order */
    memcpy(&hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_src.v6, &tunneled_ipv6->saddr, sizeof(tunneled_ipv6->saddr));
    memcpy(&hdr->extended_hdr.parsed_pkt.tunnel.tunneled_ip_dst.v6, &tunneled_ipv6->daddr, sizeof(tunneled_ipv6->daddr));

    ip_len = sizeof(struct ipv6hdr);

    while(hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto == NEXTHDR_HOP
	  || hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto == NEXTHDR_DEST
	  || hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto == NEXTHDR_ROUTING
	  || hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto == NEXTHDR_AUTH
	  || hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto == NEXTHDR_ESP
	  || hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto == NEXTHDR_FRAGMENT) {
      struct ipv6_opt_hdr *ipv6_opt;

      if(hdr->caplen < (tunnel_offset+ip_len+sizeof(struct ipv6_opt_hdr))) return 1;

      ipv6_opt = (struct ipv6_opt_hdr *)(&pkt[tunnel_offset+ip_len]);
      ip_len += sizeof(struct ipv6_opt_hdr), fragment_offset = 0;
      if(hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto == NEXTHDR_AUTH)
	/*
	  RFC4302 2.2. Payload Length: This 8-bit field specifies the
	  length of AH in 32-bit words (4-byte units), minus "2".
	*/
	ip_len += ipv6_opt->hdrlen * 4;
      else if(hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto != NEXTHDR_FRAGMENT)
        ip_len += ipv6_opt->hdrlen;

      hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto = ipv6_opt->nexthdr;
    } /* while */

    tunnel_offset += ip_len;

  } else
    return 0;

  if (fragment_offset)
    return 1;

  if(hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto == IPPROTO_TCP) {
    struct tcphdr *tcp;

    if(hdr->caplen < tunnel_offset + sizeof(struct tcphdr))
      return 1;

    tcp = (struct tcphdr *)(&pkt[tunnel_offset]);

    hdr->extended_hdr.parsed_pkt.tunnel.tunneled_l4_src_port = ntohs(tcp->source), 
    hdr->extended_hdr.parsed_pkt.tunnel.tunneled_l4_dst_port = ntohs(tcp->dest);
  } else if(hdr->extended_hdr.parsed_pkt.tunnel.tunneled_proto == IPPROTO_UDP) {
    struct udphdr *udp;

    if(hdr->caplen < tunnel_offset + sizeof(struct udphdr)) 
      return 1;
    
    udp = (struct udphdr *)(&pkt[tunnel_offset]);

    hdr->extended_hdr.parsed_pkt.tunnel.tunneled_l4_src_port = ntohs(udp->source), 
    hdr->extended_hdr.parsed_pkt.tunnel.tunneled_l4_dst_port = ntohs(udp->dest);
  }

  return 2;
}

/* ******************************* */

int pfring_parse_pkt(u_char *pkt, struct pfring_pkthdr *hdr, u_int8_t level /* L2..L4, 5 (tunnel) */, 
		     u_int8_t add_timestamp /* 0,1 */, u_int8_t add_hash /* 0,1 */) {
  struct ethhdr *eh = (struct ethhdr*) pkt;
  u_int32_t displ = 0, ip_len;
  u_int16_t analyzed = 0, fragment_offset = 0;

  hdr->extended_hdr.parsed_pkt.tunnel.tunnel_id = NO_TUNNEL_ID;

  /* Note: in order to optimize the computation, this function expects a zero-ed 
   * or partially parsed pkthdr */
  //memset(&hdr->extended_hdr.parsed_pkt, 0, sizeof(struct pkt_parsing_info));
  //hdr->extended_hdr.parsed_header_len = 0;

  if (hdr->extended_hdr.parsed_pkt.offset.l3_offset != 0)
    goto L3;

  memcpy(&hdr->extended_hdr.parsed_pkt.dmac, eh->h_dest,   sizeof(eh->h_dest));
  memcpy(&hdr->extended_hdr.parsed_pkt.smac, eh->h_source, sizeof(eh->h_source));

  hdr->extended_hdr.parsed_pkt.eth_type = ntohs(eh->h_proto);
  hdr->extended_hdr.parsed_pkt.offset.eth_offset = 0;
  hdr->extended_hdr.parsed_pkt.offset.vlan_offset = 0;
  hdr->extended_hdr.parsed_pkt.vlan_id = 0; /* Any VLAN */

  if (hdr->extended_hdr.parsed_pkt.eth_type == 0x8100 /* 802.1q (VLAN) */) {
    struct eth_vlan_hdr *vh;
    hdr->extended_hdr.parsed_pkt.offset.vlan_offset = sizeof(struct ethhdr) - sizeof(struct eth_vlan_hdr);
    while (hdr->extended_hdr.parsed_pkt.eth_type == 0x8100 /* 802.1q (VLAN) */ ) {
      hdr->extended_hdr.parsed_pkt.offset.vlan_offset += sizeof(struct eth_vlan_hdr);
      vh = (struct eth_vlan_hdr *) &pkt[hdr->extended_hdr.parsed_pkt.offset.vlan_offset];
      hdr->extended_hdr.parsed_pkt.vlan_id = ntohs(vh->h_vlan_id) & 0x0fff;
      hdr->extended_hdr.parsed_pkt.eth_type = ntohs(vh->h_proto);
      displ += sizeof(struct eth_vlan_hdr);
    }
  }

  hdr->extended_hdr.parsed_pkt.offset.l3_offset = hdr->extended_hdr.parsed_pkt.offset.eth_offset + displ + sizeof(struct ethhdr);

 L3:

  analyzed = 2;

  if (level < 3)
    goto TIMESTAMP;

  if (hdr->extended_hdr.parsed_pkt.offset.l4_offset != 0)
    goto L4;

  if (hdr->extended_hdr.parsed_pkt.eth_type == 0x0800 /* IPv4 */) {
    struct iphdr *ip;

    hdr->extended_hdr.parsed_pkt.ip_version = 4;

    if (hdr->caplen < hdr->extended_hdr.parsed_pkt.offset.l3_offset + sizeof(struct iphdr))
      goto TIMESTAMP;

    ip = (struct iphdr *)(&pkt[hdr->extended_hdr.parsed_pkt.offset.l3_offset]);

    hdr->extended_hdr.parsed_pkt.ipv4_src = ntohl(ip->saddr);
    hdr->extended_hdr.parsed_pkt.ipv4_dst = ntohl(ip->daddr);
    hdr->extended_hdr.parsed_pkt.l3_proto = ip->protocol;
    hdr->extended_hdr.parsed_pkt.ipv4_tos = ip->tos;
    fragment_offset = ip->frag_off & htons(IP_OFFSET); /* fragment, but not the first */
    ip_len  = ip->ihl*4;

  } else if(hdr->extended_hdr.parsed_pkt.eth_type == 0x86DD /* IPv6 */) {
    struct ipv6hdr *ipv6;

    hdr->extended_hdr.parsed_pkt.ip_version = 6;

    if(hdr->caplen < hdr->extended_hdr.parsed_pkt.offset.l3_offset + sizeof(struct ipv6hdr)) 
      goto TIMESTAMP;

    ipv6 = (struct ipv6hdr*)(&pkt[hdr->extended_hdr.parsed_pkt.offset.l3_offset]);
    ip_len = sizeof(struct ipv6hdr);

    /* Values of IPv6 addresses are stored as network byte order */
    memcpy(&hdr->extended_hdr.parsed_pkt.ipv6_src, &ipv6->saddr, sizeof(ipv6->saddr));
    memcpy(&hdr->extended_hdr.parsed_pkt.ipv6_dst, &ipv6->daddr, sizeof(ipv6->daddr));

    hdr->extended_hdr.parsed_pkt.l3_proto = ipv6->nexthdr;
    hdr->extended_hdr.parsed_pkt.ipv6_tos = ipv6->priority; /* IPv6 class of service */

    while(hdr->extended_hdr.parsed_pkt.l3_proto == NEXTHDR_HOP	   ||
	  hdr->extended_hdr.parsed_pkt.l3_proto == NEXTHDR_DEST	   ||
	  hdr->extended_hdr.parsed_pkt.l3_proto == NEXTHDR_ROUTING ||
	  hdr->extended_hdr.parsed_pkt.l3_proto == NEXTHDR_AUTH	   ||
	  hdr->extended_hdr.parsed_pkt.l3_proto == NEXTHDR_ESP	   ||
	  hdr->extended_hdr.parsed_pkt.l3_proto == NEXTHDR_FRAGMENT) {
      struct ipv6_opt_hdr *ipv6_opt;

      if(hdr->caplen < hdr->extended_hdr.parsed_pkt.offset.l3_offset + ip_len + sizeof(struct ipv6_opt_hdr))
        goto TIMESTAMP;

      ipv6_opt = (struct ipv6_opt_hdr *)(&pkt[hdr->extended_hdr.parsed_pkt.offset.l3_offset + ip_len]);
      ip_len += sizeof(struct ipv6_opt_hdr);
      if(hdr->extended_hdr.parsed_pkt.l3_proto == NEXTHDR_AUTH)
	/*
	  RFC4302 2.2. Payload Length: This 8-bit field specifies the
	  length of AH in 32-bit words (4-byte units), minus "2".
	*/
        ip_len += ipv6_opt->hdrlen * 4;
      else if(hdr->extended_hdr.parsed_pkt.l3_proto != NEXTHDR_FRAGMENT)
        ip_len += ipv6_opt->hdrlen;

      hdr->extended_hdr.parsed_pkt.l3_proto = ipv6_opt->nexthdr;
    }
  } else {
    hdr->extended_hdr.parsed_pkt.l3_proto = 0;
    goto TIMESTAMP;
  }

  hdr->extended_hdr.parsed_pkt.offset.l4_offset = hdr->extended_hdr.parsed_pkt.offset.l3_offset + ip_len;

 L4:

  analyzed = 3;

  if (level < 4 || fragment_offset)
    goto TIMESTAMP;

  if(hdr->extended_hdr.parsed_pkt.l3_proto == IPPROTO_TCP) {
    struct tcphdr *tcp;

    if(hdr->caplen < hdr->extended_hdr.parsed_pkt.offset.l4_offset + sizeof(struct tcphdr)) 
      goto TIMESTAMP;

    tcp = (struct tcphdr *)(&pkt[hdr->extended_hdr.parsed_pkt.offset.l4_offset]);

    hdr->extended_hdr.parsed_pkt.l4_src_port = ntohs(tcp->source);
    hdr->extended_hdr.parsed_pkt.l4_dst_port = ntohs(tcp->dest);
    hdr->extended_hdr.parsed_pkt.offset.payload_offset = hdr->extended_hdr.parsed_pkt.offset.l4_offset + (tcp->doff * 4);
    hdr->extended_hdr.parsed_pkt.tcp.seq_num = ntohl(tcp->seq);
    hdr->extended_hdr.parsed_pkt.tcp.ack_num = ntohl(tcp->ack_seq);
    hdr->extended_hdr.parsed_pkt.tcp.flags = (tcp->fin * TH_FIN_MULTIPLIER) + (tcp->syn * TH_SYN_MULTIPLIER) +
      (tcp->rst * TH_RST_MULTIPLIER) + (tcp->psh * TH_PUSH_MULTIPLIER) +
      (tcp->ack * TH_ACK_MULTIPLIER) + (tcp->urg * TH_URG_MULTIPLIER);

    analyzed = 4;
  } else if(hdr->extended_hdr.parsed_pkt.l3_proto == IPPROTO_UDP) {
    struct udphdr *udp;

    if(hdr->caplen < hdr->extended_hdr.parsed_pkt.offset.l4_offset + sizeof(struct udphdr)) 
      goto TIMESTAMP;

    udp = (struct udphdr *)(&pkt[hdr->extended_hdr.parsed_pkt.offset.l4_offset]);

    hdr->extended_hdr.parsed_pkt.l4_src_port = ntohs(udp->source), hdr->extended_hdr.parsed_pkt.l4_dst_port = ntohs(udp->dest);
    hdr->extended_hdr.parsed_pkt.offset.payload_offset = hdr->extended_hdr.parsed_pkt.offset.l4_offset + sizeof(struct udphdr);
    
    analyzed = 4;

    if (level < 5)
      goto TIMESTAMP;

    /* GTPv1 */
    if((hdr->extended_hdr.parsed_pkt.l4_src_port == GTP_SIGNALING_PORT) || 
       (hdr->extended_hdr.parsed_pkt.l4_dst_port == GTP_SIGNALING_PORT) || 
       (hdr->extended_hdr.parsed_pkt.l4_src_port == GTP_U_DATA_PORT)    || 
       (hdr->extended_hdr.parsed_pkt.l4_dst_port == GTP_U_DATA_PORT))  {
      struct gtp_v1_hdr *gtp;
      u_int16_t gtp_len;

      if(hdr->caplen < (hdr->extended_hdr.parsed_pkt.offset.payload_offset+sizeof(struct gtp_v1_hdr)))
        goto TIMESTAMP;

      gtp = (struct gtp_v1_hdr *) (&pkt[hdr->extended_hdr.parsed_pkt.offset.payload_offset]);
      gtp_len = sizeof(struct gtp_v1_hdr);

      if(((gtp->flags & GTP_FLAGS_VERSION) >> GTP_FLAGS_VERSION_SHIFT) == GTP_VERSION_1) {
        struct iphdr *tunneled_ip;

	hdr->extended_hdr.parsed_pkt.tunnel.tunnel_id = ntohl(gtp->teid);

	if((hdr->extended_hdr.parsed_pkt.l4_src_port == GTP_U_DATA_PORT) || 
	   (hdr->extended_hdr.parsed_pkt.l4_dst_port == GTP_U_DATA_PORT)) {
	  if(gtp->flags & (GTP_FLAGS_EXTENSION | GTP_FLAGS_SEQ_NUM | GTP_FLAGS_NPDU_NUM)) {
	    struct gtp_v1_opt_hdr *gtpopt;

            if(hdr->caplen < (hdr->extended_hdr.parsed_pkt.offset.payload_offset+gtp_len+sizeof(struct gtp_v1_opt_hdr)))
	      goto TIMESTAMP;
	      
	    gtpopt = (struct gtp_v1_opt_hdr *) (&pkt[hdr->extended_hdr.parsed_pkt.offset.payload_offset + gtp_len]);
	    gtp_len += sizeof(struct gtp_v1_opt_hdr);

	    if((gtp->flags & GTP_FLAGS_EXTENSION) && gtpopt->next_ext_hdr) {
	      struct gtp_v1_ext_hdr *gtpext;
	      u_int8_t *next_ext_hdr;

	      do {
		if(hdr->caplen < (hdr->extended_hdr.parsed_pkt.offset.payload_offset+gtp_len +1/* 8bit len field */)) goto TIMESTAMP;
		gtpext = (struct gtp_v1_ext_hdr *) (&pkt[hdr->extended_hdr.parsed_pkt.offset.payload_offset + gtp_len]);
		gtp_len += (gtpext->len * GTP_EXT_HDR_LEN_UNIT_BYTES);
		if(gtpext->len == 0 || hdr->caplen < (hdr->extended_hdr.parsed_pkt.offset.payload_offset+gtp_len)) goto TIMESTAMP;
		next_ext_hdr = (u_int8_t *) (&pkt[hdr->extended_hdr.parsed_pkt.offset.payload_offset + gtp_len - 1/* 8bit next_ext_hdr field*/]);
	      } while (*next_ext_hdr);
	    }
	  }

	  if(hdr->caplen < (hdr->extended_hdr.parsed_pkt.offset.payload_offset + gtp_len + sizeof(struct iphdr)))
	    goto TIMESTAMP;

	  tunneled_ip = (struct iphdr *) (&pkt[hdr->extended_hdr.parsed_pkt.offset.payload_offset + gtp_len]);

          analyzed += __pfring_parse_tunneled_pkt(pkt, hdr, tunneled_ip->version, hdr->extended_hdr.parsed_pkt.offset.payload_offset + gtp_len);
        }
      }
    }
  } else if(hdr->extended_hdr.parsed_pkt.l3_proto == IPPROTO_GRE /* 0x47 */) {
    struct gre_header *gre = (struct gre_header*)(&pkt[hdr->extended_hdr.parsed_pkt.offset.l4_offset]);
    int gre_offset;

    gre->flags_and_version = ntohs(gre->flags_and_version);
    gre->proto = ntohs(gre->proto);

    gre_offset = sizeof(struct gre_header);

    if((gre->flags_and_version & GRE_HEADER_VERSION) == 0) { 
      if(gre->flags_and_version & (GRE_HEADER_CHECKSUM | GRE_HEADER_ROUTING)) gre_offset += 4;
      if(gre->flags_and_version & GRE_HEADER_KEY) {
        u_int32_t *tunnel_id = (u_int32_t*)(&pkt[hdr->extended_hdr.parsed_pkt.offset.l4_offset+gre_offset]); 
        gre_offset += 4;
	hdr->extended_hdr.parsed_pkt.tunnel.tunnel_id = ntohl(*tunnel_id);
      }
      if(gre->flags_and_version & GRE_HEADER_SEQ_NUM)  gre_offset += 4;

      hdr->extended_hdr.parsed_pkt.offset.payload_offset = hdr->extended_hdr.parsed_pkt.offset.l4_offset + gre_offset;

      analyzed = 4;

      if (level < 5)
        goto TIMESTAMP;

      if (gre->proto == ETH_P_IP /* IPv4 */ || gre->proto == ETH_P_IPV6 /* IPv6 */)
        analyzed += __pfring_parse_tunneled_pkt(pkt, hdr, gre->proto == ETH_P_IP ? 4 : 6, hdr->extended_hdr.parsed_pkt.offset.payload_offset);

    } else { /* TODO handle other GRE versions */
      hdr->extended_hdr.parsed_pkt.offset.payload_offset = hdr->extended_hdr.parsed_pkt.offset.l4_offset;
    }
  } else {
    hdr->extended_hdr.parsed_pkt.offset.payload_offset = hdr->extended_hdr.parsed_pkt.offset.l4_offset;
    hdr->extended_hdr.parsed_pkt.l4_src_port = hdr->extended_hdr.parsed_pkt.l4_dst_port = 0;
  }

TIMESTAMP:

  if(add_timestamp && hdr->ts.tv_sec == 0)
    gettimeofday(&hdr->ts, NULL); /* TODO What about using clock_gettime(CLOCK_REALTIME, ts) ? */

  if (add_hash && hdr->extended_hdr.pkt_hash == 0)
    hdr->extended_hdr.pkt_hash = pfring_hash_pkt(hdr);

  return analyzed;
}

/* ******************************* */

static int pfring_promisc(const char *device, int set_promisc) {
  int sock_fd, ret = 0;
  struct ifreq ifr;

  if(device == NULL) return(-3);

  sock_fd = socket(PF_PACKET, SOCK_RAW, htons(ETH_P_ALL));
  if(sock_fd <= 0) return(-1);

  memset(&ifr, 0, sizeof(ifr));
  strncpy(ifr.ifr_name, device, sizeof(ifr.ifr_name));
  if(ioctl(sock_fd, SIOCGIFFLAGS, &ifr) == -1) {
    close(sock_fd);
    return(-2);
  }

  ret = ifr.ifr_flags & IFF_PROMISC;
  if(set_promisc) {
    if(ret == 0) ifr.ifr_flags |= IFF_PROMISC;
  } else {
    /* Remove promisc */
    if(ret != 0) ifr.ifr_flags &= ~IFF_PROMISC;
  }

  if(ioctl(sock_fd, SIOCSIFFLAGS, &ifr) == -1)
    return(-1);

  close(sock_fd);
  return(ret);
}

/* ******************************* */

int pfring_set_if_promisc(const char *device, int set_promisc) {
  char name_copy[256], *elem;
  int ret = 0;
  
  snprintf(name_copy, sizeof(name_copy), "%s", device);
  elem = strtok(name_copy, ";,");

  while(elem != NULL) {
    char *at = strchr(elem, '@');

    if(at != NULL) at[0] = '\0';

    ret = pfring_promisc(elem, set_promisc);
    
    if(ret < 0) return(ret);

    elem = strtok(NULL, ";,");
  }

  return(ret);
}

/* *************************************** */

char* pfring_format_numbers(double val, char *buf, u_int buf_len, u_int8_t add_decimals) {
  u_int a1 = ((u_long)val / 1000000000) % 1000;
  u_int a = ((u_long)val / 1000000) % 1000;
  u_int b = ((u_long)val / 1000) % 1000;
  u_int c = (u_long)val % 1000;
  u_int d = (u_int)((val - (u_long)val)*100) % 100;  

  if(add_decimals) {
    if(val >= 1000000000) {
      snprintf(buf, buf_len, "%u'%03u'%03u'%03u.%02d", a1, a, b, c, d);
    } else if(val >= 1000000) {
      snprintf(buf, buf_len, "%u'%03u'%03u.%02d", a, b, c, d);
    } else if(val >= 100000) {
      snprintf(buf, buf_len, "%u'%03u.%02d", b, c, d);
    } else if(val >= 1000) {
      snprintf(buf, buf_len, "%u'%03u.%02d", b, c, d);
    } else
      snprintf(buf, buf_len, "%.2f", val);
  } else {
    if(val >= 1000000000) {
      snprintf(buf, buf_len, "%u'%03u'%03u'%03u", a1, a, b, c);
    } else if(val >= 1000000) {
      snprintf(buf, buf_len, "%u'%03u'%03u", a, b, c);
    } else if(val >= 100000) {
      snprintf(buf, buf_len, "%u'%03u", b, c);
    } else if(val >= 1000) {
      snprintf(buf, buf_len, "%u'%03u", b, c);
    } else
      snprintf(buf, buf_len, "%u", (unsigned int)val);
  }

  return(buf);
}

/* *************************************** */

int pfring_get_mtu_size(pfring* ring) {
  struct ifreq ifr;

  memset(&ifr, 0, sizeof(ifr));
  strncpy(ifr.ifr_name, ring->device_name, sizeof(ifr.ifr_name));

  if(ioctl(ring->fd, SIOCGIFMTU, &ifr) == -1) {
    return(0); /* Unknown for this device */
  }

  return(ifr.ifr_mtu);
}

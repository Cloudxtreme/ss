#include <efnet.h>
#include <stdio.h>

static inline unsigned short from32to16(unsigned long x)
{
	/* add up 16-bit and 16-bit for 16+c bit */
	x = (x & 0xffff) + (x >> 16);
	/* add up carry.. */
	x = (x & 0xffff) + (x >> 16);
	return x;
}

static unsigned int do_csum(const unsigned char *buff, int len)
{
	int odd, count;
	unsigned long result = 0;

	if (len <= 0)
		goto out;
	odd = 1 & (unsigned long) buff;
	if (odd) {
#ifdef __LITTLE_ENDIAN
		result = *buff;
#else
		result += (*buff << 8);
#endif
		len--;
		buff++;
	}
	count = len >> 1;		/* nr of 16-bit words.. */
	if (count) {
		if (2 & (unsigned long) buff) {
			result += *(unsigned short *) buff;
			count--;
			len -= 2;
			buff += 2;
		}
		count >>= 1;		/* nr of 32-bit words.. */
		if (count) {
			unsigned long carry = 0;
			do {
				unsigned long w = *(unsigned int *) buff;
				count--;
				buff += 4;
				result += carry;
				result += w;
				carry = (w > result);
			} while (count);
			result += carry;
			result = (result & 0xffff) + (result >> 16);
		}
		if (len & 2) {
			result += *(unsigned short *) buff;
			buff += 2;
		}
	}
	if (len & 1)
#ifdef __LITTLE_ENDIAN
		result += *buff;
#else
		result += (*buff << 8);
#endif
	result = from32to16(result);
	if (odd)
		result = ((result >> 8) & 0xff) | ((result & 0xff) << 8);
out:
	return result;
}



static inline unsigned short ip_fast_csum(const void *iph, unsigned int ihl)
{
	unsigned int sum;

	__asm__ __volatile__(
	    "movl (%1), %0	;\n"
	    "subl $4, %2	;\n"
	    "jbe 2f		;\n"
	    "addl 4(%1), %0	;\n"
	    "adcl 8(%1), %0	;\n"
	    "adcl 12(%1), %0	;\n"
"1:	    adcl 16(%1), %0	;\n"
	    "lea 4(%1), %1	;\n"
	    "decl %2		;\n"
	    "jne 1b		;\n"
	    "adcl $0, %0	;\n"
	    "movl %0, %2	;\n"
	    "shrl $16, %0	;\n"
	    "addw %w2, %w0	;\n"
	    "adcl $0, %0	;\n"
	    "notl %0		;\n"
"2:				;\n"
	/* Since the input registers which are loaded with iph and ipl
	   are modified, we must also specify them as outputs, or gcc
	   will assume they contain their original values. */
	: "=r" (sum), "=r" (iph), "=r" (ihl)
	: "1" (iph), "2" (ihl)
	: "memory");
	return (unsigned short)sum;
}


unsigned short checksum(void *buf, int size)
{
	unsigned short *buffer = (unsigned short *)buf;
	unsigned long cksum = 0;

	while (size > 1)
	{
		cksum += *buffer++;
		size -= sizeof(unsigned short);
	}

	if (size)
	{
		cksum += *(unsigned char*)buffer;
	}

   cksum = (cksum >> 16) + (cksum & 0xffff);
   cksum += (cksum >> 16);

   return (unsigned short)(~cksum);
}

#pragma pack(1)
static struct psd_header
{
	unsigned int saddr;
	unsigned int daddr;
	char mbz;
	char proto;
	unsigned short len;
};
unsigned short nat_hdr_checksum(void *old_header, void *new_header, int l)
{
	if((!old_header) || (!new_header))
		goto err;
	if(l == 3)
	{
		struct iphdr *iph = (struct iphdr *)new_header;
		if(!iph)
			return 0;
		else
		{
			iph->check = 0;
			return checksum(iph, (iph->ihl << 2));
		}
	}
	else if(l == 4)
	{
		struct iphdr *o_iph = (struct iphdr *)old_header;
		struct iphdr *n_iph = (struct iphdr *)new_header;

		void *o_l4hdr = old_header + (o_iph->ihl << 2);
		void *n_l4hdr = new_header + (n_iph->ihl << 2);
		long o_l4hdr_check = 0, n_l4hdr_check = 0;
		unsigned short *o_check;
		unsigned short o_l4hdr_len, n_l4hdr_len;

		struct psd_header o_ph, n_ph;

		if(o_iph->protocol == PKT_TYPE_UDP)
		{
			struct udphdr *o_uph = (struct udphdr *)o_l4hdr;
			struct udphdr *n_uph = (struct udphdr *)n_l4hdr;

			o_l4hdr_check = o_uph->check;
			o_check = &(o_uph->check);
			o_uph->check = 0;
			o_l4hdr_len = sizeof(*o_uph);
			n_uph->check = 0;
			n_l4hdr_len = sizeof(*n_uph);
		}
		else if(o_iph->protocol == PKT_TYPE_TCP)
		{
			struct tcphdr *o_tph = (struct tcphdr *)o_l4hdr;
			struct tcphdr *n_tph = (struct tcphdr *)n_l4hdr;

			o_l4hdr_check = o_tph->check;
			o_check = &(o_tph->check);
			o_tph->check = 0;
			o_l4hdr_len = sizeof(*o_tph);
			n_tph->check = 0;
			n_l4hdr_len = sizeof(*n_tph);
		}
		else
			goto err;

		o_ph.saddr = o_iph->saddr;
		o_ph.daddr = o_iph->daddr;
		o_ph.mbz = 0;
		o_ph.proto = o_iph->protocol;
		o_ph.len = htons(ntohs(o_iph->tot_len) - (o_iph->ihl << 2));
		//o_ph.len = o_iph->tot_len - o_iph->ihl << 2;

		n_ph.saddr = n_iph->saddr;
		n_ph.daddr = n_iph->daddr;
		n_ph.mbz = 0;
		n_ph.proto = n_iph->protocol;
		n_ph.len = htons(ntohs(n_iph->tot_len) - (n_iph->ihl << 2));
		//n_ph.len = n_iph->tot_len - n_iph->ihl << 2;

		n_l4hdr_check = o_l4hdr_check - checksum(&o_ph, sizeof(o_ph)) - checksum(o_l4hdr, o_l4hdr_len)
						+ checksum(&n_ph, sizeof(n_ph)) + checksum(n_l4hdr, n_l4hdr_len);
		*o_check = (unsigned short)o_l4hdr_check;

		return (unsigned short)n_l4hdr_check;
	}


err:
	return 0;
}


int nat_fast_csum(void *pkg, unsigned int n_sip, unsigned int n_dip,
								unsigned short n_sport, unsigned short n_dport)
{
	unsigned int sip = 0, dip = 0;
	unsigned short sport = 0, dport = 0;
	unsigned long sum = 0;
	unsigned long l3_check = 0;
	struct iphdr *iph;
	struct udphdr *uph;
	struct tcphdr *tph;
	unsigned short l4_check, *pl4_check;

	if(unlikely(!pkg))
		return 0;
	iph = P_IPP(pkg);
	if(iph->protocol == PKT_TYPE_UDP)
	{
		uph = P_UDPP(pkg);
		pl4_check = &uph->check;
		sport = uph->source;
		dport = uph->dest;
	}
	else if(iph->protocol == PKT_TYPE_TCP)
	{
		tph = P_TCPP(pkg);
		pl4_check = &tph->check;
		sport = uph->source;
		dport = uph->dest;
	}
	else
		return 0;
	sip = iph->saddr;
	dip = iph->daddr;


	if(n_sip)
	{
		unsigned short o1 = sip >> 16;
		unsigned short o2 = sip & 0xffff;
		unsigned short n1 = n_sip >> 16;
		unsigned short n2 = n_sip & 0xffff;
		sum += (ntohs(o1) + ntohs(o2) + (~ntohs(n1) & 0xffff) + (~ntohs(n2) & 0xffff));
	}
	if(n_dip)
	{
		unsigned short o1 = dip >> 16;
		unsigned short o2 = dip & 0xffff;
		unsigned short n1 = n_dip >> 16;
		unsigned short n2 = n_dip & 0xffff;
		sum += (ntohs(o1) + ntohs(o2) + (~ntohs(n1) & 0xffff) + (~ntohs(n2) & 0xffff));
	}
	l3_check = sum + ntohs(iph->check);

	if(n_sport)
		sum += (ntohs(sport) + (~(n_sport) & 0xffff));
	if(n_dport)
		sum += (ntohs(dport) + (~(n_dport) & 0xffff));

	l3_check = (l3_check & 0xffff) + (l3_check >> 16);
	iph->check = htons(l3_check + (l3_check >> 16));

	l4_check = *pl4_check;
	sum += ntohs(l4_check);
	sum = (sum & 0xffff) + (sum >> 16);
	*pl4_check = htons(sum + (sum >> 16));

	return 0;
}



int pkg_checksum(void *pkg)
{
	struct iphdr *iph;// = (struct iphdr *)ip_header;
	struct psd_header ph;
	void *l4hdr;
	unsigned short *l4chk = NULL;

	iph = P_IPP(pkg);
	if((!iph))
		goto err;

	l4hdr = (void *)iph + (iph->ihl << 2);
	if(iph->protocol == PKT_TYPE_UDP)
	{
		struct udphdr *uph = (struct udphdr *)l4hdr;
		l4chk = &uph->check;
	}
	else if(iph->protocol == PKT_TYPE_TCP)
	{
		struct tcphdr *tph = (struct tcphdr *)l4hdr;
		l4chk = &tph->check;
	}
	//fprintf(stderr, "0x%x 0x%x\n", iph->check, *l4chk);

	iph->check = 0;
	if(l4chk)
	{
        *l4chk = 0;
        ph.saddr = iph->saddr;
        ph.daddr = iph->daddr;
        ph.mbz = 0;
        ph.proto = iph->protocol;
        ph.len = htons(ntohs(iph->tot_len) - (iph->ihl << 2));
	}

	iph->check = checksum(iph, (iph->ihl << 2));
	if(l4chk)
        *l4chk = checksum(&ph, sizeof(ph)) + checksum(l4hdr, ntohs(ph.len));
	//fprintf(stderr, "0x%x 0x%x\n", iph->check, *l4chk);

	return 0;

err:
	return -1;
}


#include <getopt.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <signal.h>	/* signal */
#include <unistd.h>
#include <sys/shm.h>
#include <syslog.h>
#include <fcntl.h>
#include <efnet.h>
#include "efvpn.h"

static vpn_des *uvpnd;
static vpn_des *kvpnd;
static vpn_des *pvpnd;
static int *pvpn_id;
static int *pvpn_key;

static int pid_fd;
FILE *output = NULL;


static int get_des()
{
	pvpn_key = ftok("/etc/efvpn", (int)'a');
	pvpn_id = shmget(pvpn_key, sizeof(vpn_des) * 2, 0777);
	pvpnd = (vpn_des *)shmat(pvpn_id, NULL, 0);

	if((int)pvpnd == -1)
	{
		fprintf(stderr, "错误，vpn程序没有正在运行!\n");
		return 0;
	}

	uvpnd = pvpnd;
	kvpnd = pvpnd;
	if(pvpnd->use)
		uvpnd++;
	else
		kvpnd++;
	if(!kvpnd->use)
	{
		fprintf(stderr, "vpn程序正在初始化，请等待!\n");
		shmdt(pvpnd);
		return 0;
	}
	return 1;
}

static int rel_des()
{
	shmdt(pvpnd);
}

static int write_pid()
{
	int ret;
	pid_t pid = getpid();
	char buf[32] = {0};
	struct flock lock;

	pid_fd = open("/var/run/efvpn.pid", O_RDWR | O_CREAT, S_IRWXU | S_IRWXG | S_IRWXO);
	if(-1 == pid_fd)
	{
		fprintf(stderr, "create pid file error!\n");
		return -1;
	}

	lock.l_type = F_WRLCK;
	lock.l_whence = SEEK_SET;
	lock.l_start = 1;
	lock.l_len = 1;
	ret = fcntl(pid_fd, F_SETLK, &lock);
	if(ret < 0)
	{
		close(pid_fd);
		return 0;
	}
	else
	{
		sprintf(buf, "%d\n", (int)pid);
		if(write(pid_fd, buf, strlen(buf)) == strlen(buf))
			return 1;
		else
			close(pid_fd);
	}
	return 0;
}

static int close_pid()
{
	close(pid_fd);
}

static int link_init(vpn_link *link, unsigned short id, unsigned char enable,
					unsigned int gate, unsigned char *smac, short vlan,
					unsigned int sip, unsigned int dip)
{
	struct iphdr *iph;
	struct udphdr *uph;
	unsigned short *data;
	int size;

	memset(link, 0, sizeof(vpn_link));
	link->id = id;
	link->enable = enable;
	link->gate = gate;
	memcpy(link->smac, smac, 6);
	link->vlan = vlan;
	link->sip = sip;
	link->dip = dip;

	memcpy(link->link_hdr + 6, link->smac, 6);
	if(link->vlan >= 0)
	{
		ETHTYPE(link->link_hdr) = PKT_TYPE_VL;
		VLAN(link->link_hdr) = htons(link->vlan);
		VTHTYPE(link->link_hdr) = PKT_TYPE_IP;
		link->iph_offset = 18;
		iph = (struct iphdr *)(link->link_hdr + 18);
	}
	else
	{
		ETHTYPE(link->link_hdr) = PKT_TYPE_IP;
		link->iph_offset = 14;
		iph = (struct iphdr *)(link->link_hdr + 14);
	}
	iph->version = 0x4;
	iph->ihl = 0x5;
	iph->frag_off = 0x0040;
	iph->ttl = 0xff;
	iph->protocol = PKT_TYPE_UDP;
	iph->saddr = link->sip;
	iph->daddr = link->dip;
	data = (unsigned short *)iph;
	size = sizeof(struct iphdr);
	while (size > 1)
	{
		link->iph_sum += *data++;
		size -= sizeof(unsigned short);
	}
	if (size)
	{
		link->iph_sum += *(unsigned char*)data;
	}
	uph = (struct udphdr *)((char *)iph + sizeof(struct iphdr));
	uph->source = htons(VPN_HDR_UDP_SPORT);
	uph->dest = htons(VPN_HDR_UDP_DPORT);
	uph->check = 0;
	*(unsigned int *)(link->link_hdr + VPN_POSITION_FLAG) = VPN_FLAG_NORMAL;
	*(unsigned short *)(link->link_hdr + VPN_POSITION_LINK_ID) = id;
	return 0;
}

static int reb_vpn_alive_link()
{
	int i;
	memset(uvpnd->vpn_outbound_alive_links, 0, sizeof(uvpnd->vpn_outbound_alive_links));
	uvpnd->vpn_outbound_alive_link_cur = 0;
	uvpnd->vpn_outbound_alive_link_tot = 0;
	for(i = 0; i < uvpnd->vpn_outbound_link_tot; i++)
	{
		if(uvpnd->vpn_outbound_links[i].enable)
			uvpnd->vpn_outbound_alive_links[uvpnd->vpn_outbound_alive_link_tot++] = i;
	}
}

static int dis_vpn_link(int id)
{
	if((id < 0) || (id >= uvpnd->vpn_outbound_link_tot))
		return 0;
	if(!uvpnd->vpn_outbound_links[id].enable)
		return 0;
	uvpnd->vpn_outbound_links[id].enable = 0;
	reb_vpn_alive_link();
	return 1;
}

static int ena_vpn_link(int id)
{
	if((id < 0) || (id >= uvpnd->vpn_outbound_link_tot))
		return 0;
	if(uvpnd->vpn_outbound_links[id].enable)
		return 0;
	uvpnd->vpn_outbound_links[id].enable = 1;
	reb_vpn_alive_link();
	return 1;

}

static int del_vpn_link(int id)
{
	int i;
	vpn_link link = {0};

	if((id < 0) || (id >= uvpnd->vpn_outbound_link_tot))
		return 0;
	memcpy(&link, &uvpnd->vpn_outbound_links[id], sizeof(vpn_link));
	for(i = id; i < uvpnd->vpn_outbound_link_tot - 1; i++)
	{
		memcpy(&uvpnd->vpn_outbound_links[i],
				&uvpnd->vpn_outbound_links[i + 1],
				sizeof(vpn_link));
	}
	memset(&uvpnd->vpn_outbound_links[i], 0, sizeof(vpn_link));
	uvpnd->vpn_outbound_link_cur = 0;
	uvpnd->vpn_outbound_link_tot--;
	uvpnd->vpn_outbound_id_bitmap[link.id] = 0;
	uvpnd->vpn_outbound_ip_bitmap[(link.sip >> 16) ^ (link.sip & 0xffff)]--;
	reb_vpn_alive_link();
	return 1;
}

static int add_vpn_link(unsigned char *gate, int vlan, unsigned int sip, unsigned int dip)
{
	int i;
	vpn_link *link;
	unsigned short link_id;

	if(uvpnd->vpn_outbound_link_tot >= VPN_MAX_LINK)
		return 0;
	for(i = 0; i < uvpnd->vpn_outbound_link_tot; i++)
	{
		vpn_link *tmp = &uvpnd->vpn_outbound_links[i];
		if((gate == tmp->gate) && (vlan == tmp->vlan) && (sip == tmp->sip) && (dip == tmp->dip))
		{
			fprintf(output, "已经存在相同链路!\n");
			return 0;
		}
	}
	do
	{
		link_id = random() % 0xffff;
	}while(uvpnd->vpn_outbound_id_bitmap[link_id]);
	uvpnd->vpn_outbound_link_cur = 0;
	link = &uvpnd->vpn_outbound_links[uvpnd->vpn_outbound_link_tot];
	link_init(link, link_id, 0, gate, kvpnd->vpn_outbound_mac, vlan, sip, dip);
	uvpnd->vpn_outbound_link_tot++;
	uvpnd->vpn_outbound_id_bitmap[link_id] = 1;
	uvpnd->vpn_outbound_ip_bitmap[(sip >> 16) ^ (sip & 0xffff)]++;
	return 1;
}

static void usage()
{
	fprintf(stderr, "Print vpn info      : efvpn --print or efvpn -p\n");
	fprintf(stderr, "Clean vpn info      : efvpn --clean or efvpn -c\n");
	fprintf(stderr, "SAVE  vin info      : efvpn --save or efvpn -s\n");
	fprintf(stderr, "Add vpn link        : efvpn --add or efvpn -a mac,vlan,local_ip,dest_ip\n");
	fprintf(stderr, "Del vpn link        : efvpn --del or efvpn -d id\n");
	fprintf(stderr, "Enalbe vpn link     : efvpn --enable id\n");
	fprintf(stderr, "Disable vpn link    : efvpn --disable id\n");
	fprintf(stderr, "Enable link check   : efvpn --check-enable\n");
	fprintf(stderr, "Disable link check  : efvpn --check-disable\n");
	fprintf(stderr, "Enable link log     : efvpn --log-enable log_server,port,level\n");
	fprintf(stderr, "Disable link log    : efvpn --log-disable\n");
}

int main(int argc, char **argv)
{
	static struct option vpn_option[] =
	{
		{"add", 1, 0, 'a'},
		{"del", 1, 0, 'd'},
		{"enable", 1, 0, 1},
		{"disable", 1, 0, 2},
		{"log-enable", 1, 0, 3},
		{"log-disable", 0, 0, 4},
		{"log-clean", 0, 0, 5},
		//{"check-enable", 0, 0, 5},
		//{"check-disable", 0, 0, 6},
		{"clean", 0, 0, 'c'},
		{"save", 0, 0, 's'},
		{"print", 0, 0, 'p'},
		{"output", 1, 0, 'o'},
		{"help", 0, 0, 'h'},
		{0, 0, 0, 0}
	};

	int ret = 0;
	int ch;
	int add_link = 0;
	int del_link = 0;
	int ena_link = 0;
	int dis_link = 0;
	int clean = 0;
	int print = 0;
	int save = 0;
	int enable_log = 0;
	int disable_log = 0;
	int clean_log = 0;
	int enable_check = 0;
	int disable_check = 0;
	unsigned char *out = NULL;
	unsigned char *val = NULL;



	while((ch = getopt_long(argc, argv, "a:d:cspo:h", vpn_option, NULL)) != -1)
	{
		switch(ch)
		{
			case 'a':
				add_link = 1;
				val = optarg;
				break;
			case 'd':
				del_link = 1;
				val = optarg;
				break;
			case 1:
				ena_link = 1;
				val = optarg;
				break;
			case 2:
				dis_link = 1;
				val = optarg;
				break;
			case 3:
				enable_log = 1;
				val = optarg;
				break;
			case 4:
				disable_log = 1;
				break;
            case 5:
                clean_log = 1;
                break;
			//case 5:
				//enable_check = 1;
				//break;
			//case 6:
				//disable_check = 1;
				//break;
			case 'c':
				clean = 1;
				break;
			case 's':
				save = 1;
				break;
			case 'p':
				print = 1;
				break;
			case 'o':
				out = optarg;
				break;
			case 'h':
				usage();
				return 0;
				break;
			default:
				fprintf(stderr, "error option!\n");
				return 0;
		}
	}

	if(!print)
	{
		if(daemon(1, 1) < 0)
			return ret;
		if(write_pid() != 1)
		{
			fprintf(stderr, "write pid err!\n");
			return ret;
		}
	}
	if(!get_des())
	{
		close_pid();
		return ret;
	}

	memcpy(uvpnd, kvpnd, sizeof(vpn_des));
	uvpnd->use = 0;

	if(out)
		output = fopen(out, "w+");
	else
		output = stderr;
	if(print)
	{
		int i;
		unsigned char gate[32];
		unsigned char mac[32];
		int vlan;
		unsigned char sip[32];
		unsigned char dip[32];
		unsigned char enable;
		unsigned int delay;
		unsigned int lost;
		long timeout;
		unsigned long long pkg, flow;

		fprintf(output, "efvpn info:\n");
		fprintf(output, "sys_name %s\nsys_version %s\n", SYS_NAME, SYS_VERSION);
		if(kvpnd->check)
			fprintf(output, "link-check is running...\n");
		if(kvpnd->log_ip)
		{
		    char ip[32] = {0};
		    ip_2_str(kvpnd->log_ip, ip);
		    fprintf(output, "link-log %u,%s,%u,%u", kvpnd->log, ip, kvpnd->log_port, kvpnd->log_level);
		}
		fprintf(output, "\n");
		for(i = 0; i < kvpnd->vpn_outbound_link_tot; i++)
		{
			vpn_link *link = &kvpnd->vpn_outbound_links[i];
			ip_2_str(link->gate, gate);
			mac_2_str(link->dmac, mac);
			vlan = link->vlan;
			ip_2_str(link->sip, sip);
			ip_2_str(link->dip, dip);
			enable = link->enable;
			pkg = link->pkg;
			flow = link->flow;
			delay = link->delay;
			lost = link->lost;
			timeout = link->ask - link->reply;

			fprintf(output, "link \t: (%d)|%s(%s) , %d| --- |%s --> %s| %s\t", i, gate, mac, vlan, sip, dip,
						enable ? "enable" : "disable");
			if(1 || kvpnd->check)
			{
				if(timeout > VPN_LINK_TIMEOUT)
					fprintf(output, "timeout!\n");
				else
					fprintf(output, "%.3f ms \t send:%llu pkg \t flow:%llu byte \t lost:%u%%\n", (float)delay/1000, pkg, flow, lost);
			}
			else
			{
				fprintf(output, "\n");
			}
		}
		goto finish;
	}

	if(clean)
	{
		memset(uvpnd->vpn_outbound_links, 0, sizeof(uvpnd->vpn_outbound_links));
		memset(uvpnd->vpn_outbound_alive_links, 0, sizeof(uvpnd->vpn_outbound_alive_links));
		memset(uvpnd->vpn_outbound_id_bitmap, 0, sizeof(uvpnd->vpn_outbound_id_bitmap));
		memset(uvpnd->vpn_outbound_ip_bitmap, 0, sizeof(uvpnd->vpn_outbound_ip_bitmap));

		uvpnd->vpn_outbound_link_cur = 0;
		uvpnd->vpn_outbound_link_tot = 0;
		uvpnd->vpn_outbound_alive_link_cur = 0;
		uvpnd->vpn_outbound_alive_link_tot = 0;

		uvpnd->mod = 1;
		goto vpnd_mod;
	}

	if(save)
	{
		int i;
		char gate[32];
		short vlan;
		char sip[32];
		char dip[32];
		char enable;

		FILE *file = fopen("/etc/efvpn/conf", "w+");
		if(file)
		{
			for(i = 0; i < kvpnd->vpn_outbound_link_tot; i++)
			{
				ip_2_str(kvpnd->vpn_outbound_links[i].gate, gate);
				vlan = kvpnd->vpn_outbound_links[i].vlan;
				ip_2_str(kvpnd->vpn_outbound_links[i].sip, sip);
				ip_2_str(kvpnd->vpn_outbound_links[i].dip, dip);
				enable = kvpnd->vpn_outbound_links[i].enable;

				fprintf(file, "vpn_link %s %d %s %s %d\n", gate, vlan, sip, dip, enable);
			}
			fclose(file);
		}
		goto vpnd_mod;
	}

	if(enable_log)
	{
		unsigned char *ip, *port, *level;
		if(!val)
		{
			fprintf(output, "缺少参数\n");
			goto vpnd_mod;
		}
		ip = strtok(val, ",");
		if(!ip)
		{
			fprintf(output, "缺少ip!\n");
			goto vpnd_mod;
		}
		port = strtok(NULL, ",");
		if(!port)
		{
			fprintf(output, "缺少端口!\n");
			goto vpnd_mod;
		}
		level = strtok(NULL, ",");
		if(!level)
		{
			fprintf(output, "缺少等级!\n");
			goto vpnd_mod;
		}
		if(str_2_ip(ip) && (atoi(port) > 0) && (atoi(port) < 0xffff))
		{
			uvpnd->log = 1;
			uvpnd->log_ip = str_2_ip(ip);
			uvpnd->log_port = atoi(port);
			if(atoi(level) < 0)
				uvpnd->log_level = 0;
			else if(atoi(level) > 7)
				uvpnd->log_level = 7;
			else
				uvpnd->log_level = atoi(level);
			uvpnd->mod = 1;
			goto vpnd_mod;
		}
		else
		{
			fprintf(output, "ip地址格式有误!\n");
			goto vpnd_mod;
		}
	}

	if(disable_log)
	{
		if(uvpnd->log)
		{
			uvpnd->log = 0;
			uvpnd->mod = 1;
			goto vpnd_mod;
		}
		else
			fprintf(output, "vpn日志模块没有正在运行!\n");
	}

	if(clean_log)
	{
        if(uvpnd->log_ip)
        {
            uvpnd->log = 0;
            uvpnd->log_ip = 0;
            uvpnd->log_port = 0;
            uvpnd->log_level = 0;
            uvpnd->mod = 1;
            goto vpnd_mod;
        }
	}

	if(enable_check)
	{
		if(!uvpnd->check)
		{
			uvpnd->check = 1;
			uvpnd->mod = 1;
			goto vpnd_mod;
		}
	}

	if(disable_check)
	{
		if(uvpnd->check)
		{
			uvpnd->check = 0;
			uvpnd->mod = 1;
			goto vpnd_mod;
		}
	}

	if(add_link)
	{
		unsigned char *gate_str, *vlan_str;
		unsigned char *sip_str, *dip_str;
		unsigned int gate;
		int vlan = -1;
		unsigned int sip;
		unsigned int dip;

		if(!val)
		{
			fprintf(output, "缺少参数!\n");
			goto vpnd_mod;
		}
		gate_str = strtok(val, ",");
		if(!gate_str)
		{
			fprintf(output, "缺少下一跳!\n");
			goto vpnd_mod;
		}
		vlan_str = strtok(NULL, ",");
		if(!vlan_str)
		{
			fprintf(output, "缺少vlan!\n");
			goto vpnd_mod;
		}
		sip_str = strtok(NULL, ",");
		if(!sip_str)
		{
			fprintf(output, "缺少本地地址!\n");
			goto vpnd_mod;
		}
		dip_str = strtok(NULL, ",");
		if(!dip_str)
		{
			fprintf(output, "缺少目标地址!\n");
			goto vpnd_mod;
		}

		if(!(gate = str_2_ip(gate_str)))
		{
			fprintf(output, "下一跳地址格式有误!\n");
			goto vpnd_mod;
		}
		vlan = atoi(vlan_str);
		if((vlan > 0xfff) || (vlan < -1))
		{
			fprintf(output, "vlan值有误!\n");
			goto vpnd_mod;
		}
		if(!(sip = str_2_ip(sip_str)))
		{
			fprintf(output, "本地地址格式有误!\n");
			goto vpnd_mod;
		}
		if(!(dip = str_2_ip(dip_str)))
		{
			fprintf(output, "目标地址格式有误!\n");
			goto vpnd_mod;
		}

		if(add_vpn_link(gate, vlan, sip, dip))
			uvpnd->mod = 1;
		goto vpnd_mod;
	}

	if(del_link)
	{
		if(!val)
		{
			fprintf(output, "缺少参数!\n");
			goto vpnd_mod;
		}
		if(del_vpn_link(atoi(val)))
			uvpnd->mod = 1;
		goto vpnd_mod;
	}

	if(ena_link)
	{
		if(!val)
		{
			fprintf(output, "缺少参数!\n");
			goto vpnd_mod;
		}
		if(ena_vpn_link(atoi(val)))
			uvpnd->mod = 1;
		goto vpnd_mod;
	}

	if(dis_link)
	{
		if(!val)
		{
			fprintf(output, "缺少参数!\n");
			goto vpnd_mod;
		}
		if(dis_vpn_link(atoi(val)))
			uvpnd->mod = 1;
		goto vpnd_mod;
	}

vpnd_mod:
	if(uvpnd->mod)
	{
		while(!uvpnd->use)
			usleep(1000);
		uvpnd->mod = 0;
		memcpy(kvpnd, uvpnd, sizeof(vpn_des));
		kvpnd->use = 0;
		ret = 1;
	}
	close_pid();

finish:
	rel_des();
	if(out)
	{
		if(ret)
			fprintf(output, "succ");
		fclose(output);
	}
	return ret;
}

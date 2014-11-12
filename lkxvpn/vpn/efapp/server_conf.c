#include <getopt.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <signal.h>	/* signal */
#include <unistd.h>
#include <sys/shm.h>
#include <syslog.h>
#include <fcntl.h>

#include <openssl/rsa.h>
#include <openssl/dsa.h>
#include <openssl/pem.h>
#include <openssl/err.h>
#include <openssl/evp.h>
#include <openssl/md5.h>
#include <openssl/sha.h>

#include <efio.h>
#include <efnet.h>
#include <efext.h>
#include "conf.h"
#include <zmq.h>

char dev_backend[32];
char dev_outbound[32];
char dev_manager[32];

static int pid_fd;

int get_dev()
{
	char buf[1024] = {0};
	FILE *fp = fopen("/etc/efvpn/dev", "r");
	if(fp)
	{
		int ret = 0;
		char *dev;
		int len;
		fgets(buf, sizeof(buf), fp);
		if(!strlen(buf))
			goto finish;
		dev = strtok(buf, ",");
		if(!dev)
			goto finish;
		strncpy(dev_backend, dev, 32);
		dev = strtok(NULL, ",");
		if(!dev)
			goto finish;
		strncpy(dev_outbound, dev, 32);
		dev = strtok(NULL, ",");
		if(!dev)
			goto finish;
		strncpy(dev_manager, dev, 32);
		len = strlen(dev_manager);
		dev_manager[len-1] = 0;
		if(strlen(dev_backend) && strlen(dev_outbound) && strlen(dev_manager))
			ret = 1;
	finish:
		fclose(fp);
		return ret;
	}
	return 0;
}

/*
int get_uuid(char *val)
{
    uuid_t uuid;
    uuid_generate(uuid);
    uuid_unparse(uuid, val);
    return 0;
}
*/

int get_server_id(unsigned char *val)
{
    unsigned char mac1[64] = {0}, mac2[64] = {0};
    unsigned char md51[64] = {0};
    int i, size;

    if(!val)
        return 0;

    get_dev_mac(dev_backend, mac1);
    mac_2_str(mac1, mac2);
    size = strlen(mac2);
    mac2[size++] = ' ';
    get_dev_mac(dev_outbound, mac1);
    mac_2_str(mac1, &mac2[size]);
    size = strlen(mac2);
    MD5(mac2, size, md51);
    for (i = 0; i < 16; i++)
    {
        sprintf(val + i * 2, "%2.2x", md51[i]);
    }
    return 1;
}

int get_license(const char *ip, unsigned int port)
{
    if(dev_if_up(dev_backend) && dev_if_up(dev_outbound))
    {
        FILE *fp = fopen("/etc/efvpn/license", "wb+");
        unsigned char server[128] = {0};
        unsigned char sid[64] = {0};
        unsigned char license[1024] = {0};
        unsigned int license_len = 0;
        int len, rc;
        int retry = 3;

        void *context = zmq_ctx_new ();
        int linger = 1;
        void *requester;
        snprintf(server, sizeof(server), "tcp://%s:%d", ip, port);

        get_server_id(sid);
        len = strlen(sid);

do_zmq:
        requester = zmq_socket (context, ZMQ_REQ);
        zmq_setsockopt(requester, ZMQ_LINGER, &linger, sizeof(linger));
        zmq_connect (requester, server);
        zmq_send (requester, sid, len, 0);
        zmq_pollitem_t items [] = { { requester, 0, ZMQ_POLLIN, 0 } };
        rc = zmq_poll (items, 1, 3000);
        if (rc == -1)
            goto do_end; // Interrupted
        if (items [0].revents & ZMQ_POLLIN)
        {
            // 接收反馈
            license_len = zmq_recv (requester, license, sizeof(license), 0);
        }
        // 重试连接
        else
        {
            //printf("Retry connecting ...\n");
            zmq_close (requester);
            if(--retry)
                goto do_zmq;
        }
do_end:
        zmq_close (requester);
        zmq_ctx_destroy (context);

        if(!license_len)
            return -1;
        else
        {
            fwrite(license, 1, license_len, fp);
            fclose(fp);
            if(!strncasecmp(license, "fail", 4))
                return 0;
            else
                return 1;
        }
    }
    return 0;
}

int check_license(char *file)
{
    FILE *license = fopen(file, "r");
    FILE *key = fopen("/etc/efvpn/key", "r");
    RSA *rsa = NULL;
    int rsa_len;

    char license_buf[1024] = {0};
    char key_buf[1024] = {0};
    unsigned char md5[16];
    unsigned char key_md51[33] = "2ddd205ecde91edf1c2483b569281ccf";
    unsigned char key_md52[33] = {0};
    int len, i, is_valid_signature = 0;

    if(!license || !key)
    {
        fprintf(stderr, "no license or key!\n");
        goto end;
    }

    len = 0;
    for(;;)
    {
        int r = fread(key_buf + len, 1, 1024, key);
        if(r <= 0) break;
        len += r;
    }

    MD5(key_buf, len, md5);
    for (i = 0; i < 16; i++)
    {
        sprintf(key_md52 + i * 2, "%2.2x", md5[i]);
    }

    #ifdef VPN_KEY_MD5
        if(strncasecmp(key_md52, VPN_KEY_MD5, 32))
        {
            fprintf(stderr, "key is not right!\n");
            goto end;
        }
    #else
        if(strncasecmp(key_md52, key_md51, 32))
        {
            fprintf(stderr, "key is not right!\n");
            goto end;
        }
    #endif // VPN_KEY_MD5

    len = 0;
    for(;;)
    {
        int r = fread(license_buf + len, 1, 1024, license);
        if(r <= 0) break;
        len += r;
    }

    fseek(key, 0, SEEK_SET);
    if ((rsa = PEM_read_RSA_PUBKEY(key, NULL, NULL, NULL)) == NULL)
    {
        ERR_print_errors_fp(stdout);
        goto end;
    }
    else
    {
        unsigned char mac[64] = {0};
        unsigned char md51[64] = {0}, md52[64] = {0};
        SHA_CTX s;
        int i, size;
        char c[512];
        unsigned char hash[20];

        get_server_id(md52);
        SHA1_Init(&s);
        SHA1_Update(&s, md52, 32);
        SHA1_Final(hash, &s);
        is_valid_signature = RSA_verify(NID_sha1, hash/*xbuf*/, 20/*strlen((char*)xbuf)*/, license_buf, len, rsa);
        if(is_valid_signature != 1)
            is_valid_signature = 0;
        else
        {
            if(strncasecmp(file, "/etc/efvpn/license", strlen("/etc/efvpn/license")))
            {
                FILE *save = fopen("/etc/efvpn/license", "wb+");
                fwrite(license_buf, 1, len, save);
                fclose(save);
            }
        }
        RSA_free(rsa);
    }

end:
    if(key)
        fclose(key);
    if(license)
        fclose(license);
    return is_valid_signature;
}

int set_pass(char *pass)
{
	FILE *fp = fopen("/etc/efvpn/pass", "w+");
	if(fp)
	{
		fprintf(fp, "%s", pass);
		fclose(fp);
		return 1;
	}
	return 0;
}

int set_net_backend(char *ip, char *mask)
{
    char mac[32] = {0};
    char mac_str[32] = {0};
	char cmd[1024] = {0};
	char file[1024] = {0};
	FILE *fp = NULL;

	snprintf(file, sizeof(file), "/etc/sysconfig/network-scripts/ifcfg-%s", dev_backend);
	fp = fopen(file, "w+");
	if(fp)
	{
        get_dev_mac(dev_backend, mac);
        mac_2_str(mac, mac_str);
		fprintf(fp, "DEVICE=%s\nHWADDR=%s\nIPADDR=%s\nNETMASK=%s\nONBOOT=YES", dev_backend, mac_str, ip, mask);
		fclose(fp);
		snprintf(cmd, sizeof(cmd), "/sbin/ifconfig %s %s netmask %s", dev_backend, ip, mask);
		system(cmd);
		return 1;
	}
	return 0;
}

int set_net_outbound(char *ip, char *mask, char *gate)
{
    char mac[32] = {0};
    char mac_str[32] = {0};
	char cmd[1024] = {0};
	char file[1024] = {0};
	FILE *fp = NULL;

	snprintf(file, sizeof(file), "/etc/sysconfig/network-scripts/ifcfg-%s", dev_outbound);
	fp = fopen(file, "w+");
	if(fp)
	{
	    get_dev_mac(dev_outbound, mac);
        mac_2_str(mac, mac_str);
		fprintf(fp, "DEVICE=%s\nHWADDR=%s\nIPADDR=%s\nNETMASK=%s\nGATEWAY=%s\nONBOOT=YES", dev_outbound, mac_str, ip, mask, gate);
		fclose(fp);
		snprintf(cmd, sizeof(cmd), "/sbin/ifconfig %s %s netmask %s", dev_outbound, ip, mask);
		system(cmd);
		memset(cmd, 0, sizeof(cmd));
		snprintf(cmd, sizeof(cmd), "/sbin/ip route replace default via %s", gate);
		system(cmd);
		return 1;
	}
	return 0;
}

int set_dhcp(char *ip, char *net, char *mask, char *ip_begin, char *ip_end)
{
    char mac[32] = {0};
    char mac_str[32] = {0};
	char cmd[1024] = {0};
	char file[1024] = {0};
	FILE *fp = fopen("/usr/local/etc/dhcpd.conf", "w+");
	FILE *fp2 = NULL;

	snprintf(file, sizeof(file), "/etc/sysconfig/network-scripts/ifcfg-%s", dev_manager);
	fp2 = fopen(file, "w+");
	if(fp && fp2)
	{
        get_dev_mac(dev_manager, mac);
        mac_2_str(mac, mac_str);
		fprintf(fp, "ddns-update-style none;\ndefault-lease-time 21600;\nmax-lease-time 43200;\n");
		fprintf(fp, "subnet %s netmask %s {\n\trange %s %s;\n\toption subnet-mask %s;\n\toption routers %s;\n}",
			net, mask, ip_begin, ip_end, mask, ip);
		fclose(fp);
		fprintf(fp2, "DEVICE=%s\nHWADDR=%s\nIPADDR=%s\nNETMASK=%s\nONBOOT=YES", dev_manager, mac_str, ip, mask);
		fclose(fp2);
		snprintf(cmd, sizeof(cmd), "/sbin/ifconfig %s %s netmask %s", dev_manager, ip, mask);
		system(cmd);
		system("/usr/bin/killall dhcpd");
		system("/bin/touch /var/db/dhcpd.leases");
		system("/usr/local/sbin/dhcpd -cf /usr/local/etc/dhcpd.conf -lf /var/db/dhcpd.leases");
		return 1;
	}
	return 0;
}

int set_ntp(char *server)
{
    char cmd[1024] = {0};
    FILE *fp = fopen("/etc/efvpn/update_time", "w");
    if(fp)
    {
        fprintf(fp, "/usr/sbin/ntpdate %s", server);
        system("/usr/bin/killall ntpdate");
        sprintf(cmd, "/usr/sbin/ntpdate %s > /dev/null 2>&1 &", server);
        system(cmd);
        fclose(fp);
        return 1;
    }
    return 0;
}

int server_info(char *info)
{
    CONF_DES *cd;
    char server_id[64] = {0};
    char version[32] = {0};
    char runtime[128] = {0};
    char key[1024] = {0};
    char val[1024] = {0};

    if(!info)
        return 0;
    conf_init(&cd);
    get_server_id(server_id);
    if(conf_read_file(cd, "/proc/version", " ", 0))
    {
        conf_getkey(cd, key, sizeof(key), 1);
        conf_getval(cd, key, val, sizeof(val), 2);
        sprintf(version, "Linux %s\0", val);
    }
    if(conf_read_file(cd, "/proc/uptime", ".", 0))
    {
        unsigned long tmp = 0;
        conf_getkey(cd, key, sizeof(key), 1);
        tmp = strtoul(key, 0, 10);
        sprintf(runtime, "系统已运行 %d天%d时%d分%d秒\0", tmp/86400, (tmp % 86400)/3600, (tmp % 3600)/60, tmp%60);
    }
    conf_uninit(&cd);
    sprintf(info, "%s\n%s\n%s", server_id, version, runtime);
    return 1;
}

int server_shutdown()
{
    system("/sbin/shutdown -h now");
}

int server_reboot()
{
    system("/sbin/reboot");
}

static int write_pid()
{
	int ret;
	pid_t pid = getpid();
	char buf[32] = {0};
	struct flock lock;

	pid_fd = open("/var/run/server_conf.pid", O_RDWR | O_CREAT, S_IRWXU | S_IRWXG | S_IRWXO);
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

static void usage()
{
	fprintf(stderr, "Set password      : server_conf --pass 123456\n");
	fprintf(stderr, "Set backend       : server_conf --backend ip,mask\n");
	fprintf(stderr, "Set outbound      : server_conf --outbound ip,mask,gateway\n");
	fprintf(stderr, "Set dhcp          : server_conf --dhcp ip,mask,ip_range_begin,ip_range_end\n");
}

int main(int argc, char **argv)
{
	static struct option srv_option[] =
	{
		{"pass", 1, 0, 1},
		{"backend", 1, 0, 2},
		{"outbound", 1, 0, 3},
		{"dhcp", 1, 0, 4},
		{"ntp", 1, 0, 5},
		{"devstat", 1, 0, 6},
		//{"reset", 0, 0, 7},
		{"sysinfo", 0, 0, 7},
		{"license-get", 1, 0, 8},
		{"license-check", 1, 0, 9},
		{"shutdown", 0, 0, 10},
		{"reboot", 0, 0, 11},
		{"output", 1, 0, 12},
		{"help", 0, 0, 'h'},
		{0, 0, 0, 0}
	};

	int ret = 0;
	int ch;
	char *pass = NULL;
	char *backend = NULL;
	char *outbound = NULL;
	char *dhcp = NULL;
	char *ntp = NULL;
	char *devstat = NULL;
	char *license_get = NULL;
	char *license_check = NULL;
	int sysinfo = 0;
	int syshalt = 0;
	int sysreboot = 0;
	//int reset = 0;
	char *out = NULL;
    FILE *output = NULL;

    efio_link();
	while((ch = getopt_long(argc, argv, "h", srv_option, NULL)) != -1)
	{
		switch(ch)
		{
			case 1:
				pass = optarg;
				break;
			case 2:
				backend = optarg;
				break;
			case 3:
				outbound = optarg;
				break;
			case 4:
				dhcp = optarg;
				break;
            case 5:
                ntp = optarg;
                break;
            case 6:
                devstat = optarg;
                break;
            //case 7:
            //    reset = 1;
            //    break;
            case 7:
                sysinfo = 1;
                break;
            case 8:
                license_get = optarg;
                break;
            case 9:
                license_check = optarg;
                break;
            case 10:
                syshalt = 1;
                break;
            case 11:
                sysreboot = 1;
                break;
			case 12:
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

	if(write_pid() != 1)
		return ret;

	output = stderr;
	if(out)
	{
		output = fopen(out, "w+");
	}
	if(pass)
	{
		ret = set_pass(pass);
		goto finish;
	}

	if(!get_dev())
		goto finish;

    if(license_get)
    {
        /*char buf[32] = {0};
        char mac[32] = {0};
        char ip[32] = {0};
        if(!strncmp(devinfo, "outbound", strlen("outbound")))
        {
            if(dev_if_up(dev_outbound))
            {
                get_dev_mac(dev_outbound, buf);
                mac_2_str(buf, mac);
                ip_2_str(get_dev_ip(dev_outbound), ip);
                ret = 1;
            }
        }
        else if(!strncmp(devinfo, "backend", strlen("backend")))
        {
            if(dev_if_up(dev_backend))
            {
                get_dev_mac(dev_backend, buf);
                mac_2_str(buf, mac);
                ip_2_str(get_dev_ip(dev_backend), ip);
                ret = 1;
            }
        }
        if(ret)
            fprintf(output, "%s\n%s\n", mac, ip);
        */
        char *ip = NULL;
		char *port = NULL;

		ip = strtok(license_get, ",");
		if(!ip)
		{
			fprintf(output, "缺少ip地址!\n");
			goto finish;
		}
		port = strtok(NULL, ",");
		if(!port)
		{
			fprintf(output, "缺少port!\n");
			goto finish;
		}

		if(!str_2_ip(ip))
		{
			fprintf(output, "ip地址格式有误!\n");
			goto finish;
		}
		if(atoi(port) <= 0)
		{
			fprintf(output, "port格式有误!\n");
			goto finish;
		}
        ret = get_license(ip, atoi(port));
        fprintf(stderr, "ret = %d\n", ret);
        if(ret == -1)
            fprintf(output, "Connect server fail!\n");
        goto finish;
    }

    if(license_check)
    {
        ret = check_license(license_check);
        goto finish;
    }

    if(sysinfo)
    {
        char buf[1024] = {0};
        ret = server_info(buf);
        if(strlen(buf))
            fprintf(output, "%s\n", buf);
    }

    if(syshalt)
    {
        close_pid();
        server_shutdown();
    }

    if(sysreboot)
    {
        close_pid();
        server_reboot();
    }

    if(devstat)
    {
        if(!strncmp(devstat, "outbound", strlen("outbound")))
            ret = dev_if_run(dev_outbound);
        else if(!strncmp(devstat, "backend", strlen("backend")))
            ret = dev_if_run(dev_backend);
        else if(!strncmp(devstat, "manager", strlen("manager")))
            ret = dev_if_run(dev_manager);
    }

	if(backend)
	{
		char *ip = NULL;
		char *mask = NULL;

		ip = strtok(backend, ",");
		if(!ip)
		{
			fprintf(output, "缺少ip地址!\n");
			goto finish;
		}
		mask = strtok(NULL, ",");
		if(!mask)
		{
			fprintf(output, "缺少子网掩码!\n");
			goto finish;
		}

		if(!str_2_ip(ip))
		{
			fprintf(output, "ip地址格式有误!\n");
			goto finish;
		}
		if(!str_2_ip(mask))
		{
			fprintf(output, "子网掩码格式有误!\n");
			goto finish;
		}

		ret = set_net_backend(ip, mask);
		goto finish;
	}

	if(outbound)
	{
		char *ip = NULL;
		char *mask = NULL;
		char *gate = NULL;

		ip = strtok(outbound, ",");
		if(!ip)
		{
			fprintf(output, "缺少ip地址!\n");
			goto finish;
		}
		mask = strtok(NULL, ",");
		if(!mask)
		{
			fprintf(output, "缺少子网掩码!\n");
			goto finish;
		}
		gate = strtok(NULL, ",");
		if(!gate)
		{
			fprintf(output, "缺少网关!\n");
			goto finish;
		}

		if(!str_2_ip(ip))
		{
			fprintf(output, "ip地址格式有误!\n");
			goto finish;
		}
		if(!str_2_ip(mask))
		{
			fprintf(output, "子网掩码格式有误!\n");
			goto finish;
		}
		if(!str_2_ip(gate))
		{
			fprintf(output, "网关格式有误!\n");
			goto finish;
		}

		if((str_2_ip(ip) & str_2_ip(mask)) != (str_2_ip(gate) & str_2_ip(mask)))
		{
			fprintf(output, "ip地址与网关不在同一子网!\n");
			goto finish;
		}
		ret = set_net_outbound(ip, mask, gate);
		goto finish;
	}

	if(dhcp)
	{
		char *ip = NULL;
		char *mask = NULL;
		char *ip_begin = NULL, *ip_end = NULL;
		unsigned int net = 0;
		char net_str[32] = {0};

		ip = strtok(dhcp, ",");
		if(!ip)
		{
			fprintf(output, "缺少ip地址!\n");
			goto finish;
		}
		mask = strtok(NULL, ",");
		if(!mask)
		{
			fprintf(output, "缺少子网掩码!\n");
			goto finish;
		}
		ip_begin = strtok(NULL, ",");
		if(!ip_begin)
		{
			fprintf(output, "缺少开始地址!\n");
			goto finish;
		}
		ip_end = strtok(NULL, ",");
		if(!ip_end)
		{
			fprintf(output, "缺少结束地址!\n");
			goto finish;
		}

		if(!str_2_ip(ip))
		{
			fprintf(output, "ip地址格式有误!\n");
			goto finish;
		}
		if(!str_2_ip(mask))
		{
			fprintf(output, "子网掩码格式有误!\n");
			goto finish;
		}
		if(!str_2_ip(ip_begin))
		{
			fprintf(output, "开始地址格式有误!\n");
			goto finish;
		}
		if(!str_2_ip(ip_end))
		{
			fprintf(output, "结束地址格式有误!\n");
			goto finish;
		}

		net = str_2_ip(ip) & str_2_ip(mask);
		ip_2_str(net, net_str);
		if(ntohl(str_2_ip(ip_begin)) > ntohl(str_2_ip(ip_end)))
		{
		    fprintf(output, "错误的地址池!\n");
			goto finish;
		}
		if((str_2_ip(ip_begin) & str_2_ip(mask)) != net)
		{
			fprintf(output, "开始地址与ip地址不在同一子网!\n");
			goto finish;
		}
		if((str_2_ip(ip_end) & str_2_ip(mask)) != net)
		{
			fprintf(output, "结束地址与ip地址不在同一子网!\n");
			goto finish;
		}
		if( (ntohl(str_2_ip(ip_begin)) <= ntohl(str_2_ip(ip))) && (ntohl(str_2_ip(ip)) <= ntohl(str_2_ip(ip_end))) )
		{
		    fprintf(output, "ip地址不能位于地址池中!\n");
		    goto finish;
		}
		ret = set_dhcp(ip, net_str, mask, ip_begin, ip_end);
		goto finish;
	}

	if(ntp)
	{
	    ret = set_ntp(ntp);
	    goto finish;
	}

    /*
	if(reset)
	{
	    ret = system("/bin/sh /etc/efvpn/reset");
	    goto finish;
	}
	*/

finish:
	if(out)
	{
		if(ret == 1)
			fprintf(output, "succ");
		fclose(output);
	}
	close_pid();
	return ret;
}

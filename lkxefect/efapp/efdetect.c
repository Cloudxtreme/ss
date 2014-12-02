#include <getopt.h>
#include <efdetect.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/shm.h>


static void usage()
{
	fprintf(stderr, "Add ip    : efdetect --name outbound --add-ip xxx.xxx.xxx.xxx\n");
	fprintf(stderr, "Del ip    : efdetect --name outbound --del-ip xxx.xxx.xxx.xxx\n");
}

static detect_opera *get_opera(const unsigned char *name)
{
    char path[1024];
    snprintf(path, sizeof(path), "/etc/efdetect/%s\0", name);
    int *key = ftok(path, (int)'a');
	int *id = shmget(key, sizeof(detect_opera), 0777);
	detect_opera *opera = (detect_opera *)shmat(id, NULL, 0);

	if((int)opera == -1)
	{
		fprintf(stderr, "err, please check detector running or database name is right!\n");
		opera = NULL;
	}
	return opera;
}

static void release_opera(detect_opera *opera)
{
    if(opera)
        shmdt(opera);
}

int main(int argc, char *argv[])
{
    static struct option detect_option[] =
	{
        {"name", 1, 0, 'n'},
		{"add-ip", 1, 0, 'a'},
		{"del-ip", 1, 0, 'd'},
		{"clean-ip", 0, 0, 'c'},
		{"save-ip", 0, 0, 's'},
		{"print-ip", 0, 0, 'p'},
		{"top-ip", 1, 0, 't'},
		{"load-ip", 0, 0, 'l'},
		{"input", 1, 0, 'i'},
		{"output", 1, 0, 'o'},
		{"help", 0, 0, 'h'},
		{0, 0, 0, 0}
	};

	int ret = 0;
	int ch;
	int add_ip = 0;
	int del_ip = 0;
	int clean_ip = 0;
	int print_ip = 0;
	int top_ip = 0;
	int save_ip = 0;
	int load_ip = 0;
	unsigned char *name = NULL;
	unsigned char *in = NULL;
	unsigned char *out = NULL;
	unsigned char *val = NULL;
	FILE *output = NULL;
	FILE *input = NULL;
    detect_opera *opera;

	while((ch = getopt_long(argc, argv, "n:a:d:cspli:o:h", detect_option, NULL)) != -1)
	{
		switch(ch)
		{
            case 'n':
                name = optarg;
                break;
			case 'a':
				add_ip = 1;
				val = optarg;
				break;
			case 'd':
				del_ip = 1;
				val = optarg;
				break;
			case 'c':
				clean_ip = 1;
				break;
			case 's':
				save_ip = 1;
				break;
			case 'p':
				print_ip = 1;
				break;
            case 't':
                top_ip = 1;
                val = optarg;
                break;
            case 'l':
                load_ip = 1;
                break;
            case 'i':
                in = optarg;
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

	if(!name)
	{
        usage();
        goto done;
	}

	if(in)
        input = fopen(in, "rw+");

	if(out)
		output = fopen(out, "w+");
	else
		output = stderr;

	if((opera = get_opera(name)))
	{
        opera->arg = opera->result = 0;
        if(add_ip)
        {
            if(str_2_ip(val))
            {
                opera->arg = 1;
                opera->ip[0].ip = str_2_ip(val);
                opera->code = OPERA_ADD_IP;
            }
        }
        else if(del_ip)
        {
            if(str_2_ip(val))
            {
                opera->arg = 1;
                opera->ip[0].ip = str_2_ip(val);
                opera->code = OPERA_DEL_IP;
            }
        }
        else if(print_ip || save_ip)
        {
            opera->code = OPERA_GET_ALL;
        }
        else if(top_ip)
        {
            if(!memcmp(val, "ppsin", 5))
                opera->code = OPERA_GET_TOP_PPS_IN;
            if(!memcmp(val, "ppsout", 6))
                opera->code = OPERA_GET_TOP_PPS_OUT;
            if(!memcmp(val, "bpsin", 5))
                opera->code = OPERA_GET_TOP_BPS_IN;
            if(!memcmp(val, "bpsout", 6))
                opera->code = OPERA_GET_TOP_BPS_OUT;
            if(!memcmp(val, "session", 7))
                opera->code = OPERA_GET_TOP_NEW_SESSION;
            if(!memcmp(val, "http", 4))
                opera->code = OPERA_GET_TOP_NEW_HTTP;
            if(!memcmp(val, "icmpbps", 7))
                opera->code = OPERA_GET_TOP_ICMP_BPS;
            if(!memcmp(val, "httpbps", 7))
                opera->code = OPERA_GET_TOP_HTTP_BPS;
        }
        else if(load_ip)
        {
            if(input)
            {
                unsigned char ipaddr[32];
                while(!feof(input))
                {
                    fgets(ipaddr, sizeof(ipaddr), input);
                    ipaddr[strlen(ipaddr) - 1] = 0;
                    opera->ip[opera->arg++].ip = str_2_ip(ipaddr);
                }
                opera->code = OPERA_ADD_IP;
            }
        }
        while(opera->code) {usleep(100);}
        if(print_ip || save_ip)
        {
            int i;
            unsigned char ip_str[32];
            ip_data *data;
            for(i = 0; i < opera->result; i++)
            {
                data = &(opera->ip[i]);
                ip_2_str(data->ip, ip_str);
                if(save_ip)
                    fprintf(output, "%s\n", ip_str);
                else
                    fprintf(output, "%s %lu %lu %lu %lu %lu %lu %lu %lu %lu %lu %lu %lu\n",
                        ip_str, data->recv, data->send, data->inflow, data->outflow,
                        data->tcp_flow, data->udp_flow, data->icmp_flow, data->http_flow,
                        data->session_total, data->session_close, data->session_timeout, data->http_session);
            }
        }
        if(top_ip)
        {
            int i;
            unsigned char ip_str[32];
            top_data *data;
            for(i = 0; i < opera->result; i++)
            {
                data = &(opera->top[i]);
                ip_2_str(data->ip, ip_str);
                fprintf(output, "%s %lu\n", ip_str, data->val);
            }
        }
        ret = opera->result;
        release_opera(opera);
	}
done:
    if(in)
        fclose(input);
	if(out)
	{
		if(ret)
			fprintf(output, "succ");
		fclose(output);
	}
	return ret;
}

#include <getopt.h>
#include <efnet.h>
#include <efext.h>
#include <eftrack.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/shm.h>



static void usage()
{
	fprintf(stderr, "Add ip    : eftrack --add-ip xxx.xxx.xxx.xxx\n");
	fprintf(stderr, "Del ip    : eftrack --del-ip xxx.xxx.xxx.xxx\n");
}

static track_opera *get_opera()
{
    int *key = ftok("/etc/eftrack", (int)'a');
	int *id = shmget(key, sizeof(track_opera), 0777);
	track_opera *to = (track_opera *)shmat(id, NULL, 0);

	if((int)to == -1)
	{
		fprintf(stderr, "错误，程序没有正在运行!\n");
		to = NULL;
	}
	return to;
}

static void release_opera(track_opera *to)
{
    if(to)
        shmdt(to);
}

int main(int argc, char *argv[])
{
    static struct option track_option[] =
	{
		{"add-ip", 1, 0, 'a'},
		{"del-ip", 1, 0, 'd'},
		{"clean-ip", 0, 0, 'c'},
		{"save-ip", 0, 0, 's'},
		{"print-ip", 0, 0, 'p'},
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
	int save_ip = 0;
	int load_ip = 0;
	unsigned char *in = NULL;
	unsigned char *out = NULL;
	unsigned char *val = NULL;
	FILE *output = NULL;
	FILE *input = NULL;
    track_opera *to;

	while((ch = getopt_long(argc, argv, "a:d:cspli:o:h", track_option, NULL)) != -1)
	{
		switch(ch)
		{
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

	if(in)
        input = fopen(in, "rw+");

	if(out)
		output = fopen(out, "w+");
	else
		output = stderr;

	if((to = get_opera()))
	{
        to->arg = to->result = 0;
        if(add_ip)
        {
            if(str_2_ip(val))
            {
                to->arg = 1;
                to->ip[0].ip = str_2_ip(val);
                to->code = OPERA_ADD_IP;
            }
        }
        else if(del_ip)
        {
            if(str_2_ip(val))
            {
                to->arg = 1;
                to->ip[0].ip = str_2_ip(val);
                to->code = OPERA_DEL_IP;
            }
        }
        else if(print_ip || save_ip)
        {
            to->code = OPERA_GET_ALL;
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
                    to->ip[to->arg++].ip = str_2_ip(ipaddr);
                }
                to->code = OPERA_ADD_IP;
            }
        }
        while(to->code) {usleep(100);}
        if(print_ip || save_ip)
        {
            int i;
            unsigned char ip_str[32];
            ip_data *data;
            for(i = 0; i < to->result; i++)
            {
                data = &(to->ip[i]);
                ip_2_str(data->ip, ip_str);
                if(save_ip)
                    fprintf(output, "%s\n", ip_str);
                else
                    fprintf(output, "%s %lu %lu %lu %lu\n",
                        ip_str, data->recv, data->send, data->inflow, data->outflow);
            }
        }
        ret = to->result;
        release_opera(to);
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

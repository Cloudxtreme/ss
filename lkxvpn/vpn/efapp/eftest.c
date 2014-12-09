#include <stdio.h>
#include <signal.h>	/* signal */
#include <efio.h>
#include <efnet.h>


static unsigned int run_stats = 0;
static unsigned long long pkg_total = 0, flow_total = 0;
static int fd[10] = {0};
static int fd_total;
static int read_cpu = -1;

static int control()
{
    unsigned long long pkg_prev = 0, flow_prev = 0;
    while(run_stats)
    {
        unsigned long long pps = pkg_total - pkg_prev;
        unsigned long long bps = flow_total - flow_prev;
        fprintf(stderr, "pps : %llu, bps : %llu\n", pps, bps * 8);
        pkg_prev += pps;
        flow_prev += bps;
        sleep(1);
    }
}


static int handle(int fd, ef_slot *slot, int num)
{
    int i;
    #if 0
    ef_slot slot[1024];
    if(read_cpu >= 0)
	{
        unsigned long mask = 1;
		mask = mask << read_cpu;
		sched_setaffinity(0, sizeof(mask), &mask);
	}
	while(run_stats)
	{
        int ret;
        int i;
        unsigned long long reads, flow = 0;
		ret = efio_flush(fdr, EF_FLUSH_READ, 2);
        if(!(ret & EF_FLUSH_READ))
        {
            usleep(0);
            continue;
        }
        reads = efio_read(fdr, slot, 1024);
        //for(i = 0; i < reads; i++)
        //{
            //flow += slot[i].len;
        //}
        pkg_total += reads;
        flow_total += flow;
	}
    #endif;
    pkg_total += num;
    for(i = 0; i < num; i++)
    {
        flow_total += slot[i].plen;
    }
}

static int read_thread()
{
    efio_mbdg_start(handle, 2, fd[0], fd[1], fd[2], fd[3]);
}

/* control-C handler */
static void
sigint_h(int sig)
{
	(void)sig;	/* UNUSED */

	run_stats = 0;
	efio_mbdg_stop();
}

int main(int argc, char *argv[])
{
    int i;
    pthread_t read_t;
    pthread_t control_t;
    fd_total = argc - 1;
    for(i = 0; i < argc; i++)
        fd[i] = efio_init(argv[i + 1], EF_CAPTURE_NETMAP, EF_ENABLE_READ, 1);
    signal(SIGINT, sigint_h);
    signal(SIGTERM, sigint_h);
    signal(SIGKILL, sigint_h);
    run_stats = 1;
    fprintf(stderr, "read begin!\n");
    pthread_create(&read_t, NULL, read_thread, NULL);
    pthread_create(&control_t, NULL, control, NULL);
    pthread_join(control_t, NULL);
    fprintf(stderr, "read end!\n");
    return 0;
}

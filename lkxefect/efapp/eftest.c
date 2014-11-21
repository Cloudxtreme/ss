#include <stdio.h>
#include <signal.h>	/* signal */
#include <efio.h>
#include <efnet.h>


static unsigned int run_stats = 0;
static unsigned long long pkg_total = 0, flow_total = 0;
static int fdr = 0;
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


static int read()
{
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
        for(i = 0; i < reads; i++)
        {
            flow += slot[i].len;
        }
        pkg_total += reads;
        flow_total += flow;
	}

}

/* control-C handler */
static void
sigint_h(int sig)
{
	(void)sig;	/* UNUSED */

	run_stats = 0;
}

int main(int argc, char *argv[])
{
    pthread_t read_t;
    pthread_t control_t;
    fdr = efio_init(argv[1], EF_CAPTURE_NETMAP, EF_ENABLE_READ, 1);
    read_cpu = atoi(argv[2]);
    signal(SIGINT, sigint_h);
    signal(SIGTERM, sigint_h);
    signal(SIGKILL, sigint_h);
    run_stats = 1;
    fprintf(stderr, "read begin!\n");
    pthread_create(&read_t, NULL, read, NULL);
    pthread_create(&control_t, NULL, control, NULL);
    pthread_join(read_t, NULL);
    pthread_join(control_t, NULL);
    fprintf(stderr, "read end!\n");
    return 0;
}

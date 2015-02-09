#include <stdio.h>
#include <math.h>

int main(int argc, char *argv[])
{
	int begin = atoi(argv[1]);

	int i = 0;
	for(i = 0; i < 16; i++)
	{
		fprintf(stderr, "echo %u > /proc/irq/%d/smp_affinity\n", atoi(argv[2]), begin+i);
	}
}

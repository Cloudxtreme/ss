#include <stdio.h>

int main()
{
	unsigned int server;
    
    ((unsigned char *)&server)[0] = 192;
    ((unsigned char *)&server)[1] = 168;
    ((unsigned char *)&server)[2] = 22;
    ((unsigned char *)&server)[3] = 166;
    
    printf("sip:%u\n",server);
    return 0;
}

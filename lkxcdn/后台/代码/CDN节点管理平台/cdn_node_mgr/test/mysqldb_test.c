#include <stdio.h>

static int  d_ex(char *search, char *domain)
{

  char mdot[]=".";
  char *array[142];
  int loop;

  array[0]=strtok(search,mdot);


   if(array[0]==NULL)
   {

           return 1;

   }

  for(loop=1;loop<142;loop++)
   {
           array[loop]=strtok(NULL,mdot);
           if(array[loop]==NULL)
                   break;
   }

   if(loop<2) {
	return 1;
   }

   snprintf(domain,255,"%s.%s",array[loop-2],array[loop-1]);
   
   return 0;
}

int main(int argc, char *argv[])
{
	char domain[255];
	if(d_ex(argv[1],domain) != 0) {
					printf("get domain error!\n");
                    return (0);
            }
	printf("get domain is:%s\n", domain);
}


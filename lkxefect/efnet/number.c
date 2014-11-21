#include <efnet.h>

static char num_str[1000000][8] = {0};
static char num_len[1000000] = {0};
static char pat_str[1000000][8] = {0};


int num_2_str(unsigned long long num, unsigned char *str)
{
    int number[4] = {0};
    int len = 0;
    int numlen = 0;
    int pos = 0;
    if(!str)
        return 0;
    if(num < 1000000)
    {
        memcpy(str, num_str[num], num_len[num]);
        return num_len[num];
    }
    do
    {
        number[len++] = num % 1000000;
        num /= 1000000;
    }while(num);
    pos = num_len[number[len - 1]];
    numlen += pos;
    memcpy(str, num_str[number[len - 1]], pos);
    len--;
    while(len--)
    {
        memcpy(str+pos, pat_str[number[len]], 6);
        pos += 6;
        numlen += 6;
    }
    *(str+pos) = 0;
    return numlen;
}

int str_2_num(unsigned char *str, unsigned long *num)
{
}

int num_init()
{
    int i;
    num_str[0][0] = '0';
    pat_str[0][0] = '0';
    for(i = 0; i < 1000000; i++)
    {
            int ip = i;
            int jj = 0;
            int kk = 0;
            int nn = 1000000;
            while(nn)
            {
                    if((ip / nn) || jj)
                        num_str[i][jj++] = '0' + ip / nn;
                    if(ip / nn)
                        pat_str[i][kk++] = '0' + ip / nn;
                    else if(nn < 1000000)
                        pat_str[i][kk++] = '0';
                    ip -= ip / nn * nn;
                    nn /= 10;
            }
            num_len[i] = strlen(num_str[i]);
    }
}

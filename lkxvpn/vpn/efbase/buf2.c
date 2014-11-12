#include <efnet.h>


#define BUF_USE_FLAG       0x1234ABCD


typedef struct _buf_stat
{
    struct _buf_stat *prev, *next;
    unsigned int len;
    unsigned int flag;
}buf_stat;




void *buf_init(unsigned long size)
{
    void *buf = NULL;
    buf_stat bs = {0};

    buf = (void *)malloc(size);
    if(!buf)
        goto done;
    bs.len = size;
    memcpy(buf, &bs, sizeof(buf_stat));

done:
    return buf;
}

int buf_tini(void *buf)
{
    if(buf)
    {
        free(buf);
        return 1;
    }
    return 0;
}

void *put_to_buf(void *buf, void *c, unsigned int len)
{
    void *ret = NULL;
    buf_stat *bs;
    buf_stat *bs_new;
    unsigned int need = len + sizeof(buf_stat);
    if(!buf)
        return NULL;
    bs = (buf_stat *)buf;
    while(bs && (bs->flag == BUF_USE_FLAG || bs->len < need))
        bs = bs->next;
    if(bs)
    {
        buf_stat *prev = bs->prev;
        buf_stat *next = bs->next;
        bs->flag = BUF_USE_FLAG;
        if(bs->len > need)
        {
            bs_new = (buf_stat *)((void *)bs + need);
            bs_new->prev = prev;
            bs_new->next = next;
            prev->next = bs_new;
            next->prev = bs_new;
            bs->next = bs_new;
            bs_new->len = bs->len - need;
            bs->len = need;
            bs_new->flag = 0;
        }
        else
        {
            prev->next = next;
            next->prev = prev;
        }
        ret = (void *)bs + sizeof(buf_stat);
        memcpy(ret, c, len);
    }
    return ret;
}

int del_in_buf(void *c)
{
    buf_stat *bs;
    if(c && (c > sizeof(buf_stat)))
    {
        bs = (buf_stat *)(c - sizeof(buf_stat));
        if(bs->flag == BUF_USE_FLAG)
        {
            buf_stat *prev = bs->prev;
            buf_stat *next = bs->next;
            while(prev && prev->prev && prev->flag == BUF_USE_FLAG)
                prev = prev->prev;
            while(next && next->next && next->flag == BUF_USE_FLAG)
                next = next->next;
            if(next)
            {
                if((void *)bs + bs->len == (void *)next)
                {
                    bs->len += next->len;
                    bs->next = next->next;
                    next->prev = next->next = NULL;
                    next->len = next->flag = 0;
                }
                else
                {
                    bs->next = next;
                    next->prev = bs;
                }
            }
            if(prev)
            {
                if((void *)prev + prev->len == (void *)bs)
                {
                    prev->len += bs->len;
                    prev->next = bs->next;
                    bs->prev = bs->next = NULL;
                    bs->len = bs->flag = 0;
                }
                else
                {
                    bs->prev = prev;
                    prev->next = bs;
                }
            }
        }
    }
    return 0;
}

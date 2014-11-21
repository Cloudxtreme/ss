/// buf api
#define PIECE_OF_BUF_SIZE           100
#define MAX_PIECE                   10000000
#define MAX_BLOCK_SIZE              20
#define BLOCK_FLAG                  0x1234abcd
typedef struct _buf_t
{
    void *buf;
    void *piece[MAX_PIECE];
    unsigned long buf_size;
    unsigned int piece_total, piece_alive, cur, rec;
}buf_t;
typedef struct _buf_block
{
    buf_t *bt;
    unsigned int flag;
    unsigned char buf_total;
    unsigned int buf_len;
    void *buf_piece[MAX_BLOCK_SIZE];
}buf_block;

buf_t *buf_t_init(unsigned long size);
int buf_t_tini(buf_t *b);
int buf_get_block(buf_t *b, buf_block *block);
int buf_put_in_block(buf_block *block, void *c, unsigned int len);
int buf_copy_from_block(buf_block *block, void *c, unsigned int len);
int buf_release_block(buf_block *block);


buf_t *buf_t_init(unsigned long size)
{
    unsigned int i;
    buf_t *b = NULL;

    b = (buf_t *)malloc(sizeof(buf_t));
    if(!b)
        goto err;
    memset(b, 0, sizeof(buf_t));
    b->buf = (void *)malloc(size);
    if(!(b->buf))
        goto err;
    b->buf_size = size;
    b->piece_total = size / PIECE_OF_BUF_SIZE;
    if(b->piece_total > MAX_PIECE)
        b->piece_total = MAX_PIECE;
    for(i = 0; i < b->piece_total; i++)
        b->piece[i] = (void *)(b->buf + i * PIECE_OF_BUF_SIZE);

err:
    if(b)
    {
        if(b->buf)
            free(b->buf);
        free(b);
        b = NULL;
    }
done:
    return b;
}

int buf_t_tini(buf_t *b)
{
    if(b)
    {
        if(b->buf)
            free(b->buf);
        free(b);
        return 1;
    }
    return 0;
}

int buf_get_block(buf_t *b, buf_block *block)
{
    if(!b || !block)
        return 0;
    if(block->flag == BLOCK_FLAG)
        return 0;
    memset(block, 0, sizeof(buf_block));
    block->bt = b;
    block->flag = BLOCK_FLAG;
    return 1;
}

int buf_put_in_block(buf_block *block, void *c, unsigned int len)
{
    buf_t *b;
    unsigned int i;
    unsigned int need_piece = 0, need_more_len = 0, last_piece_use = 0, last_piece_left = 0;
    unsigned int put_len = 0;

    if(!block || !c || !len)
        return 0;
    if(block->flag != BLOCK_FLAG)
        return 0;
    if((block->buf_len + len) > (MAX_BLOCK_SIZE * PIECE_OF_BUF_SIZE))
        return 0;
    if(!block->bt)
        return 0;

    b = block->bt;
    last_piece_use = block->buf_len % PIECE_OF_BUF_SIZE;
    if(last_piece_use)
        last_piece_left = PIECE_OF_BUF_SIZE - last_piece_use;
    need_more_len = (len > last_piece_left) ? (len - last_piece_left) : 0;
    need_piece = need_more_len / PIECE_OF_BUF_SIZE;
    if(need_piece * PIECE_OF_BUF_SIZE < need_more_len)
        need_piece++;
    if(b->piece_alive < need_piece)
        need_piece = b->piece_alive;

    if(last_piece_left)
    {
        memcpy(block->buf_piece[block->buf_total - 1] + last_piece_use, c, len - need_more_len);
        c += (len - need_more_len);
        put_len += (len - need_more_len);
    }
    for(i = 0; i < need_piece; i++)
    {
        void *buf;
        buf = block->buf_piece[block->buf_total + i] = b->piece[b->cur++];
        if(need_more_len >= PIECE_OF_BUF_SIZE)
        {
            memcpy(buf, c, PIECE_OF_BUF_SIZE);
            need_more_len -= PIECE_OF_BUF_SIZE;
            c += PIECE_OF_BUF_SIZE;
            put_len += PIECE_OF_BUF_SIZE;
        }
        else
        {
            memcpy(buf, c, need_more_len);
            put_len += need_more_len;
            need_more_len = 0;
        }
    }
    block->buf_len += len;
    block->buf_total += need_piece;
    b->piece_alive -= need_piece;
    return put_len;
}

int buf_copy_from_block(buf_block *block, void *c, unsigned int len)
{
    unsigned int i;
    unsigned copy_len = len;
    if(!block || !c)
        return 0;
    for(i = 0; i < block->buf_total; i++)
    {
        void *buf = block->buf_piece[i];
        if(len > PIECE_OF_BUF_SIZE)
        {
            memcpy(c + i * PIECE_OF_BUF_SIZE, buf, PIECE_OF_BUF_SIZE);
            len -= PIECE_OF_BUF_SIZE;
        }
        else
        {
            memcpy(c + i * PIECE_OF_BUF_SIZE, buf, len);
            len = 0;
        }
    }
    return copy_len - len;
}

int buf_release_block(buf_block *block)
{
    buf_t *b = NULL;
    unsigned int i = 0;
    if(!block || (block->flag != BLOCK_FLAG))
        return 0;
    b = block->bt;
    if(!b)
        return 0;
    for(i = 0; i < block->buf_total; i++)
        b->piece[b->rec++] = block->buf_piece[i];
    b->piece_alive += block->buf_total;
    memset(block, 0, sizeof(buf_block));
    return 1;
}

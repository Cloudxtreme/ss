#include <conf.h>
#include <stdio.h>
#include <stdlib.h>


#define MAX_CONF_BUF	(1024*1024*16)
#define MIN_CONF_BUF	(1024*1024)
#define MAX_LINE_LEN	2048
#define MAX_KEY_LEN		1024
#define MAX_VAL_LEN		1024
#define MAX_VAL_NUM		16


#define MAX_FILE_NAME_LEN	64
#define MAX_TOKEN_LEN		32

struct _CONF_DES
{
	char conf_file[MAX_FILE_NAME_LEN];
	char *conf_buf;
	char *conf_tok[MAX_TOKEN_LEN];
	int  tok_flg;	//0:one  1:all
	int  buf_size;
	int  keys;
};


int conf_init(CONF_DES **_conf_des)
{
	CONF_DES *conf_des = NULL;
	int ret = 0;
	unsigned int buf_size;

	if(_conf_des == NULL)
	{
		goto err;
	}

	conf_des = (CONF_DES *)malloc(sizeof(CONF_DES));
	if(conf_des == NULL)
	{
		goto err;
	}
	memset(conf_des, 0, sizeof(CONF_DES));

	buf_size = MAX_CONF_BUF;
	conf_des->conf_buf = (char *)malloc(buf_size);
	if(conf_des->conf_buf == NULL)
	{
		goto err;
	}
	conf_des->buf_size = buf_size;


	*_conf_des = conf_des;

	ret = 1;
	return ret;


err:
	*_conf_des = NULL;
	if(conf_des)
	{
		if(conf_des->conf_buf)
		{
			free(conf_des->conf_buf);
		}
		free(conf_des);
	}
	return ret;
}

int conf_uninit(CONF_DES **_conf_des)
{
	CONF_DES *conf_des;

	if(_conf_des == NULL)
		return 0;

	conf_des = *_conf_des;

	if(conf_des)
	{
		if(conf_des->conf_buf)
		{
			free(conf_des->conf_buf);
		}
		free(conf_des);
		*_conf_des = NULL;
		return 1;
	}
	else
	{
		return 0;
	}
}

int conf_keycount(CONF_DES *conf_des)
{
	if(conf_des == NULL)
		return 0;
	return conf_des->keys;
}

int conf_getkey(CONF_DES *conf_des, char *key, int get_key_len, int get_key_num)
{
	char *conf_buf;
	int key_i;
	char key_s[MAX_KEY_LEN];
	int key_len;
	int val_len;
	int val_num;
	int ret;
	int all_val_len;

	if((conf_des == NULL) || (key == NULL))
		return 0;
	if((get_key_len == 0) || (get_key_num <= 0))
		return 0;
	if(get_key_num > conf_des->keys)
		return 0;

	conf_buf = conf_des->conf_buf;
	if((conf_buf == NULL))
	{
		return 0;
	}
	key_i = 0;
	ret = 0;

	while(key_i < conf_des->keys)
	{
		memset(key_s, 0, sizeof(key_s));

		key_len = *(int *)conf_buf;
		conf_buf += sizeof(int);
		memcpy(key_s, conf_buf, key_len);
		conf_buf += key_len;
		all_val_len = *(int *)conf_buf;
		conf_buf += sizeof(int);
		val_num = *(int *)conf_buf;
		conf_buf += sizeof(int);

		conf_buf += all_val_len + (val_num * sizeof(int));

		if(key_i + 1 == get_key_num)
		{
			ret = 1;
			break;
		}

		key_i++;

	}

	memset(key, 0, get_key_len);
	strncpy(key, key_s, get_key_len);
	return ret;
}

int conf_ifkey(CONF_DES *conf_des, const char *key)
{
	char *conf_buf;
	int key_i;
	char key_c[MAX_KEY_LEN];
	char key_s[MAX_KEY_LEN];
	int key_len;
	int val_num;
	int i;
	int ret;
	int all_val_len;

	if((conf_des == NULL) || (key == NULL))
	{
		return 0;
	}

	conf_buf = conf_des->conf_buf;
	if((conf_buf == NULL) || (strlen(key)==0))
	{
		return 0;
	}

	key_len = strlen(key);
	memset(key_c, 0, sizeof(key_c));
	for(i = 0; i < key_len; i++)
	{
		key_c[i] = tolower(key[i]);
	}

	key_i = 0;
	ret = 0;
	while(key_i < conf_des->keys)
	{
		memset(key_s, 0, sizeof(key_s));

		key_len = *(int *)conf_buf;
		conf_buf += sizeof(int);
		memcpy(key_s, conf_buf, key_len);
		conf_buf += key_len;
		all_val_len = *(int *)conf_buf;
		conf_buf += sizeof(int);
		val_num = *(int *)conf_buf;
		conf_buf += sizeof(int);

		if(!strcmp(key_c, key_s))
		{
			ret = 1;
			break;
		}

		conf_buf += all_val_len + (val_num * sizeof(int));
		key_i++;

	}

	return ret;
}

int conf_ifval(CONF_DES *conf_des, const char *val, int get_val_num)
{
	char *conf_buf;
	int key_i;
	char key_s[MAX_KEY_LEN];
	char val_s[MAX_VAL_LEN];
	int key_len;
	int val_len;
	int val_num;
	int i;
	int ret;
	int all_val_len;

	if((conf_des == NULL))
	{
		return 0;
	}

	conf_buf = conf_des->conf_buf;
	if((conf_buf == NULL) || (get_val_num <= 0))
	{
		return 0;
	}

	key_i = 0;
	ret = 0;
	while(key_i < conf_des->keys)
	{
		memset(key_s, 0, sizeof(key_s));

		key_len = *(int *)conf_buf;
		conf_buf += sizeof(int);
		memcpy(key_s, conf_buf, key_len);
		conf_buf += key_len;
		all_val_len = *(int *)conf_buf;
		conf_buf += sizeof(int);
		val_num = *(int *)conf_buf;
		conf_buf += sizeof(int);

		if(get_val_num > val_num)
		{
			conf_buf += all_val_len + (val_num * sizeof(int));
			key_i++;
			continue;
		}

		for(i = 0; i < val_num; i++)
		{
			val_len = *(int *)conf_buf;
			conf_buf += sizeof(int);

			if(get_val_num == i+1)
			{
				memset(val_s, 0, sizeof(val_s));
				memcpy(val_s, conf_buf, val_len);
				if(val && !strcmp(val, val_s))
					ret = 1;
				if(!val && !strlen(val_s))
					ret = 1;
				if(val && !strlen(val) && !strlen(val_s))
					ret = 1;
			}
			conf_buf += val_len;
		}

		key_i++;

		if(ret)
			break;
	}
	return ret;
}

int conf_getval(CONF_DES *conf_des, const char *key, char *val, int get_val_len, int get_val_num)
{
	char *conf_buf;
	int key_i;
	char key_c[MAX_KEY_LEN];
	char key_s[MAX_KEY_LEN];
	char val_s[MAX_VAL_LEN];
	int key_len;
	int val_len;
	int val_num;
	int i;
	int ret;
	int all_val_len;

	if((conf_des == NULL) || (key == NULL))
	{
		return 0;
	}

	if((val == NULL) || (get_val_len <= 0))
	{
		return 0;
	}

	conf_buf = conf_des->conf_buf;
	if((conf_buf == NULL) || (strlen(key)==0) || (get_val_num <= 0))
	{
		memset(val, 0, get_val_len);
		return 0;
	}

	key_len = strlen(key);
	memset(key_c, 0, sizeof(key_c));
	for(i = 0; i < key_len; i++)
	{
		key_c[i] = tolower(key[i]);
	}

	key_i = 0;
	ret = 0;
	memset(val, 0, get_val_len);
	while(key_i < conf_des->keys)
	{
		memset(key_s, 0, sizeof(key_s));

		key_len = *(int *)conf_buf;
		conf_buf += sizeof(int);
		memcpy(key_s, conf_buf, key_len);
		conf_buf += key_len;
		all_val_len = *(int *)conf_buf;
		conf_buf += sizeof(int);
		val_num = *(int *)conf_buf;
		conf_buf += sizeof(int);

		if(strcmp(key_c, key_s))
		{
			conf_buf += all_val_len + (val_num * sizeof(int));
			key_i++;
			continue;
		}

		if(get_val_num > val_num)
		{
			//return ret;
			get_val_num -= val_num;
			conf_buf += all_val_len + (val_num * sizeof(int));
			key_i++;
			continue;
		}

		for(i = 0; i < val_num; i++)
		{
			val_len = *(int *)conf_buf;
			conf_buf += sizeof(int);

			if(get_val_num == i+1)
			{
				if(val_len > get_val_len)
					val_len = get_val_len;
				memcpy(val, conf_buf, val_len);
				ret = 1;
				break;
			}
			else
			{
				conf_buf += val_len;
			}
		}

		break;
	}
	return ret;
}

static char * conf_strtok(char *str, char *tok, int tok_flg)
{
	char *p;
	if(tok == NULL)
		return NULL;
	if(str == NULL)
		return NULL;
	if(!strlen(str))
		return NULL;
	if(!strlen(tok))
		return NULL;

	if((p = strstr(str, tok)) != NULL)
	{
		memset(p, 0, strlen(tok));
		p += strlen(tok);
		if(tok_flg)
		{
			while(p == strstr(p, tok))
			{
				memset(p, 0, strlen(tok));
				p += strlen(tok);
			}
		}
	}

	return p;
}

static char *conf_str_init(char *src_str)
{
	char *p;
	int str_len;

	if(!src_str)
		return src_str;
	str_len = strlen(src_str);
	if(!str_len)
		return src_str;

	p = src_str;
	while((p) && (*p == ' '))
	{
		*p = '\0';
		p++;
	}

	src_str = p;
	str_len = strlen(src_str);
	if(!str_len)
		return src_str;

	p = &src_str[str_len - 1];
	while((p) && (*p == ' '))
	{
		*p = '\0';
		p--;
	}

	return src_str;
}

static int conf_read(CONF_DES *conf_des)
{
	FILE *fp;
	char *conf_buf;
	char line_buf[MAX_LINE_LEN] = {0};
	int  line_len = 0;
	char *p, *q, *key, *val[MAX_VAL_NUM];
	int  i, key_len, val_len[MAX_VAL_NUM], record_len, val_num, all_val_len;

	if(conf_des == NULL)
		goto err;

	conf_des->keys = 0;

	fp = fopen(conf_des->conf_file, "r");
	conf_buf = conf_des->conf_buf;

	if((fp == NULL) || (conf_buf == NULL))
	{
		return 0;
	}

	fseek(fp, 0, SEEK_SET);

	while(fgets(line_buf, sizeof(line_buf), fp) != NULL)
	{
		q = conf_str_init(line_buf);
		if(q[0] == '\n')
		{
			continue;
		}
		p = strchr(q, '\n');
		if(p)
		{
			*p = '\0';
		}
		p = strchr(q, '\r');
		if(p)
		{
			*p = '\0';
		}

		if((p = strchr(q, '#')) != NULL)
		{
			*p = '\0';
		}
		if((p = strchr(q, ';')) != NULL)
		{
			*p = '\0';
		}
		line_len = strlen(q);
		if(!line_len)
		{
			continue;
		}


		p = q;
		q = conf_strtok(q, conf_des->conf_tok, conf_des->tok_flg);

		val_num = 0;
		record_len = sizeof(int);
		all_val_len = 0;
		while((p) && (val_num <= MAX_VAL_NUM))
		{
			p = conf_str_init(p);
			if(val_num == 0)
			{
				key = p;
				key_len = strlen(p);
				if(!key_len)
				{
					break;
				}
				record_len += key_len;
				//puts(key);
			}
			else
			{
				val[val_num-1] = p;
				val_len[val_num-1] = strlen(p);
				record_len += val_len[val_num-1];
				all_val_len += val_len[val_num-1];
				//puts(val[val_num-1]);
			}
			val_num++;
			p = q;
			q = conf_strtok(q, conf_des->conf_tok, conf_des->tok_flg);
		}
		val_num--;

		if(!key_len)
		{
			continue;
		}

		for(i = 0; i < key_len; i++)
		{
			key[i] = tolower(key[i]);
		}



		record_len += (val_num+3) * sizeof(int);
		if((conf_des->conf_buf + conf_des->buf_size - conf_buf) < record_len)
		{
			goto err;
		}


		memcpy(conf_buf, &key_len, sizeof(int));
		conf_buf += sizeof(int);
		memcpy(conf_buf, key, key_len);
		conf_buf += key_len;
		memcpy(conf_buf, &all_val_len, sizeof(int));
		conf_buf += sizeof(int);
		memcpy(conf_buf, &val_num, sizeof(int));
		conf_buf += sizeof(int);
		for(i = 0; i < val_num; i++)
		{
			memcpy(conf_buf, &val_len[i], sizeof(int));
			conf_buf += sizeof(int);
			memcpy(conf_buf, val[i], val_len[i]);
			conf_buf += val_len[i];
		}
		conf_des->keys++;
		memset(line_buf, 0, sizeof(line_buf));
	}

	return 1;

err:

	return 0;
}



int conf_read_file(CONF_DES *conf_des, const char *file_name, const char *token, int token_flag)
{
	memset(conf_des->conf_file, 0, MAX_FILE_NAME_LEN);
	memset(conf_des->conf_tok, 0, MAX_TOKEN_LEN);
	memset(conf_des->conf_buf, 0, MAX_CONF_BUF);

	strncpy(conf_des->conf_file, file_name, MAX_FILE_NAME_LEN);
	strncpy(conf_des->conf_tok, token, MAX_TOKEN_LEN);
	conf_des->tok_flg = token_flag;
	return conf_read(conf_des);
}



int conf_print_all(CONF_DES *conf_des)
{
	char *conf_buf;
	int key_i;
	char key_s[MAX_KEY_LEN];
	char val_s[MAX_VAL_LEN];
	int key_len;
	int val_len;
	int val_num;
	int i;
	int all_val_len;

	if(conf_des == NULL)
	{
		return 0;
	}

	conf_buf = conf_des->conf_buf;
	if(conf_buf == NULL)
	{
		return 0;
	}
	key_i = 0;
	while(key_i < conf_des->keys)
	{
		memset(key_s, 0, sizeof(key_s));
		memset(val_s, 0, sizeof(val_s));


		key_len = *(int *)conf_buf;
		conf_buf += sizeof(int);
		memcpy(key_s, conf_buf, key_len);
		conf_buf += key_len;
		all_val_len = *(int *)conf_buf;
		conf_buf += sizeof(int);
		val_num = *(int *)conf_buf;
		conf_buf += sizeof(int);
		fprintf(stderr, "%s", key_s);
		if(val_num)
		{
			fprintf(stderr, "=");
		}

		for(i = 0; i < val_num; i++)
		{
			memset(val_s, 0, sizeof(val_s));
			val_len = *(int *)conf_buf;
			conf_buf += sizeof(int);
			memcpy(val_s, conf_buf, val_len);
			conf_buf += val_len;
			fprintf(stderr, "%s", val_s);
			if(i < val_num-1)
			{
				fprintf(stderr, ",");
			}
		}
		fprintf(stderr, "\n");

		key_i ++;
	}
}


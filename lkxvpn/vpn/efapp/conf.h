#ifndef CONF
#define CONF


typedef struct _CONF_DES CONF_DES;


int conf_init(CONF_DES **conf_des);

int conf_uninit(CONF_DES **conf_des);

int conf_keycount(CONF_DES *conf_des);

int conf_getkey(CONF_DES *conf_des, char *key, int get_key_len, int get_key_num);

int conf_ifkey(CONF_DES *conf_des, const char *key);

int conf_ifval(CONF_DES *conf_des, const char *val, int get_val_num);

int conf_getval(CONF_DES *conf_des, const char *key, char *val, int get_val_len, int get_val_num);

int conf_read_file(CONF_DES *conf_des, const char *file_name, const char *token, int token_flag);

#endif

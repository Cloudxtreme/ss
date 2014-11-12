#pragma once
#include "list.h"

typedef struct _hash_node_t
{
	char *key;
	void *value;

}hash_node_t;

typedef struct _hash_table_t
{
	int size;
	int used;
	list_t **buckets;

}hash_table_t;

hash_table_t* create_hash_table(int size);
void destroy_hash_table(hash_table_t *hash_table);
void hash_table_insert(hash_table_t *hash_table, char *key, void *value);
void* hash_table_find(hash_table_t *hash_table, char *key);
void hash_table_remove(hash_table_t *hash_table, char *key);
void hash_table_clear(hash_table_t *hash_table);

#define HASH_TABLE_FOR_EACH_BEGIN(hash_node, hash_table) { \
	int _i_; list_t *_bucket_; list_node_t *_node_; \
	for( _i_ = 0; _i_ < hash_table->size; _i_++ ) { \
		_bucket_ = hash_table->buckets[_i_]; \
		if( _bucket_ ) { \
			LIST_FOR_EACH(_node_, _bucket_) { \
				hash_node = _node_->data; 

#define HASH_TABLE_FOR_EACH_END \
			} \
		} \
	} \
}


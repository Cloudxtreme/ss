#include "hash.h"
#include <stdlib.h>
#include <string.h>

static unsigned int table_size[] = {
	7, 13, 31, 61, 127, 251,
	509, 1021, 2039, 4093, 8191, 16381,
	32749, 65521, 131071, 262143, 524287, 1048575,
	2097151, 4194303, 8388607, 16777211, 33554431, 67108863,
	134217727, 268435455, 536870911, 1073741823, 2147483647, 0
};

#define mix(a,b,c) \
{ \
	a -= b; a -= c; a ^= (c>>13); \
	b -= c; b -= a; b ^= (a<<8); \
	c -= a; c -= b; c ^= (b>>13); \
	a -= b; a -= c; a ^= (c>>12); \
	b -= c; b -= a; b ^= (a<<16); \
	c -= a; c -= b; c ^= (b>>5); \
	a -= b; a -= c; a ^= (c>>3); \
	b -= c; b -= a; b ^= (a<<10); \
	c -= a; c -= b; c ^= (b>>15); \
}

unsigned int make_hash( k, length, initval )
register unsigned char *k; /* the key */
register unsigned int length; /* the length of the key */
register unsigned int initval; /* the previous hash, or an arbitrary value */
{
	register unsigned int a,b,c,len;

	/* Set up the internal state */
	len = length;
	a = b = 0x9e3779b9; /* the golden ratio; an arbitrary value */
	c = initval; /* the previous hash value */

	/*---------------------------------------- handle most of the key */
	while (len >= 12)
	{
		a += (k[0] +((unsigned int)k[1]<<8) +((unsigned int)k[2]<<16) +((unsigned int)k[3]<<24));
		b += (k[4] +((unsigned int)k[5]<<8) +((unsigned int)k[6]<<16) +((unsigned int)k[7]<<24));
		c += (k[8] +((unsigned int)k[9]<<8) +((unsigned int)k[10]<<16)+((unsigned int)k[11]<<24));
		mix(a,b,c);
		k += 12; len -= 12;
	}

	/*------------------------------------- handle the last 11 bytes */
	c += length;
	switch(len) /* all the case statements fall through */
	{
	case 11: c+=((unsigned int)k[10]<<24);
	case 10: c+=((unsigned int)k[9]<<16);
	case 9 : c+=((unsigned int)k[8]<<8);
		/* the first byte of c is reserved for the length */
	case 8 : b+=((unsigned int)k[7]<<24);
	case 7 : b+=((unsigned int)k[6]<<16);
	case 6 : b+=((unsigned int)k[5]<<8);
	case 5 : b+=k[4];
	case 4 : a+=((unsigned int)k[3]<<24);
	case 3 : a+=((unsigned int)k[2]<<16);
	case 2 : a+=((unsigned int)k[1]<<8);
	case 1 : a+=k[0];
		/* case 0: nothing left to add */
	}
	mix(a,b,c);
	/*-------------------------------------------- report the result */
	return c;
}

hash_table_t* create_hash_table(int size)
{
	hash_table_t *hash_table = malloc(sizeof(hash_table_t));
	hash_table->size = hash_table->used = 0;

	while( table_size[hash_table->size] < size ) {
		hash_table->size++;
		if( table_size[hash_table->size] == 0 ) {
			hash_table->size--;
			break;
		}
	}

	hash_table->buckets = malloc(sizeof(list_t*) * table_size[hash_table->size]);
	memset(hash_table->buckets, 0, sizeof(list_t*) * table_size[hash_table->size]);

	return hash_table;
}

void destroy_hash_table(hash_table_t *hash_table)
{
	hash_table_clear(hash_table);
	free(hash_table->buckets);
	free(hash_table);
}

void hash_table_insert(hash_table_t *hash_table, char *key, void *value)
{
	unsigned int hash, index;
	int key_size = strlen(key);
	list_t *bucket;
	hash_node_t *node;
	
	hash = make_hash(key, key_size, 0);
	index =  hash % hash_table->size;
	bucket = hash_table->buckets[index];

	//printf("%d %d \n", hash, index);
	if( ! bucket ) {
		hash_table->buckets[index] = malloc(sizeof(list_t));
		bucket = hash_table->buckets[index];
		list_init(bucket);
	}
	node = malloc(sizeof(hash_node_t));
	node->key = strdup(key);
	node->value = value;
	list_node_add_tail(bucket, node);
}

void* hash_table_find(hash_table_t *hash_table, char *key)
{
	unsigned int hash, index;
	int key_size = strlen(key);
	list_t *bucket;
	list_node_t *cur;
	hash_node_t *node;

	hash = make_hash(key, key_size, 0);
	index =  hash % hash_table->size;
	bucket = hash_table->buckets[index];
	
	if( ! bucket ) { return NULL; }

	LIST_FOR_EACH(cur, bucket) {
		node = (hash_node_t*)cur->data;
		if( ! strcmp(node->key, key) ) {
			return node->value;
		}
	}
	return NULL;
}

void hash_table_remove(hash_table_t *hash_table, char *key)
{
	unsigned int hash, index;
	int key_size = strlen(key);
	list_t *bucket;
	list_node_t *cur;
	hash_node_t *node;

	hash = make_hash(key, key_size, 0);
	index =  hash % hash_table->size;
	bucket = hash_table->buckets[index];

	LIST_FOR_EACH(cur, bucket) {
		node = (hash_node_t*)cur->data;
		if( ! strcmp(node->key, key) ) {
			list_node_del(bucket, node);
			free(node->key);
			free(node);
			return;
		}
	}
}

void hash_table_clear(hash_table_t *hash_table)
{
	int i;
	list_t *bucket;
	list_node_t *cur;
	hash_node_t *node;

	for( i = 0; i < hash_table->size; i++ ) {
		bucket = hash_table->buckets[i];
		hash_table->buckets[i] = NULL;
		if( bucket ) {
			LIST_FOR_EACH(cur, bucket) {
				node = (hash_node_t*)cur->data;
				free(node->key);
				free(node);
			}
			list_clear(bucket);
		}
		free(bucket);
	}
}




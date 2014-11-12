#pragma once
#include "typedef.h"

typedef struct _list_node_t
{
	void *data;
	struct _list_node_t *prev;
	struct _list_node_t *next;

}list_node_t;

typedef struct _list_t
{
	int size;
	list_node_t head;
	list_node_t tail;

}list_t;

void list_init(list_t *list);
void list_clear(list_t *list);
void* list_node_add_tail(list_t *list, void *data);
int list_node_del(list_t *list, void *data);
int list_node_find(list_t *list, void *data);

#define LIST_FOR_EACH(pos, list) \
	for( pos = (list)->head.next; pos != &(list)->tail; pos = pos->next)


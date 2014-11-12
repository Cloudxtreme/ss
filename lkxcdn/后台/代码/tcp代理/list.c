#include "list.h"
#include <stdlib.h>

void list_init(list_t *list)
{
	list->size = 0;
	list->head.next = &list->tail;
	list->head.prev = NULL;
	list->tail.prev = &list->head;
	list->tail.next = NULL;
	list->head.data = list->tail.data = NULL;
}

void list_clear(list_t *list)
{
	list_node_t *cur = list->head.next;
	while( cur != &list->tail ) {
		list_node_t *next = cur->next;
		free(cur);
		cur = next;
	}
	list->head.next = &list->tail;
	list->tail.prev = &list->head;
	list->size = 0;
}

void* list_node_add_tail(list_t *list, void *data)
{
	list_node_t *node = (list_node_t*)malloc(sizeof(list_node_t));
	node->data = data;
	list->tail.prev->next = node;
	node->prev = list->tail.prev;
	node->next = &list->tail;
	list->tail.prev = node;
	list->size++;
	return node;
}

int list_node_del(list_t *list, void *data)
{
	list_node_t *cur;
	LIST_FOR_EACH(cur, list) {
		if( cur->data == data ) {
			cur->prev->next = cur->next;
			cur->next->prev = cur->prev;
			free(cur);
			list->size--;
			return 0;
		}
	}
	return 1;
}

int list_node_find(list_t *list, void *data)
{
	list_node_t *node = (list_node_t*)data;
	list_node_t *cur;
	LIST_FOR_EACH(cur, list) {
		if( cur->data == node ) {
			return 1;
		}
	}
	return 0;
}


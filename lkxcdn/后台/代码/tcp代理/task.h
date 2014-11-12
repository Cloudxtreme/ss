#pragma once
#include "typedef.h"

typedef struct _task_t
{
	volatile int want_exit, is_run;
	void *data;

	void (*run)(void *data);

}task_t;

task_t* create_task();
int task_start(task_t *task, int run_once);
int task_stop(task_t *task);
int task_join(task_t *task);


#include "task.h"
#include "misc.h"

#ifdef WIN32
void task_run_once(void *data)
#else
void* task_run_once(void *data)
#endif
{
	task_t *task = (task_t*)data;
	if( task->run ) {
		task->run(task->data);
	}
	task->is_run = 0;
}

#ifdef WIN32
void task_run(void *data)
#else
void* task_run(void *data)
#endif
{
	task_t *task = (task_t*)data;
	while( ! task->want_exit ) {
		if( task->run ) {
			task->run(task->data);
		}
	}
	task->is_run = 0;
}

task_t* create_task()
{
	task_t *task = malloc(sizeof(task_t));
	task->want_exit = 0;
	task->is_run = 0;
	task->run = NULL;
	return task;
}

int task_start(task_t *task, int run_once)
{
	if( ! task->want_exit && ! task->is_run ) {
		task->is_run = 1;
		if( run_once ) {
#ifdef WIN32
			_beginthread(task_run_once, 0, task);
#else
			pthread_t pid;
			pthread_create(&pid, NULL, task_run_once, task);
#endif
		} else {
#ifdef WIN32
			_beginthread(task_run, 0, task);
#else
			pthread_t pid;
			pthread_create(&pid, NULL, task_run, task);
#endif
		}
	}
	return 0;
}

int task_stop(task_t *task)
{
	task->want_exit = 1;
	return 0;
}

int task_join(task_t *task)
{
	while( task->is_run ) {
		misc_msleep(100);
	}
	return 0;
}


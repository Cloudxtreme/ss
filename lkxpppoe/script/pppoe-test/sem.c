#include <stdio.h>
#include <sys/sem.h>
#include <sys/ipc.h>
#include <string.h>
#include <errno.h>
#include "pppoe-test.h"
union semun
{
	int					val;
	struct semid_ds		*buf;
	unsigned short		*array;
}arg;

sem_t CreateSem(key_t key,int value)
{
	union semun sem;
	sem_t semid;
	sem.val = value;
	semid = semget(key,1,IPC_CREAT|0666);
	if(-1 == semid)
	{
		printf("after semget error num:[%d]\n",errno);
		return -1;
	} 
	semctl(semid,0,SETVAL,sem);
	return semid;
}

int Sem_Wait(sem_t semid)
{
	struct sembuf sops;
	sops.sem_num = 0;
	sops.sem_op  = -1;
	sops.sem_flg   = SEM_UNDO;
	return (semop(semid,&sops,1));
}

int Sem_Post(sem_t semid)
{
	struct sembuf sops;
	sops.sem_num = 0;
	sops.sem_op  = 1;
	sops.sem_flg   = SEM_UNDO;
	return (semop(semid,&sops,1));
}

void DestroySem(sem_t semid)
{
	union semun sem;
	sem.val = 0;
	semctl(semid,0,IPC_RMID,sem);
}



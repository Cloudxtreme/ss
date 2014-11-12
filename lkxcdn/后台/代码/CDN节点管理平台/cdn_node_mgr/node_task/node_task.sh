#!/bin/sh


#########################
#########################

task_get_timeout=10
task_retry=3

proc=`ps -ef|grep -v grep|grep alltask.list |wc -l`
echo $proc

if [ $proc -gt 0 ]
then
        echo "running"
        exit 1
fi

/usr/bin/wget -t $task_retry -T $task_get_timeout -N "http://cdnmgr.efly.cc/cdn_node_mgr/node_task/node_task.php" -O /opt/node_task/alltask.list

/bin/sh /opt/node_task/alltask.list

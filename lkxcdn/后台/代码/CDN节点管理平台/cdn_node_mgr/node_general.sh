#!/bin/sh

task_get_timeout=10
task_retry=3

/usr/bin/wget -t $task_retry -T $task_get_timeout -N http://cdnmgr.efly.cc/cdn_node_mgr/node_task/node_task.sh -O /opt/node_task/node_task.sh
/bin/sh < /opt/node_task/node_task.sh

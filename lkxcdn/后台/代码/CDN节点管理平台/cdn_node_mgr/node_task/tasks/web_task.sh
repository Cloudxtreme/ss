#!/bin/sh

task_get_timeout=10
task_retry=3

to_day=`date +%Y%m%d`
today_dir="/opt/node_task/log/$to_day"
mkdir -p $today_dir

filesize=100
while [ $filesize -gt 2 ];
do
	now=`date +%H:%M:%S.%N`
	web_cache_task="/opt/node_task/web_cache_task.list_$now"
	/usr/bin/wget -t $task_retry -T $task_get_timeout "http://cdnmgr.efly.cc/cdn_node_mgr/node_task/tasks/web_cache.php" -O $web_cache_task
	filesize=`ls -l $web_cache_task | awk '{print $5}'`
	taskid=`cat $web_cache_task | grep "##" | awk -F"=" '{print $2}'`
	if [ $filesize -gt 2 ]; then
		task_name="web_cache_task.list_${taskid}_"
		final_task="${today_dir}/${task_name}"
		mv $web_cache_task $final_task
		ps -ef | grep ${task_name} | grep -v grep | awk '{print $2}' | xargs kill -9
		/bin/sh $final_task &
	else
		rm $web_cache_task
	fi
	sleep 1 
done

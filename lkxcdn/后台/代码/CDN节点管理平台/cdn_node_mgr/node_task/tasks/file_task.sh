#!/bin/sh

task_get_timeout=10
task_retry=3

to_day=`date +%Y%m%d`
today_dir="/opt/node_task/log/$to_day"
mkdir -p $today_dir
mkdir -p /opt/node_task/file

filesize=100
while [ $filesize -gt 2 ];
do
        now=`date +%H:%M:%S.%N`
        file_md5_task="/opt/node_task/file_md5_task.list_$now"
        /usr/bin/wget -t $task_retry -T $task_get_timeout "http://cdnmgr.efly.cc/cdn_node_mgr/node_task/tasks/file_md5.php" -O $file_md5_task
        filesize=`ls -l $file_md5_task | awk '{print $5}'`
	taskid=`cat $file_md5_task | grep "##" | awk -F"=" '{print $2}'`
        if [ $filesize -gt 2 ]; then
		task_name="file_md5_task.list_${taskid}_"
                final_task="${today_dir}/${task_name}"
                mv $file_md5_task $final_task
		ps -ef | grep ${task_name} | grep -v grep | awk '{print $2}' | xargs kill -9
                /bin/sh $final_task &   
        else
                rm $file_md5_task
        fi
	sleep 1
done

filesize=100
while [ $filesize -gt 2 ];
do
        now=`date +%H:%M:%S.%N`
        file_cache_task="/opt/node_task/file_cache_task.list_$now"
        /usr/bin/wget -t $task_retry -T $task_get_timeout "http://cdnmgr.efly.cc/cdn_node_mgr/node_task/tasks/file_cache.php" -O $file_cache_task
        filesize=`ls -l $file_cache_task | awk '{print $5}'`
	taskid=`cat $file_cache_task | grep "##" | awk -F"=" '{print $2}'`
        if [ $filesize -gt 2 ]; then
		task_name="file_cache_task.list_${taskid}_"
                final_task="${today_dir}/${task_name}"
                mv $file_cache_task $final_task
		ps -ef | grep ${task_name} | grep -v grep | awk '{print $2}' | xargs kill -9
                /bin/sh $final_task &
        else
                rm $file_cache_task
        fi
	sleep 1
done

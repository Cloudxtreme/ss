#!/bin/sh

source /etc/profile

proc=`ps -ef|grep -v grep|grep python|grep node_task|wc -l`
ip=`curl http://61.142.208.98/ip.php`

echo $proc $ip

cd /opt/skynet

if [ $proc -eq 0 ]
then
	nohup python /opt/skynet/node_task_sub.py $ip $ip > /opt/skynet/log 2>&1 &
fi

ps -ef|grep python


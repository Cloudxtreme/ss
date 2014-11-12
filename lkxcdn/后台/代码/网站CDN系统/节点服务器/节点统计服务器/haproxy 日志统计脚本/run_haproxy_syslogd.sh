#!/bin/sh

proc=`ps -ef|grep -v grep|grep haproxy_syslogd.py |wc -l`
echo $proc

if [ $proc -eq 0 ]
then
	echo "run"
	nohup python /opt/haproxy_tools/haproxy_syslogd.py > /dev/null 2>&1 &
fi



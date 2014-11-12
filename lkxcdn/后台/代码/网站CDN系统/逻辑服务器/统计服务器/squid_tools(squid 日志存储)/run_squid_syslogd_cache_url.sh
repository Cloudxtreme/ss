#!/bin/sh

proc=`ps -ef|grep -v grep|grep squid_syslogd_cache_url.py|wc -l`
echo $proc

if [ $proc -eq 0 ]
then
	echo "run"
	nohup python /opt/squid_tools/squid_syslogd_cache_url.py > /dev/null 2>&1 &
fi



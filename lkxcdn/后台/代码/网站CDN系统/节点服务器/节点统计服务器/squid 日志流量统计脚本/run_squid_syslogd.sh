#!/bin/sh

proc=`ps -ef|grep -v grep|grep squid_syslogd.py|wc -l`
echo $proc

if [ $proc -eq 0 ]
then
	echo "run"
	python /opt/squid_tools/squid_syslogd.py "webstats.cdn.efly.cc" "/cdnmgr/cdn_stats/web_client_rate_post.php" &
fi



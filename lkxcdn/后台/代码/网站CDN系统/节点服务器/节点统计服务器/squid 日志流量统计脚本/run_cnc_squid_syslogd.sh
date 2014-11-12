#!/bin/sh

proc=`ps -ef|grep -v grep|grep cnc_squid_syslogd.py|wc -l`
echo $proc

if [ $proc -eq 0 ]
then
	echo "run"
	nohup python /opt/squid_tools/cnc_squid_syslogd.py "cnc.webstats.cdn.efly.cc" "/cdnmgr/cdn_stats/web_client_rate_post.php" > /dev/null 2>&1 &
fi



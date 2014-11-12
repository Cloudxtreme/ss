#!/bin/sh

logpath=/opt/haproxy_log
logtemppath=/opt/haproxy_temp_log

date=`date -d '-1 day' +%Y-%m-%d`
deldate=`date -d '-8 day' +%Y-%m-%d`

cpcmd=/opt/haproxy_tools/cpcmd.sh

server_list=/opt/haproxy_tools/server_list.txt

logfile=$date.tar.gz

rm -rf $logtemppath/*
rm -rf $logpath/$deldate

mkdir -p $logpath/$date

cd $logtemppath

`wget http://cdninfo.efly.cc/cdnmgr/cdn_web/cdn_stats_server_list.php -O $server_list`

cat $server_list | while read server_info
do
	ip=${server_info%:*}
	port=${server_info#*:}
	#iplogfile="$date""_$ip.tar.gz"
	#echo $ip $port

	mkdir -p $ip
	url="http://$server_info/$logfile"
	cd $ip

	dwcmd="/usr/bin/wget -t 5 http://$server_info/$logfile -O $logfile"
	#echo $dwcmd
	$dwcmd

	tar zxf $logfile
	rm -rf $logfile

	cd ..
done

/usr/bin/php /opt/haproxy_tools/deal_haproxy_log_day.php > $cpcmd

bash $cpcmd




#!/bin/sh

cd /opt/cdn_hot_file/ftp_inotify

logproc=`ps -ef|grep -v grep|grep ftp_general.php|wc -l`
echo $logproc

if [ $logproc -eq 0 ]
then
	echo "run ftp general"
	/usr/bin/php ftp_general.php > /dev/null 2>&1 &
fi

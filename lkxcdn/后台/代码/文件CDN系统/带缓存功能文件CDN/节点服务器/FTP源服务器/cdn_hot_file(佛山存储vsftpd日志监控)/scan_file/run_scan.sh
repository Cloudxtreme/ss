#!/bin/sh

cd /opt/cdn_hot_file/scan_file

proc=`ps -ef|grep -v grep|grep scan_file_main.php |wc -l`
echo $proc

if [ $proc -eq 0 ]
then
	echo "run"
	/usr/bin/php /opt/cdn_hot_file/scan_file/scan_file_main.php > /dev/null 2>&1
fi


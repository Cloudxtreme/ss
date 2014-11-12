#!/bin/sh

cd /opt/cdn_hot_file/auto_extract

proc=`ps -ef|grep -v grep|grep auto_extract_main.php |wc -l`
echo $proc

if [ $proc -eq 0 ]
then
	echo "run"
	/usr/bin/php /opt/cdn_hot_file/auto_extract/auto_extract_main.php > /dev/null 2>&1
fi



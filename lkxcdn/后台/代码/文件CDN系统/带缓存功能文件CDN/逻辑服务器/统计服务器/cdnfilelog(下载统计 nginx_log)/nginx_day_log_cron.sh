#!/bin/sh

myip=$1

logpath=/opt/nginx_log
logsrcpath=/opt/nginx_cache/nginx/logs

date=`date -d '-1 day' +%Y-%m-%d`
deldate=`date -d '-3 day' +%Y-%m-%d`
#echo $date

rm -rf $logpath/$deldate*

mkdir -p $logpath/$date

cd $logsrcpath

echo "" > access.log
echo "" > error.log

ls *.log | egrep -v "access.log|error.log" | while read line
do
	strlen=${#line}
	if [ $strlen -eq 0 ]
	then
		continue
	fi

	filename=$line
	filepathname=$logsrcpath/$line
	#echo $filepathname $filename
	/bin/mv -f $filepathname $logpath/$date/$filename
done

/usr/bin/killall -s USR1 nginx

cd $logpath
/bin/tar -zcf $date.tar.gz $date


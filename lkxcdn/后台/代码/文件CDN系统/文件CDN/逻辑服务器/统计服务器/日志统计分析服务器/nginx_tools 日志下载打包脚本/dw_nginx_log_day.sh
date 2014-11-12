#!/bin/sh

logpath=/opt/nginx_log
logsrc=/opt/cdnfilelog/logs/

date=`date -d '-1 day' +%Y-%m-%d`
deldate=`date -d '-15 day' +%Y-%m-%d`

mkdir -p $logpath/$date

rm -rf $logpath/$deldate
rm -rf $logsrc/$deldate

cd $logsrc/$date

for log in *.log
do
	echo $log
	/bin/tar -zcf $date\_$log.txt.tar.gz $log
	mv $date\_$log.txt.tar.gz $logpath/$date/$date\_$log.txt.tar.gz
done



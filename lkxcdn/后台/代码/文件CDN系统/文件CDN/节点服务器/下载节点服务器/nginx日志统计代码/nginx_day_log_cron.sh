#!/bin/sh

myip=$1

logpath=/opt/nginx_log
toolspath=/opt/nginx_tools
config=/opt/nginx_tools/config 

date=`date -d '-1 day' +%Y-%m-%d`
deldate=`date -d '-3 day' +%Y-%m-%d`
#echo $date

rm -rf $logpath/$deldate*

mkdir -p $logpath/$date

cat $config | while read line
do
	strlen=${#line}
	if [ $strlen -eq 0 ]
	then
		continue
	fi

	clientdb=`echo $line | awk '{print $1}'`
	#echo $clientdb
	
	logfile=`echo $line | awk '{print $2}'`
	#echo $logfile

	filename=${logfile##/*/}
	#echo $filename

	/bin/mv $logfile $logpath/$date/$filename

        /usr/bin/python $toolspath/nginx_day_log_deal.py $myip $date $clientdb $logpath/$date/$filename
done

/usr/bin/killall -s USR1 nginx

cd $logpath
/bin/tar -zcf $date.tar.gz $date


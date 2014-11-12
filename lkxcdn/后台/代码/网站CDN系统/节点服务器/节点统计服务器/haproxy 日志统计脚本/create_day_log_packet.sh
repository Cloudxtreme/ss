#!/bin/sh

date=`date -d '-1 day' +%Y-%m-%d`
olddate=`date -d '-2 day' +%Y-%m-%d`
logpath=/opt/haproxy_log
cd $logpath
/bin/tar -zcf $date.tar.gz $date
rm -rf $olddate.tar.gz $olddate $olddate'_h'


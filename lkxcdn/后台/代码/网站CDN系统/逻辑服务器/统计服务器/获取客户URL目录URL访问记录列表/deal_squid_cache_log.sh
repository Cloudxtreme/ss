#!/bin/sh

date=`date -d '-1 day' +%Y-%m-%d`
logpath=/opt/squid_log/$date
#echo $logpath

/usr/bin/php /opt/squid_tools/deal_squid_cache_log.php 

rm -rf $logpath


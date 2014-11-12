#!/bin/sh

parse_date=`date +%Y-%m-%d -d "-5 minute"`
parse_hour=`date +%H -d "-5 minute"`
parse_minute=`date +%M -d "-5 minute"`
parse_group=`expr ${parse_minute} / 5`

parse_path="/opt/cdnfilelog/logs/$parse_date/$parse_hour/$parse_group"

for i in `ls $parse_path`
do
	parse_file="$parse_path/$i"
	parse_user=`echo $i | cut -d "." -f 1`
	
	/usr/bin/php /opt/cdnfilelog/cdnfilelog_stat.php $parse_date $parse_hour $parse_group $parse_user $parse_file
	#echo $parse_user
	#echo $parse_file
done;

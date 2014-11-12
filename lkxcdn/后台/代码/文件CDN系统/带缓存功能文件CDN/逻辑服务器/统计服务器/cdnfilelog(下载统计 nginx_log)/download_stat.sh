#!/bin/sh

parse_date=`date +%Y-%m-%d -d "-1 day"`
#parse_date=`date +%Y-%m-%d`
parse_path="/opt/cdnfilelog/logs/$parse_date"

for i in `ls $parse_path`
do
	parse_file="$parse_path/$i"
	parse_user=`echo $i | cut -d "." -f 1`
	
	/usr/bin/php /opt/cdnfilelog/download_stat.php $parse_date $parse_user $parse_file
done;

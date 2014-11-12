#!/bin/sh

parse_date=$1

for((h=0;h<24;h++))
do
	if [ $h -lt 10 ]
	then
		hh="0${h}"
	else
		hh="$h"
	fi
	for((g=0;g<12;g++))
	do

		parse_path="/opt/cdnfilelog/logs/$parse_date/$hh/$g"

		for i in `ls $parse_path`
		do
			parse_file="$parse_path/$i"
			parse_user=`echo $i | cut -d "." -f 1`
	
			echo $parse_date $hh $g $parse_user $parse_file
			/usr/bin/php /opt/cdnfilelog/cdnfilelog_stat.php $parse_date $hh $g $parse_user $parse_file
			#echo $parse_user
			#echo $parse_file
		done;
	done;
done;

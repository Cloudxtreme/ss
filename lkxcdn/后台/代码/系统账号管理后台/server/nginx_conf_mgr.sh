#!/bin/sh
opcode=$1
clientname=$2
clientport=$3
tempconf=nginx.conf.temp
conf=nginx.conf

cd /var/www/html/webadmin/server

case "$opcode" in
add)
	echo 'add'

	check=`grep $clientname $tempconf|wc -l`;
	echo $check

	if [ $check -eq 0 ]
	then
		echo "#$clientname""_begin" >> $tempconf
		echo "server {" >> $tempconf
		echo "	listen $clientport;" >> $tempconf
		echo "	server_name localhost;" >> $tempconf
		echo "	location / {root /opt/rsyncdata/cdn_file/$clientname;}" >> $tempconf
		echo "	access_log logs/$clientname.log mylog;" >> $tempconf
		echo "}" >> $tempconf
		echo "#$clientname""_end" >> $tempconf
	fi

	cp -r $tempconf $conf
	echo "}" >> $conf

	;;

del)
	echo 'del'

	n1=`grep -n "#$clientname""_begin" $tempconf|awk -F ':' '{print $1}'`
	n2=`grep -n "#$clientname""_end" $tempconf|awk -F ':' '{print $1}'`

	echo $n1
	echo $n2

	sedcmd="sed -i '$n1,$n2""d' $tempconf"
	echo "$sedcmd"|bash

	cp -r $tempconf $conf
	echo "}" >> $conf

	;;

*)
        echo 'unknow!'
        ;;
esac


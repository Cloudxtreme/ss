#!/bin/sh
opcode=$1
clientname=$2
conf=nginx_tools_config

cd /var/www/html/webadmin/server

case "$opcode" in
add)
	echo 'add'

	check=`grep $clientname $conf|wc -l`;
	echo $check

	if [ $check -eq 0 ]
	then
		echo "cdn_""$clientname""_nginx_stats /opt/nginx_cache/nginx/logs/""$clientname"".log" >> $conf
	fi

	;;

del)
	echo 'del'

	sed -i "/$clientname/d" $conf

	;;

*)
        echo 'unknow!'
        ;;
esac


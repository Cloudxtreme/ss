#!/bin/sh
opcode=$1
clientname=$2
clientport=$3
conf=cdn_hotfile_nginx.conf
confbase=cdn_hotfile_nginx.conf.base
confbe=cdn_hotfile_nginx.conf.backend

cd /var/www/html/webadmin/server

case "$opcode" in
add)
	echo 'add'

	check=`grep $clientname $confbe|wc -l`;
	echo $check

	if [ $check -eq 0 ]
	then
		echo "#$clientname""_begin" >> $confbe
		echo "server {" >> $confbe
		echo "	listen $clientport;" >> $confbe
		echo "	server_name localhost;" >> $confbe
		echo "	location / {" >> $confbe
		echo "		proxy_cache cache_one;" >> $confbe
		echo "		proxy_cache_valid 200 3650d;" >> $confbe
		echo '		proxy_cache_key $uri;' >> $confbe
		echo '		proxy_set_header Host $host;' >> $confbe
		echo '		proxy_set_header X-Forwarded-For $remote_addr;' >> $confbe
		echo "		proxy_pass http://cdn_hotfile_fs_backend_server/$clientname/;" >> $confbe
		echo "		access_log logs/$clientname.log mylog;" >> $confbe
		echo "	}" >> $confbe
		echo "	location ~ /rjkjcdn-purge(/.*) {" >> $confbe
		echo "		allow all;" >> $confbe
		echo '		proxy_cache_purge cache_one $1;' >> $confbe
		echo "	} " >> $confbe
		echo "}" >> $confbe
		echo "#$clientname""_end" >> $confbe
	fi

	cat $confbase $confbe > $conf
	echo "}" >> $conf

	;;

del)
	echo 'del'

	n1=`grep -n "#$clientname""_begin" $confbe|awk -F ':' '{print $1}'`
	n2=`grep -n "#$clientname""_end" $confbe|awk -F ':' '{print $1}'`

	echo $n1
	echo $n2

	sedcmd="sed -i '$n1,$n2""d' $confbe"
	echo "$sedcmd"|bash

	cat $confbase $confbe > $conf
	echo "}" >> $conf

	;;

*)
        echo 'unknow!'
        ;;
esac


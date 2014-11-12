#!/bin/sh
opcode=$1
nginxport=$2

ctconf=file_port_rate_config_ct
cncconf=file_port_rate_config_cnc

cd /var/www/html/webadmin/server

case "$opcode" in
add)
	echo 'add'

	check=`grep $nginxport $ctconf|wc -l`;
	echo $check
	if [ $check -eq 0 ]
	then
		echo "/opt/cachemgr/portinfo filestats.cdn.efly.cc src $nginxport eth0" >> $ctconf
		echo "/opt/cachemgr/portinfo filestats.cdn.efly.cc $nginxport eth0" >> $ctconf
	fi

	check=`grep $nginxport $cncconf|wc -l`;
	echo $check
	if [ $check -eq 0 ]
	then
		echo "/opt/cachemgr/portinfo filestats.cdn.efly.cc src $nginxport eth0" >> $cncconf
		echo "/opt/cachemgr/portinfo filestats.cdn.efly.cc dst $nginxport eth0" >> $cncconf
	fi

	;;

del)
	echo 'del'

	sed -i "/$nginxport/d" $ctconf
	sed -i "/$nginxport/d" $cncconf

	;;

*)
        echo 'unknow!'
        ;;
esac


#!/bin/sh
opcode=$1
squidport=$2

ctconf=web_port_rate_config_ct
cncconf=web_port_rate_config_cnc

cd /var/www/html/webadmin/server

case "$opcode" in
add)
	echo 'add'

	check=`grep $squidport $ctconf|wc -l`;
	echo $check
	if [ $check -eq 0 ]
	then
		echo "/opt/cachemgr/portinfo 121.9.13.245 src $squidport lo" >> $ctconf
		echo "/opt/cachemgr/portinfo 121.9.13.245 dst $squidport lo" >> $ctconf
	fi

	check=`grep $squidport $cncconf|wc -l`;
	echo $check
	if [ $check -eq 0 ]
	then
		echo "/opt/cachemgr/portinfo 58.255.252.91 src $squidport lo" >> $cncconf
		echo "/opt/cachemgr/portinfo 58.255.252.91 dst $squidport lo" >> $cncconf
	fi

	;;

del)
	echo 'del'

	sed -i "/$squidport/d" $ctconf
	sed -i "/$squidport/d" $cncconf

	;;

*)
        echo 'unknow!'
        ;;
esac


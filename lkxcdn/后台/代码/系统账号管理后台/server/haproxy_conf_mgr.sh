#!/bin/sh
opcode=$1
domain=$2
squidport=$3
conf=haproxy.cfg
headconf=haproxy.cfg.1
aclconf=haproxy.cfg.2
backendconf=haproxy.cfg.3

cd /var/www/html/webadmin/server

case "$opcode" in
add)
	echo 'add'

	check=`grep $domain $aclconf|wc -l`;
	echo $check
	if [ $check -eq 0 ]
	then
		echo "acl is.$domain hdr_end(host) -i $domain" >> $aclconf
		echo "use_backend $domain if is.$domain" >> $aclconf
	fi

	check=`grep $domain $backendconf|wc -l`;
	echo $check
	if [ $check -eq 0 ]
	then
		echo "#$domain""_begin" >> $backendconf
		echo "backend $domain" >> $backendconf
		echo "	balance roundrobin" >> $backendconf
		echo "	cookie SERVERID insert nocache indirect" >> $backendconf
		echo "	option httpchk HEAD /check.txt HTTP/1.0" >> $backendconf
		echo "	option httpclose" >> $backendconf
		echo "	option forwardfor" >> $backendconf
		echo "	server squid1 127.0.0.1:$squidport cookie squid1" >> $backendconf
		echo "#$domain""_end" >> $backendconf
	fi

	cat $headconf $aclconf $backendconf > $conf

	;;

del)
	echo 'del'

	sed -i "/$domain/d" $aclconf	
	
	n1=`grep -n "#$domain""_begin" $backendconf|awk -F ':' '{print $1}'`
	n2=`grep -n "#$domain""_end" $backendconf|awk -F ':' '{print $1}'`

	echo $n1
	echo $n2

	sedcmd="sed -i '$n1,$n2""d' $backendconf"
	echo "$sedcmd"|bash

	cat $headconf $aclconf $backendconf > $conf

	;;

*)
        echo 'unknow!'
        ;;
esac


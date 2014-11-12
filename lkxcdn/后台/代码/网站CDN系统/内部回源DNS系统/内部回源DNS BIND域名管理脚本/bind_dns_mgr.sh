#!/bin/sh
#opcode domain db table
opcode=$1
domain=$2
table=$3

#bind_check conffile
bind_check=/opt/bind9db/sbin/named-checkconf

#bind_rndc -c confile
bind_rndc=/opt/bind9db/sbin/rndc

ct_name=/opt/bind9db/etc/ct_named.conf
cnc_name=/opt/bind9db/etc/cnc_named.conf

ct_rndc=/opt/bind9db/etc/ct_rndc.conf
cnc_rndc=/opt/bind9db/etc/cnc_rndc.conf

case "$opcode" in
add)
		echo 'add'

		check=`grep \"$domain\" $ct_name|wc -l`;
		echo $check
		if [ $check -eq 0 ]
		then
			echo "zone \"$domain\" IN {type master;notify no;database \"mysqldb squid_dns_ct $table 127.0.0.1 root rjkj@rjkj\";};" >> $ct_name
		fi

		check=`grep \"$domain\" $cnc_name|wc -l`;
		echo $check
		if [ $check -eq 0 ]
		then
			echo "zone \"$domain\" IN {type master;notify no;database \"mysqldb squid_dns_cnc $table 127.0.0.1 root rjkj@rjkj\";};" >> $cnc_name
		fi
        ;;

del)
        echo 'del'
		
		sed -i "/$domain/d" $ct_name
		sed -i "/$domain/d" $cnc_name
        ;;

check)
		echo 'check'

		echo `$bind_check $ct_name`
		echo `$bind_check $cnc_name`
		;;

reload)
        echo 'reload'

		echo `$bind_rndc -c $ct_rndc reload`
		echo `$bind_rndc -c $cnc_rndc reload`
        ;;

*)
        echo 'unknow!'
        ;;
esac




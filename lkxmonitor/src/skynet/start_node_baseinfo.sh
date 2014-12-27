#!/bin/sh

source /etc/profile

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/local/lib

proc=`ps -ef|grep -v grep|grep python|grep node_baseinfo_snmp_push|wc -l`

ip=`curl http://61.142.208.98/ip.php`

cd /opt/skynet/


if [ $proc -eq 0 ]
then
	nohup python node_baseinfo_snmp_push.py $ip > /opt/skynet/snmplog 2>&1 &
fi

ps -ef|grep python


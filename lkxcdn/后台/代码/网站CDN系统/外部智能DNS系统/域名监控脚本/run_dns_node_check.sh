#!/bin/sh
while [ 1 ]
do
	echo 'run'
	cd /opt/dns_check_ex
	php dns_node_check.php >> log
	sleep 60
done

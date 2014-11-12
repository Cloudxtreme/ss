#!/bin/sh
while [ 1 ]
do
	echo 'run'
	cd /opt/dns_check_ex
	php main_dns_node_check.php >> log
	sleep 60
done

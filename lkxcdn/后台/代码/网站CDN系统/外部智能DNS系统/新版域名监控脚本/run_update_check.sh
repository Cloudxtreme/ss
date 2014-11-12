#!/bin/sh
while [ 1 ]
do
	echo 'run'
	cd /opt/cdn_dns_web_node_check
	/usr/bin/php mobile_update_dns_ip_set.php >> log
	/usr/bin/php mobile_main_dns_node_check.php >> log
	sleep 120
done

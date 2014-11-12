#!/bin/sh
while [ 1 ]
do
	echo 'run'
	cd /opt/cdn_node_check
	php main.php >> log
	sleep 60
done

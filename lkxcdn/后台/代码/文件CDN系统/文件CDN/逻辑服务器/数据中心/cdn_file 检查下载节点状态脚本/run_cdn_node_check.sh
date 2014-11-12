#!/bin/sh
while [ 1 ]
do
	echo 'run'
	cd /opt/cdn_node_check
	php cdn_node_check.php >> log
	sleep 60
done


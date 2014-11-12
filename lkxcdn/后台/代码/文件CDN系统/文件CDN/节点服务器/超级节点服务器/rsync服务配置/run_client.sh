#!/bin/sh
while [ 1 ]
do
	echo 'run'
	/opt/rsync/rsync -rptgoD -L --delete --exclude=".*" 116.28.64.165::cdn_file /opt/rsyncdata/cdn_file
	sleep 60
done

#!/bin/sh
while [ 1 ]
do
	echo 'run'
	/opt/rsync/rsync -rptgoD -L --delete --exclude=".*" supernode.cdn.efly.cc::cdn_file /opt/rsyncdata/cdn_file
	sleep 60
done


#!/bin/sh
while [ 1 ]
do
	run=`pgrep rsyncd | wc -l`
	if [ $run -eq 0 ]
	then
		echo 'run rsync'
		/opt/rsync/rsyncd --daemon --config=/opt/rsync/rsync.conf
		sleep 3
	fi
	sleep 1
done

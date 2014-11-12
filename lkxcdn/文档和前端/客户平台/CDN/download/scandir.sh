#!/bin/sh
# ./scandir www.test.com /var/www/html/testweb/doc
# arg1 hostname
# arg2 scan dir path

hostname=$1
dir=$2

pdir=${dir%/*}
sdir=${dir##/*/}
cd $pdir

find $sdir | while read line
do
	echo "http://$hostname/$line"
done


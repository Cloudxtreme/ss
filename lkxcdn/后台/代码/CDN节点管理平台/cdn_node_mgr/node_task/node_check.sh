#!/bin/sh

cd /opt/cdn_node_mgr/node_task

#/usr/bin/php node_check.php > /dev/null 2>&1

proc=`ps -ef|grep -v grep|grep node_check.php |wc -l`
echo $proc

if [ $proc -eq 0 ]
then
        echo "run"
        /usr/bin/php node_check.php > /dev/null 2>&1
fi

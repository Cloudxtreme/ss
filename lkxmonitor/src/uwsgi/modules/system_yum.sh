#!/bin/sh
opt=$1
software=$2
taskid=$3
yumlog=/opt/uwsgi/modules/yum.log

st=`date "+%Y-%m-%d_%T"`
yum $opt -y $software > /tmp/yum.$taskid.log 2>&1 
ret=$?
et=`date "+%Y-%m-%d_%T"`

echo "yum $opt $software $taskid $st $et $ret" >> $yumlog


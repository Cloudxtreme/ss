#!/bin/bash
pid=`ps -e|grep radiusd|awk '{printf $1}'`
kill -9 $pid
/sbin/radiusd

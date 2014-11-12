#!/bin/sh

proc=`ps -ef | grep -v grep| grep mysql-proxy | grep 4041 | wc -l`

if [ $proc -eq 0 ]
then
        echo "run 4041"
        /usr/local/bin/mysql-proxy --proxy-backend-addresses=127.0.0.1 --proxy-address=:4041 --proxy-lua-script=/opt/db_mgr/file/script/efly-mysql-proxy.lua &
fi

proc=`ps -ef | grep -v grep| grep mysql-proxy | grep 4042 | wc -l`

if [ $proc -eq 0 ]
then
        echo "run 4042"
        /usr/local/bin/mysql-proxy --proxy-backend-addresses=127.0.0.1 --proxy-address=:4042 --proxy-lua-script=/opt/db_mgr/file/script/efly-mysql-proxy.lua &
fi

#4041

#4042

#...

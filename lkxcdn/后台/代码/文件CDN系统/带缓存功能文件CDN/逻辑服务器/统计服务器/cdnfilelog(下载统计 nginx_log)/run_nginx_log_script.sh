#!/bin/sh

export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/local/lib

ip=`curl http://cdnmgr.efly.cc/test.php`
echo $ip

kill `ps -ef|grep -v grep|grep nginx_log_sender|awk '{print $2}'`
kill `ps -ef|grep -v grep|grep nginx_log_reader|awk '{print $2}'`

cd /opt/nginx_tools

nohup python -u nginx_log_sender.py 183.60.46.163 > 163.sender.log 2>&1 &
nohup python -u nginx_log_sender.py 183.61.80.176 > /dev/null 2>&1 &
nohup python -u nginx_log_sender.py 115.238.185.165 > /dev/null 2>&1 &

ps -ef|grep -v grep|grep "nginx_log_sender"

nohup python -u nginx_log_reader.py $ip 183.60.46.163 > 163.reader.log 2>&1 &
nohup python -u nginx_log_reader.py $ip 183.61.80.176 > /dev/null 2>&1 &
nohup python -u nginx_log_reader.py $ip 115.238.185.165 > /dev/null 2>&1 &

ps -ef|grep -v grep|grep "nginx_log_reader"


#!/bin/bash
while [ 1 ]; do
pid=`ps axu |grep synuser.php -ab | grep -v grep | wc -l`
if [ "$pid" -gt 0 ]
then
date >> /var/log/syn.log
else
nohup php /etc/raddb/syn-user-on-radius/synuser.php > /var/log/syn.log 2>&1 &
fi
sleep 5
done

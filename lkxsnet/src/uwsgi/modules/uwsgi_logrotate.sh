#!/bin/sh
cp /var/log/uwsgi.log /var/log/uwsgi.`date +%Y-%m-%d`.log
echo "" > /var/log/uwsgi.log
deldate=`date -d '-7 day' +%Y-%m-%d`
rm -rf /var/log/uwsgi.$deldate.log

#!/bin/sh
today=`date '+%Y-%m-%d'`
mysqldump -h 10.18.255.281 -u root -prjkj@rjkj radius > /var/mysql/radius/$today.sql
mysqldump -h 10.18.255.183 -u root -prjkj@rjkj pppCenter > /var/mysql/center/$today.sql

#!/bin/sh

source /etc/profile

cd /opt/mjfilelog/

/usr/bin/php /opt/mjfilelog/cdnfilelog_stat_main.php > /dev/null 2>&1


#!/bin/sh

cd /opt/cdn_node_mgr/node_monitor

to_day=`date +%Y%m%d`
node_check_log="log/node_check_${to_day}.log"
dns_check_log="log/dns_check_${to_day}.log"

mkdir -p /opt/cdn_node_mgr/node_monitor/log

/usr/bin/php node_status_check.php >> $node_check_log

/usr/bin/php dns_status_check.php >> $dns_check_log

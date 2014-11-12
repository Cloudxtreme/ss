#!/bin/sh
echo 'run'
cd /opt/dns_check_ex
php db_proxy_server.php >> dblog

#!/bin/sh

pwd=/opt/nginx_cache

nginx_conf=$pwd/nginx/conf/nginx.conf

cd $pwd

#/usr/bin/wget http://s1.efly.cc/webadmin/server/cdn_hotfile_nginx.conf.base -O cdn_hotfile_nginx.conf.base
/usr/bin/wget http://s1.efly.cc/webadmin/server/cdn_hotfile_nginx.conf.backend -O cdn_hotfile_nginx.conf.backend

cat cdn_hotfile_nginx.conf.base cdn_hotfile_nginx.conf.backend > $nginx_conf

echo "}" >> $nginx_conf


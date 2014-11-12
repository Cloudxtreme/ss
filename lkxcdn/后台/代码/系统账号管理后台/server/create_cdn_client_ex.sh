#!/bin/sh
# $1 client name
# $2 client squid port
# $3 squid dns ip
# $4 squid cache size

cdn_root=opt
client_path=cdn_client
cdn_prefix=/$cdn_root/$client_path
client_squid=$1_squid
client_data=$1_data
client_squid_port=$2
client_squid_conf=$cdn_prefix/$client_squid/squid.conf
squid_dnsip=$3
squid_cachesize=$4

mkdir $cdn_prefix/$client_squid 
mkdir $cdn_prefix/$client_data 
chown nobody:nobody $cdn_prefix/$client_data

cp $cdn_prefix/squid $cdn_prefix/$client_squid
cp $cdn_prefix/squid_ex.conf $client_squid_conf

touch $cdn_prefix/$client_squid/access.log  $cdn_prefix/$client_squid/cache.log 
chmod 777 $cdn_prefix/$client_squid/access.log $cdn_prefix/$client_squid/cache.log

sed -i "s/cdn_snmp_port/$client_squid_port/g" $client_squid_conf
sed -i "s/cdn_client_http_port/$client_squid_port/g" $client_squid_conf
sed -i "s/cdn_client_dns_nameservers/$squid_dnsip/g" $client_squid_conf

sed -i "s/cdn_client_cache_dir/\/$cdn_root\/$client_path\/$client_data/g" $client_squid_conf
sed -i "s/cdn_client_cache_size/$squid_cachesize/g" $client_squid_conf

sed -i "s/cdn_client_coredump_dir/\/$cdn_root\/$client_path\/$client_squid/g" $client_squid_conf
sed -i "s/cdn_client_cache_log/\/$cdn_root\/$client_path\/$client_squid\/cache.log/g" $client_squid_conf
sed -i "s/cdn_client_access_log/\/$cdn_root\/$client_path\/$client_squid\/access.log/g" $client_squid_conf
sed -i "s/cdn_client_pid_filename/\/$cdn_root\/$client_path\/$client_squid\/squid.pid/g" $client_squid_conf



#!/bin/sh

rm -rf efapp
rm -rf efio
rm -rf efnet
rm -rf efext
rm -rf module
rm -rf tools
rm -rf doc
cd /usr/local/lib
rm -rf libefio.a libefnet.a libefext.a
cd /usr/local/include
rm -rf efio.h efnet.h efext.h netmap.h netmap_user.h pfring.h
cd /usr/sbin
rm -rf efvpn efvpnd server_conf
rm -rf /usr/local/sbin/dhcpd
rm -rf /usr/local/etc/dhcpd.conf
rm -rf /var/db/dhcpd.leases
chkconfig --del efvpnd
rm -rf /etc/init.d/efvpnd
rm -rf /etc/efvpn
echo "Finish!"

Summary: high performance virtual data link
Name: vdl
Version: 2.1
Release: 1
License: 2-clause BSD-like license
Group: Applications/Server
Source: http://www.eflypro.com/download/vdl.tar.gz
URL: http://www.eflypro.com
Distribution: Linux
Packager: eflypro <admin@www.centos.bz>
%define kernelsrc /usr/work/linux/linux-3.0.8
%define kernelversion 3.0.8
 
%description
	vdl - virtual data link
%prep
	rm -rf $RPM_BUILD_DIR/vpn
	zcat $RPM_SOURCE_DIR/vpn.tar.gz | tar -xvf -
%build
	cd $RPM_BUILD_DIR/vpn/efio/iobase/capture/netmap/LINUX
	make clean && make KSRC=%{kernelsrc}
	rm ./ixgbe/ixgbe.ko
	sed -i 's/ixgbe_vlan_strip_enable(adapter);/ixgbe_vlan_strip_disable(adapter);/g' ./ixgbe/ixgbe_main.c
	make
	cp ../sys/net/* $RPM_BUILD_DIR/vpn/inc
	cd $RPM_BUILD_DIR/vpn/efio/iobase/capture/pf_ring/drivers/PF_RING_aware/broadcom/netxtreme2-7.0.36/bnx2/src
	make clean && make
	cd $RPM_BUILD_DIR/vpn/efio/iobase/capture/pf_ring/kernel
	make clean && make
	cp linux/pf_ring.h $RPM_BUILD_DIR/vpn/inc/linux
	cd $RPM_BUILD_DIR/vpn/efio/iobase/capture/pf_ring/userland/lib
	/bin/sh configure && make clean && make
	cp pfring.h $RPM_BUILD_DIR/vpn/inc
	cp libpfring.a $RPM_BUILD_DIR/vpn/lib
	cd $RPM_BUILD_DIR/vpn/efio/iobase/capture/pf_ring/userland/libpcap
	/bin/sh configure && make clean && make
	cp libpcap.a $RPM_BUILD_DIR/vpn/lib
	cd $RPM_BUILD_DIR/vpn/efio
	/bin/sh configure && make clean && make
	cd $RPM_BUILD_DIR/vpn/efnet
	/bin/sh configure && make clean && make
	cd $RPM_BUILD_DIR/vpn/efext
	/bin/sh configure && make clean && make
	cd $RPM_BUILD_DIR/vpn/tools/zeromq-4.0.4
	/bin/sh configure && make clean && make
	cp include/zmq.h $RPM_BUILD_DIR/vpn/inc
	cp include/zmq_utils.h $RPM_BUILD_DIR/vpn/inc
	cp src/.libs/libzmq.a $RPM_BUILD_DIR/vpn/lib
	cd $RPM_BUILD_DIR/vpn/tools/openssl-1.0.1f
	/bin/sh config && make clean && make
	cp -rf include/openssl $RPM_BUILD_DIR/vpn/inc
	cp libcrypto.a $RPM_BUILD_DIR/vpn/lib
	cd $RPM_BUILD_DIR/vpn/efapp
	make clean && make all
	cd $RPM_BUILD_DIR/vpn/tools/dhcp-4.2.2
	/bin/sh configure && make clean && make
%install
	cd $RPM_BUILD_DIR/vpn/tools/dhcp-4.2.2
        make DESTDIR=$RPM_BUILD_ROOT install
	cd $RPM_BUILD_DIR/vpn/efapp
	make DESTDIR=$RPM_BUILD_ROOT install
	cd $RPM_BUILD_DIR/vpn/efio/iobase/capture/netmap/LINUX
	mkdir -p $RPM_BUILD_ROOT/lib/modules/%{kernelversion}/kernel/drivers/net/ixgbe
	mkdir -p $RPM_BUILD_ROOT/lib/modules/%{kernelversion}/kernel/drivers/net/e1000
        cp netmap_lin.ko $RPM_BUILD_ROOT/lib/modules/%{kernelversion}/kernel/drivers/net/
        cp ixgbe/ixgbe.ko $RPM_BUILD_ROOT/lib/modules/%{kernelversion}/kernel/drivers/net/ixgbe/
        cp e1000/e1000.ko $RPM_BUILD_ROOT/lib/modules/%{kernelversion}/kernel/drivers/net/e1000/
	mkdir -p $RPM_BUILD_ROOT/var/www/html
	cp -rf $RPM_BUILD_DIR/web $RPM_BUILD_ROOT/var/www/html/efvpn
%pre
	cd /lib/modules/%{kernelversion}/kernel/drivers/net
	if [ -f "ixgbe/ixgbe.ko" ]; then
		cp ixgbe/ixgbe.ko ixgbe/ixgbe.ko.bak
	fi
	if [ -f "e1000/e1000.ko" ]; then
		cp e1000/e1000.ko e1000/e1000.ko.bak
	fi
%post
	depmod -a
	rmmod ixgbe
	modprobe ixgbe
	rmmod e1000
	modprobe e1000
	chmod 755 /etc/efvpn/*
	chmod u+s /usr/sbin/efvpnd /usr/sbin/efvpn /usr/sbin/server_conf
	chmod u+s /sbin/ifconfig /sbin/ip /bin/touch /usr/sbin/ntpdate /usr/local/sbin/dhcpd
	chmod 777 /var/www/html/efvpn/Home/Runtime/Cache
	rm -rf /var/www/html/efvpn/Home/Runtime/Cache/*
	/usr/sbin/server_conf --dhcp 192.168.1.1,255.255.255.0,192.168.1.2,192.168.1.254
	chkconfig --add efvpnd
	chkconfig efvpnd on
	echo "ntpdate 0.centos.pool.ntp.org" > /etc/efvpn/update_time
	record=`crontab -l | grep efvpn | grep update_time | wc -l`
	if [ $record -eq 0 ]
	then
		( crontab -l; echo "* */1 * * * /bin/sh /etc/efvpn/update_time > /dev/null 2>&1" ) | crontab
	fi
	service efvpnd start
%preun
	/usr/sbin/efvpnd -s
	sleep 1
	chkconfig --del efvpnd
%postun
	cd /lib/modules/%{kernelversion}/kernel/drivers/net
	if [ -f "ixgbe/ixgbe.ko.bak" ]; then
		mv ixgbe/ixgbe.ko.bak ixgbe/ixgbe.ko
	fi
	if [ -f "e1000/e1000.ko.bak" ]; then
		mv e1000/e1000.ko.bak e1000/e1000.ko
	fi
	depmod -a
	rmmod ixgbe
	modprobe ixgbe
	rmmod e1000
	modprobe e1000
	rmmod netmap_lin
%files
	%defattr(-,root,root)
	/etc/efvpn
	/etc/init.d/efvpnd
	/usr
	/lib/modules/%{kernelversion}/kernel/drivers/net/netmap_lin.ko
	/lib/modules/%{kernelversion}/kernel/drivers/net/ixgbe/ixgbe.ko
	/lib/modules/%{kernelversion}/kernel/drivers/net/e1000/e1000.ko
	/var/www/html/efvpn

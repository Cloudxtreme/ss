#!/bin/sh

function install_lib()
{
	cd $home_dir
	tar -jxvf libnfnetlink-1.0.1.tar.bz2
	cd libnfnetlink-1.0.1
	./configure && make clean && make && make install

	cd $home_dir
	tar -jxvf libmnl-1.0.3.tar.bz2
	cd libmnl-1.0.3
	./configure && make clean && make && make install
	
	cd $home_dir
	tar -jxvf libnetfilter_conntrack-1.0.2.tar.bz2
	cd libnetfilter_conntrack-1.0.2
	./configure && make clean && make && make install

	cd $home_dir
	tar -jxvf libnetfilter_log-1.0.1.tar.bz2
	cd libnetfilter_log-1.0.1
	./configure && make clean && make && make install

	cd $home_dir
	tar -jxvf libnetfilter_acct-1.0.1.tar.bz2
	cd libnetfilter_acct-1.0.1
	./configure && make clean && make && make install

	cd $home_dir
	tar -zxvf libpcap-1.3.0.tar.gz
	cd libpcap-1.3.0
	./configure && make clean && make && make install
}
function install_ulog()
{
	cd $home_dir
	tar -jxvf ulogd-2.0.1.tar.bz2
	cd ulogd-2.0.1
	./configure && make clean && make && make install
	cp ulogd.conf /usr/local/etc
}

export PKG_CONFIG_PATH=/usr/local/lib/pkgconfig:$PKG_CONFIG_PATH
home_dir=`pwd`
install_lib
install_ulog

#!/bin/sh

function install_netmap()
{
	echo "installing netmap..."

	cd $capture_dir/netmap/LINUX
	make clean
	make KSRC=$ksrc
	rm ./ixgbe/ixgbe.ko
	sed -i 's/ixgbe_vlan_strip_enable(adapter);/ixgbe_vlan_strip_disable(adapter);/g' ./ixgbe/ixgbe_main.c
	make
	cp netmap_lin.ko $home_dir/module/netmap_lin.ko
	cp ixgbe/ixgbe.ko $home_dir/module/netmap_ixgbe.ko
	cp e1000/e1000.ko $home_dir/module/netmap_e1000.ko

	#cp $capture_dir/netmap/sys/net/* /usr/local/include
	cp $capture_dir/netmap/sys/net/* /$home_dir/inc

	cp netmap_lin.ko /lib/modules/`uname -r`/kernel/drivers/net/
	cp ixgbe/ixgbe.ko /lib/modules/`uname -r`/kernel/drivers/net/ixgbe/
	cp e1000/e1000.ko /lib/modules/`uname -r`/kernel/drivers/net/e1000/
	depmod -eF /boot/System.map-`uname -r` -A
}

function install_pfring()
{
	echo "installing pfring..."

	cd $capture_dir/pf_ring/drivers/PF_RING_aware/broadcom/netxtreme2-7.0.36/bnx2/src
	make clean && make
	cp bnx2.ko $home_dir/module/pfring_bnx2.ko

	cd $capture_dir/pf_ring/kernel
	make clean && make
	cp pf_ring.ko $home_dir/module/pf_ring.ko
	cp linux/pf_ring.h $home_dir/inc/linux

	cd $capture_dir/pf_ring/userland/lib
	chmod 755 configure
	./configure && make clean && make #&& make install
	cp pfring.h $home_dir/inc
	cp libpfring.a $home_dir/lib
	cd $capture_dir/pf_ring/userland/libpcap
	chmod 755 configure
	chmod 755 runlex.sh
	./configure && make clean && make #&& make install
	cp libpcap.a $home_dir/lib

}

function install_efio()
{
	cd $home_dir/efio
	aclocal
	automake
	autoconf
	automake --add-missing
	./configure && make && make install
}

function install_efnet()
{
	cd $home_dir/efnet
	aclocal
	automake
	autoconf
	automake --add-missing
	./configure && make && make install
}

function install_efext()
{
	cd $home_dir/efext
	aclocal
	automake
	autoconf
	automake --add-missing
	./configure && make && make install
}

function install_efapp()
{
	cd $home_dir/efapp
    echo "${backend},${outbound},${manager}" > ./efvpn.dev
	make clean
	make all
	make install
}

function install_dhcpd()
{
	cd $home_dir/tools
	tar -zxvf dhcp-4.2.2.tar.gz
	cd dhcp-4.2.2
	./configure
	make && make install
}

if [[ -z $1 ]]; then
	echo "please input kernel source path!"
	exit 0
fi

ksrc=$1
#backend=$2
#outbound=$3
#manager=$4
home_dir=`pwd`
capture_dir=$home_dir/efio/iobase/capture
mkdir -p $home_dir/module
mkdir -p $home_dir/inc/linux
mkdir -p $home_dir/lib

install_netmap
install_pfring
install_efio
install_efnet
install_efext
#install_dhcpd
#install_efapp

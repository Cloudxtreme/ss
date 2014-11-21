#!/bin/sh

function install_netmap()
{
	echo "installing netmap..."

	cd $home_dir/netmap/LINUX
	make clean
	make KSRC=$ksrc
	cp netmap_lin.ko $home_dir/module/netmap_lin.ko
	cp ixgbe/ixgbe.ko $home_dir/module/netmap_ixgbe.ko
	cp e1000/e1000.ko $home_dir/module/netmap_e1000.ko

	cp $home_dir/netmap/sys/net/* /usr/local/include
}

function install_pfring()
{
	echo "installing pfring..."

	cd $home_dir/pf_ring/drivers/PF_RING_aware/broadcom/netxtreme2-7.0.36/bnx2/src
	make clean && make
	cp bnx2.ko $home_dir/module/pfring_bnx2.ko

	cd $home_dir/pf_ring/kernel
	make clean && make
	cp pf_ring.ko $home_dir/module/pf_ring.ko
	cp linux/pf_ring.h /usr/include/linux

	cd $home_dir/pf_ring/userland/lib
	chmod 755 configure
	./configure && make clean && make && make install
	cd $home_dir/pf_ring/userland/libpcap
	chmod 755 configure
	chmod 755 runlex.sh
	./configure && make clean && make && make install
}

if [[ -z $1 ]]; then
	echo "please input kernel source path!"
	exit 0
fi

ksrc=$1
home_dir=`pwd`
mkdir -p module

install_netmap
install_pfring

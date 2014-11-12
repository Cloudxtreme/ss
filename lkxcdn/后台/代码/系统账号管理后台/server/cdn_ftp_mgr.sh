#!/bin/sh
opcode=$1
clientname=$2
clientpass=$3

case "$opcode" in
add)
	echo "add"
	useradd $clientname -d /var/ftp/pub/$clientname -s /sbin/nologin
	echo "$clientname:$clientpass"|chpasswd
	;;
del)
	echo "del"
	userdel -rf $clientname
	;;

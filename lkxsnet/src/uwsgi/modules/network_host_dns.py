#!/usr/bin/env python
#coding=utf-8
import commands
import os
import sys
import time
import json
import urllib

args = {}

####################################################
def init():
	global args
	global result

	temp = sys.argv[1]
	temp = temp.split('&')
	for info in temp:
		#print info	
		ks = info.split('=')
		args[ks[0]] = ks[1]
	#print args


####################################################
def get_hostname():
	global args
	global json_result

	ret = commands.getstatusoutput('hostname')
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def set_hostname():
	global args
	global json_result

	hostname = urllib.unquote(args['hostname'])

	cmd = 'hostname ' + hostname
	ret = commands.getstatusoutput(cmd)

	cmd = "sed -i '/HOSTNAME/d' /etc/sysconfig/network"
	ret = commands.getstatusoutput(cmd)

	cfg = '/etc/sysconfig/network'
	cmd = """echo "HOSTNAME=%s" >> %s """%(hostname, cfg)
	ret = commands.getstatusoutput(cmd)

	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def get_nameserver():
	global args
	global json_result

	res = []
	ret = commands.getstatusoutput('egrep "^nameserver" /etc/resolv.conf')
	data = ret[1].split("\n")
	for line in data:
		ns = line.split()[1]
		res.append(ns)
	json_result['ret'] = ret[0]
	json_result['result'] = res

####################################################
def set_nameserver():
	global args
	global json_result

	ns1 = urllib.unquote(args['ns1'])
	ns2 = urllib.unquote(args['ns2'])
	ns3 = urllib.unquote(args['ns3'])

	cmd = "sed -i '/^nameserver/d' /etc/resolv.conf"
	ret = commands.getstatusoutput(cmd)

	cfg = '/etc/resolv.conf'
	cmd = """echo "nameserver %s" >> %s && echo "nameserver %s" >> %s && echo "nameserver %s" >> %s """%(ns1, cfg, ns2, cfg, ns3, cfg)
	ret = commands.getstatusoutput(cmd)

	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
if __name__ == '__main__':

	json_result = {}
	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {'IPAddr':'IP地址', 'Hostname':'域名'}

	init()

	if args['opt'] == 'get_hostname':
		get_hostname()
	elif args['opt'] == 'set_hostname':
		set_hostname()
	elif args['opt'] == 'get_nameserver':
		get_nameserver()
	elif args['opt'] == 'set_nameserver':
		set_nameserver()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


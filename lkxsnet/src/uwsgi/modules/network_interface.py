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
def show_interface():
	global json_result

	ret = commands.getstatusoutput('/etc/init.d/network status')
	lines = ret[1].split("\n")
	res = []
	netdevs = {}

	#config_devs
	cdevs = lines[1].split()
	#active_devs
	adevs = lines[3].split()
	for dev in cdevs:
		netdevs[dev] = {}
		if adevs.index(dev) >= 0:
			netdevs[dev]['STATUS'] = 'up'
		else:
			netdevs[dev]['STATUS'] = 'down'

	for netdev in netdevs:
		cfg = '/etc/sysconfig/network-scripts/ifcfg-' + netdev
		cmd = """cat %s"""%(cfg)
		ret = commands.getstatusoutput(cmd)
		if ret[0] != 0:
			continue
		data = ret[1].split("\n")
		for line in data:
			line = line.strip()
			if line[0] == '#':
				continue
			kv = line.split('=')
			kv[0] = kv[0].strip()
			kv[1] = kv[1].strip()
			netdevs[netdev][kv[0]] = kv[1]

	for value in netdevs.values():
		res.append(value)

	json_result['ret'] = ret[0]
	json_result['result'] = res

####################################################
def set_interface():
	global args
	global json_result

	interface = args['interface']
	lines = []
	cfg = '/etc/sysconfig/network-scripts/ifcfg-' + interface
	cmd = 'cat ' + cfg
	ret = commands.getstatusoutput(cmd)
	data = ret[1].split("\n")
	for line in data:
		#print line
		line = line.strip()
		if line[0] == '#':
			lines.append(line+"\n")
			continue
		kv = line.split('=')
		kv[0] = kv[0].strip()
		kv[1] = kv[1].strip()
		if args.has_key(kv[0]):
			new_line = "%s=%s"%(kv[0], urllib.unquote(args[kv[0]]))
			lines.append(new_line+"\n")
		else:
			lines.append(line+"\n")
	#print lines

	json_result['ret'] = 0

	try:
		file = open(cfg, 'w')
		file.writelines(lines)
		file.close()
	except IOError as e:
		json_result['ret'] = 1
		json_result['error'] = "I/O error({0}): {1}".format(e.errno, e.strerror)
	except:
		json_result['error'] = "Unexpected error:", sys.exc_info()[0]

####################################################
def restart_interface():
	global args
	global json_result

	interface = args['interface']
	cmd = "ifdown %s ; ifup %s"%(interface, interface)
	ret = commands.getstatusoutput(cmd)

	json_result['ret'] = ret[0]
	json_result['error'] = ret[1]

####################################################
if __name__ == '__main__':

	json_result = {}
	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {'NETWORK':'网络', 'IPADDR':'IP地址',
							'BROADCAST':'广播地址', 'NETMASK':'掩码','TYPE':'类型',
							'DEVICE':'设备', 'ONBOOT':'开机启动', 'NAME':'名称',
							'STATUS':'状态', 'up':'启用', 'down':'停用', 'GATEWAY':'网关', 'HWADDR':'MAC地址'}

	init()

	if args['opt'] == 'show_interface':
		show_interface()
	elif args['opt'] == 'set_interface':
		set_interface()
	elif args['opt'] == 'restart_interface':
		restart_interface()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


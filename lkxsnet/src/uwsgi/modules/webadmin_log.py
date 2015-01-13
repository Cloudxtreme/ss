#!/usr/bin/env python
#coding=utf-8
import commands
import os
import sys
import time
import json
import urllib
import datetime

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
def show_log_list():
	global args
	global json_result

	infos = []
	cmd = 'ls -l /var/log/|grep uwsgi'
	ret = commands.getstatusoutput(cmd)
	if ret[0] == 0:
		lines = ret[1].split("\n")
		for line in lines:
			info = line.split()
			#print info
			size = info[4]
			log = info[8]
			infos.append({'log':log, 'size':size})

	json_result['ret'] = ret[0]
	json_result['result'] = infos

####################################################
def show_mod_log():
	global args
	global json_result

	mod = urllib.unquote(args['mod'])
	log = urllib.unquote(args['log'])
	cmd = 'grep %s /var/log/%s'%(mod, log)
	ret = commands.getstatusoutput(cmd)
	if ret[0] == 0:
		lines = ret[1].split("\n")
	

	json_result['ret'] = ret[0]
	json_result['result'] = lines

####################################################
def show_module_list():
	global args
	global json_result

	modules = [	{'mod':'network_host_dns.py', 'desc':'网络管理-主机域名'},
				{'mod':'webadmin_log.py', 'desc':'平台管理-日志管理'},
                {'mod':'network_route_gateway.py', 'desc':'网络管理-路由网关'},
                {'mod':'system_users_groups.py', 'desc':'系统管理-账号管理'},
                {'mod':'hardware_date_time.py', 'desc':'硬件-系统时间'},
                {'mod':'network_interface.py', 'desc':'网络管理-网络接口'},
                {'mod':'system_disk.py', 'desc':'系统管理-磁盘管理'},
                {'mod':'system_software.py', 'desc':'系统管理-软件管理'},
                {'mod':'system_ps.py', 'desc':'系统管理-进程管理'},
                {'mod':'webadmin_admin.py', 'desc':'平台管理-账号管理'},
                {'mod':'system_log.py', 'desc':'系统管理-日志管理'},
                {'mod':'network_firewall.py', 'desc':'网络管理-防火墙'},
                {'mod':'hardware_reboot_shutdown.py', 'desc':'硬件-重启关机'},
                {'mod':'network_host_addr.py', 'desc':'网络管理-主机地址'}]

	json_result['ret'] = 0
	json_result['result'] = modules

####################################################
if __name__ == '__main__':

	json_result = {}
	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {}

	init()

	if args['opt'] == 'show_log_list':
		show_log_list()
	elif args['opt'] == 'show_module_list':
		show_module_list()
	elif args['opt'] == 'show_mod_log':
		show_mod_log()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


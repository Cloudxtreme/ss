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
def show_host_addr():
	global args
	global json_result

	res = []
	cfg = '/etc/hosts'
	cmd = 'cat ' + cfg
	ret = commands.getstatusoutput(cmd)
	data = ret[1].split("\n")
	id = 1
	for line in data:
		line = line.strip()
		if line[0] == '#':
			continue
		temp = line.split()
		ipaddr = temp[0].strip()
		hostname = line[len(ipaddr):].strip()
		res.append({'ID':id, 'IPAddr':ipaddr, 'Hostname':hostname})
		id = id + 1

	json_result['ret'] = 0
	json_result['result'] = res

####################################################
def edit_host_addr():
	global args
	global json_result

	eid = int(args['id'])
	eip = args['ip']
	ehostname = urllib.unquote(args['hostname'])

	cfg = '/etc/hosts'
	cmd = 'cat ' + cfg
	ret = commands.getstatusoutput(cmd)
	data = ret[1].split("\n")
	lines = []
	id = 1
	for line in data:
		line = line.strip()
		if line[0] == '#':
			continue
		if id == eid:
			newline = "%s\t\t%s"%(eip, ehostname)
			lines.append(newline+"\n")
		else:
			lines.append(line+"\n")
		id = id + 1

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
def add_host_addr():
	global args
	global json_result

	eip = args['ip']
	ehostname = urllib.unquote(args['hostname'])

	cfg = '/etc/hosts'
	cmd = """echo "%s\t\t%s" >> %s """%(eip, ehostname, cfg)
	ret = commands.getstatusoutput(cmd)
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def del_host_addr():
	global args
	global json_result

	did = int(args['id'])

	cfg = '/etc/hosts'
	cmd = 'cat ' + cfg
	ret = commands.getstatusoutput(cmd)
	data = ret[1].split("\n")
	lines = []
	id = 1
	for line in data:
		line = line.strip()
		if line[0] == '#':
			continue
		if id != did:
			lines.append(line+"\n")
		id = id + 1

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
if __name__ == '__main__':

	json_result = {}
	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {'IPAddr':'IP地址', 'Hostname':'域名'}

	init()

	if args['opt'] == 'show_host_addr':
		show_host_addr()
	elif args['opt'] == 'edit_host_addr':
		edit_host_addr()
	elif args['opt'] == 'add_host_addr':
		add_host_addr()
	elif args['opt'] == 'del_host_addr':
		del_host_addr()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


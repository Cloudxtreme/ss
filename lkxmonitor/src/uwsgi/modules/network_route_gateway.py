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
def show_route():
	global args
	global json_result

	res = []
	ret = commands.getstatusoutput('route -n')
	data = ret[1].split("\n")
	cols = data[1].split()
	#print cols
	for line in data[2:]:
		temp = line.split()
		info = {}
		for i in range(len(temp)):
			info[cols[i]] = temp[i]
		#print info
		res.append(info)
	json_result['ret'] = ret[0]
	json_result['result'] = res

####################################################
def add_route():
	global args
	global json_result

	dst = args['dst']
	netmask = args['netmask']
	viatype = args['viatype']
	via = args['via']

	if dst != 'default':
		dst = '-net ' + dst

	if netmask == 'default':
		netmask = 'netmask 0.0.0.0'
	else:
		netmask = 'netmask ' + netmask

	if viatype == 'interface':
		via = 'dev ' + via
	else:
		via = 'gw ' + via

	cmd = """route add %s %s %s"""%(dst, netmask, via)
	#print cmd
	ret = commands.getstatusoutput(cmd)
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def del_route():
	global args
	global json_result

	dst = args['dst']
	netmask = args['netmask']

	if dst != 'default':
		dst = '-net ' + dst

	if netmask == 'default':
		netmask = 'netmask 0.0.0.0'
	else:
		netmask = 'netmask ' + netmask

	cmd = """route del %s %s"""%(dst, netmask)
	#print cmd
	ret = commands.getstatusoutput(cmd)
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
if __name__ == '__main__':

	json_result = {}
	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {}

	init()

	if args['opt'] == 'show_route':
		show_route()
	elif args['opt'] == 'add_route':
		add_route()
	elif args['opt'] == 'del_route':
		del_route()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


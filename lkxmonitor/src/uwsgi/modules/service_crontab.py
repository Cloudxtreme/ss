#!/usr/bin/env python
#coding=utf-8
import sys
import os
import web
import time
import json
import urllib
import commands

args = {}
json_result = {}

####################################################
def init():
	global args

	temp = sys.argv[1]
	temp = temp.split('&')
	for info in temp:
		#print info	
		ks = info.split('=')
		args[ks[0]] = ks[1]
	#print args

# sed '3d' -i conf
# sed '3ixxxxxx' -i conf

####################################################
def add():
	global json_result

	min = urllib.unquote(args['min'])
	hour = urllib.unquote(args['hour'])
	date = urllib.unquote(args['date'])
	month = urllib.unquote(args['month'])
	week = urllib.unquote(args['week'])
	cmd = urllib.unquote(args['cmd'])
	cmd = """(crontab -l;echo "%s %s %s %s %s %s")|crontab"""%(min, hour, date, month, week, cmd)
	#print cmd
	ret = commands.getstatusoutput(cmd)
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def delete():
	global json_result

	id = int(args['id'])
	ret = commands.getstatusoutput('crontab -l')
	lines = ret[1].split("\n")
	data = []
	idx = -1
	for line in lines:
		idx += 1
		if id == idx:
			line = line.strip()
			info = line.split()
			if len(info) < 6:
				break
			#print info
			pos = line.find(info[5])
			path = line[pos:]
			cmd = """crontab -l | grep -v "%s" | crontab"""%(path)
			#print cmd
			ret = commands.getstatusoutput(cmd)
			break

	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def list():
	global json_result

	ret = commands.getstatusoutput('crontab -l')
	lines = ret[1].split("\n")
	data = []
	idx = -1
	for line in lines:
		idx += 1
		line = line.strip()
		if line[0] == '#':
			continue
		info = line.split()
		if len(info) < 6:
			continue 
		#print info
		pos = line.find(info[5])
		#print pos 
		x = {'id':idx, 'min':info[0], 'hour':info[1], 'date':info[2], 'month':info[3], 'week':info[4], 'cmd':line[pos:]}
		data.append(x)
	json_result['ret'] = ret[0]
	json_result['result'] = data

if __name__ == '__main__':

	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {'min':'分钟', 'hour':'小时', 'date':'日期', 'month':'月份', 'week':'星期', 'cmd':'命令'}

	init()

	if args['opt'] == 'list':
		list()
	elif args['opt'] == 'delete':
		delete()
	elif args['opt'] == 'add':
		add()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)



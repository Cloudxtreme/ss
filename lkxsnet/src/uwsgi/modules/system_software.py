#!/usr/bin/env python
#coding=utf-8
import commands
import random
import os
import sys
import time
import json
import urllib

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

####################################################
def show_installed():
	global json_result

	list = []
	is_start = 0
	ret = commands.getstatusoutput('yum list installed')
	lines = ret[1].split("\n")
	for line in lines:
		if line == 'Installed Packages':
			is_start = 1
			continue
		if is_start != 1:
			continue
		temp = line.split()
		if len(temp) < 2:
			continue
		#print line
		software = temp[0]
		version = temp[1]
		list.append({'software':software, 'version':version})
	#print logs
	json_result['ret'] = 0
	json_result['result'] = list

####################################################
def show_updates():
	global json_result

	list = []
	is_start = 0
	ret = commands.getstatusoutput('yum list updates')
	lines = ret[1].split("\n")
	for line in lines:
		if line == 'Updated Packages':
			is_start = 1
			continue
		if is_start != 1:
			continue
		temp = line.split()
		if len(temp) < 2:
			continue
		#print line
		software = temp[0]
		version = temp[1]
		list.append({'software':software, 'version':version})
	#print logs
	json_result['ret'] = 0
	json_result['result'] = list

####################################################
def show_info():
	global json_result

	if not args.has_key('software'):
		return
	software = args['software']
	is_start = 0
	rets = []
	cmd = "yum info %s"%(software)
	ret = commands.getstatusoutput(cmd)
	lines = ret[1].split("\n")
	for line in lines:
		if line == '':
			continue
		if line.startswith("Load"):
			continue
		if line[0] != ' ':
			is_start = 1
		#print line
		if is_start == 1:
			if line[0] == ' ':
				line = line.replace(' ', '&nbsp;')
			rets.append(line)
			
	json_result['ret'] = ret[0]
	json_result['result'] = rets

####################################################
def search_software():
	global json_result

	if not args.has_key('software'):
		return
	software = args['software']
	is_start = 0
	rets = []
	cmd = "yum search %s"%(software)
	ret = commands.getstatusoutput(cmd)
	lines = ret[1].split("\n")
	for line in lines:
		if line == '':
			continue
		if line.startswith('=============='):
			is_start = 1
			continue
		if line[0] == ' ':
			is_start = 0
			continue
		#print line
		if is_start == 1:
			temp = line.split(':')
			match = temp[0].strip()
			detail = temp[1].strip()
			rets.append({'match':match, 'detail':detail})
			
	json_result['ret'] = ret[0]
	json_result['result'] = rets


####################################################
def install_software():
	global json_result

	if not args.has_key('software'):
		return
	software = args['software']
	taskid = random.randint(1,1000000000)
	cmd = "/opt/uwsgi/modules/system_yum.sh install %s %s &"%(software, taskid)
	os.system(cmd)
	json_result['ret'] = 0
	json_result['result'] = {'taskid':taskid}

####################################################
def erase_software():
	global json_result

	if not args.has_key('software'):
		return
	software = args['software']
	taskid = random.randint(1,1000000000)
	cmd = "/opt/uwsgi/modules/system_yum.sh erase %s %s &"%(software, taskid)
	os.system(cmd)
	json_result['ret'] = 0
	json_result['result'] = {'taskid':taskid}

####################################################
def update_software():
	global json_result

	if not args.has_key('software'):
		return
	software = args['software']
	taskid = random.randint(1,1000000000)
	cmd = "/opt/uwsgi/modules/system_yum.sh update %s %s &"%(software, taskid)
	os.system(cmd)
	json_result['ret'] = 0
	json_result['result'] = {'taskid':taskid}

####################################################
def show_tasks():
	global json_result

	res = []
	try:
		f = open('/opt/uwsgi/modules/yum.log', 'r')
		lines = f.readlines()
		f.close()
		for line in lines:
			temp = line.split()
			opt = temp[1]
			software = temp[2]
			taskid = temp[3]
			starttime = temp[4]
			endtime = temp[5]
			ret = temp[6]
			res.append({'opt':opt,'software':software,'taskid':taskid,'starttime':starttime,'endtime':endtime,'ret':ret})
	except Exception, e:
		pass
	json_result['ret'] = 0
	json_result['result'] = res

####################################################
def show_taskid():
	global json_result

	if not args.has_key('taskid'):
		return
	taskid = args['taskid']
	try:
		f = open('/tmp/yum.'+taskid+'.log', 'r')
		lines = f.readlines()
		f.close()
	except Exception, e:
		pass
	json_result['ret'] = 0
	json_result['result'] = lines

####################################################
if __name__ == '__main__':

	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {'software':'软件', 'version':'版本',
							'match':'匹配', 'detail':'详情',
							'taskid':'任务ID', 'starttime':'开始时间', 'endtime':'结束时间',
							'install':'安装', 'erase':'删除', 'update':'更新',
							'opt':'操作', 'ret':'返回值'}

	init()

	if args['opt'] == 'show_installed':
		show_installed()
	elif args['opt'] == 'show_updates':
		show_updates()
	elif args['opt'] == 'show_info':
		show_info()
	elif args['opt'] == 'search_software':
		search_software()
	elif args['opt'] == 'install_software':
		install_software()
	elif args['opt'] == 'erase_software':
		erase_software()
	elif args['opt'] == 'update_software':
		update_software()
	elif args['opt'] == 'show_tasks':
		show_tasks()
	elif args['opt'] == 'show_taskid':
		show_taskid()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


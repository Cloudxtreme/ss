#!/usr/bin/env python
#coding=utf-8
import commands
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
def show_logs():
	global json_result

	logs = []
	start_rules = 0
	ret = commands.getstatusoutput('cat /etc/rsyslog.conf')
	lines = ret[1].split("\n")
	for line in lines:
		line = line.strip()
		if line == '':
			continue
		if line == '#### RULES ####':
			start_rules = 1
		if line == '#### WEBADMIN_RULES_BEGIN ####':
			start_rules = 1
			continue
		if line == '#### WEBADMIN_RULES_END ####':
			start_rules = 0
			continue
		if line[0] == '#':
			continue
		if start_rules:
			temp = line.split()
			logdst = temp[1]
			logrule = temp[0]
			logsize = 0
			try:
				fileinfo = os.stat(logdst)
				logsize = fileinfo.st_size
			except OSError, e:
				pass
			logrule = temp[0]
			if logdst[0] == '-':
				logdst = logdst[1:]
			logs.append({'log':logdst, 'rule':logrule, 'size':logsize})
	#print logs
	json_result['ret'] = 0
	json_result['result'] = logs

####################################################
def tail_log():
	global json_result

	log = urllib.unquote(args['log'])
	line = args['line']
	grep = ''

	if len(log) == 0 or len(line) == 0:
		return
	if args.has_key('grep') and args['grep'] != '':
		grep = args['grep']
		cmd = "tail -n %s %s | grep %s"%(line, log, grep)
	else:
		cmd = "tail -n %s %s"%(line, log)

	ret = commands.getstatusoutput(cmd)
	lines = ret[1].split("\n")
	json_result['ret'] = 0
	json_result['result'] = lines

####################################################
if __name__ == '__main__':

	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {'log':'日志', 'rule':'规则', 'size':'大小'}

	init()

	if args['opt'] == 'show_logs':
		show_logs()
	if args['opt'] == 'tail_log':
		tail_log()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


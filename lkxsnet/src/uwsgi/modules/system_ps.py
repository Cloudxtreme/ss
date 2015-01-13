#!/usr/bin/env python
import commands
import os
import sys
import time
import json
import urllib

args = {}
ps = []
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
def get_ps():
	global ps
	global json_result

	_ps = {}
	pids = []
	ret = commands.getstatusoutput('ps -ef')
	lines = ret[1].split("\n")
	ps_cols = []
	for line in lines:
		if len(ps_cols) == 0:
			ps_cols = line.split()
			continue
		temp = line.split()
		pid = int(temp[1])
		pids.append(pid)
		_ps[pid] = {}
		for i in range(len(ps_cols)-1):
			_ps[pid][ps_cols[i]] = temp[i]
		_ps[pid][ps_cols[i+1]] = line[line.index(temp[i])+len(temp[i])+1:]
		#print pid, ps[pid]

	pids.sort()
	for pid in pids:
		ps.append(_ps[pid])
	#print ps.iteritems()
	#print ps
	json_result['ret'] = ret[0]
	json_result['result'] = ps


####################################################
def kill_ps():
	global args
	global json_result

	pid = args['pid']
	ret = commands.getstatusoutput("kill -9 %s"%(pid))
	json_result['ret'] = ret[0]
	json_result['error'] = ret[1]

####################################################
def run_ps():
	global args
	global json_result

	cmd = urllib.unquote(args['cmd'])
	os.system(cmd)
	json_result['ret'] = 0
	json_result['error'] = ''

####################################################
if __name__ == '__main__':

	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''

	init()

	if args['opt'] == 'showps':
		get_ps()
	elif args['opt'] == 'killps':
		kill_ps()
	elif args['opt'] == 'runps':
		run_ps()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


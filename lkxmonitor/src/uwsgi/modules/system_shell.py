#!/usr/bin/env python
#coding=utf-8
import commands
import os
import sys
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
def run_cmd():
	global json_result

	cmd = urllib.unquote(args['cmd'])
	ret = commands.getstatusoutput(cmd)
	data = ret[1].split("\n")
	json_result['ret'] = ret[0]
	json_result['result'] = data

if __name__ == '__main__':

	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {}

	init()

	if args['opt'] == 'run_cmd':
		run_cmd()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


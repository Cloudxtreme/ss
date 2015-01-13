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
def reboot():
	global args
	global json_result

	
	ret = commands.getstatusoutput('reboot')

	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def shutdown():
	global args
	global json_result

	ret = commands.getstatusoutput('halt')
	
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

	if args['opt'] == 'reboot':
		reboot()
	elif args['opt'] == 'shutdown':
		shutdown()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


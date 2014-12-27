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
def set_passwd():
	global args
	global json_result

	passwd = args['passwd']
	cmd = "/opt/uwsgi/modules/htpasswd.py -c -b /opt/uwsgi/modules/htpasswd admin %s"%(passwd)
	ret = commands.getstatusoutput(cmd)
	if ret[0] == 0:
		cmd = 'cp -rf /opt/uwsgi/modules/htpasswd /opt/nginx/conf/htpasswd && /opt/nginx/sbin/nginx -s reload'
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

	if args['opt'] == 'set_passwd':
		set_passwd()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


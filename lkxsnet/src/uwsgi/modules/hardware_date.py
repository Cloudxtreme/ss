#!/usr/bin/env python 
import os
import sys
import json
import time

args = {}
result = {}

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

	result['ret'] = 0
	result['error'] = ''
	result['result'] = ''

if __name__ == '__main__':

	init()

	if args['opt'] == 'showdate':
		tt = time.localtime()
		tts = """%04d-%02d-%02d %02d:%02d:%02d"""%(tt.tm_year, tt.tm_mon, tt.tm_mday, tt.tm_hour, tt.tm_min, tt.tm_sec)
		result['result'] = tts
		print json.dumps(result)		

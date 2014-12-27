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
def get_sys_date_time():
	global args
	global json_result

	tt = time.localtime()
	tts = """%04d-%02d-%02d %02d:%02d:%02d"""%(tt.tm_year, tt.tm_mon, tt.tm_mday, tt.tm_hour, tt.tm_min, tt.tm_sec)
	tts = tts.split()
	
	json_result['ret'] = 0
	json_result['result'] = {'date':tts[0], 'time':tts[1]}

####################################################
def set_sys_date_time():
	global args
	global json_result

	date = urllib.unquote(args['date'])
	time = urllib.unquote(args['time'])

	if date != '' and time != '':
		cmd = ("date -s \"%s %s\"")%(date, time)
	elif date != '' and time == '':
		cmd = ("data +%F -s \"%s\"")%(date)
	elif date == '' and time != '':
		cmd = ("data +%T -s \"%s\"")%(time)
	else:
		return

	ret = commands.getstatusoutput(cmd)
	
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def datetime_systohc():
	global args
	global json_result

	ret = commands.getstatusoutput('hwclock --systohc')
	
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def get_hw_date_time():
	global args
	global json_result

	#['Fri', 'Sep', '13', '03:19:16', '2013', '-0.898777', 'seconds']
	ret = commands.getstatusoutput('hwclock --utc')
	temp = ret[1].split()
	#month day year
	date = "%s %s %s"%(temp[2], temp[1], temp[3])
	time = temp[4]
		
	x = datetime.datetime.strptime(date,'%b %d %Y');
	date = (str(x)).split()[0]

	json_result['ret'] = 0
	json_result['result'] = {'date':date, 'time':time}

####################################################
def set_hw_date_time():
	global args
	global json_result

	date = urllib.unquote(args['date'])
	time = urllib.unquote(args['time'])

	if date != '' and time != '':
		cmd = ("date -s \"%s %s\"")%(date, time)
	elif date != '' and time == '':
		cmd = ("data +%F -s \"%s\"")%(date)
	elif date == '' and time != '':
		cmd = ("data +%T -s \"%s\"")%(time)
	else:
		return

	ret = commands.getstatusoutput(cmd)
	
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def datetime_hctosys():
	global args
	global json_result

	ret = commands.getstatusoutput('hwclock --hctosys')
	
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def get_ntpserver_list():
	global args
	global json_result

	json_result['ret'] = 0
	json_result['result'] = ['0.centos.pool.ntp.org', '1.centos.pool.ntp.org', '2.centos.pool.ntp.org']

####################################################
def ntpdate():
	global args
	global json_result

	ntpserver = args['ntpserver']
	ret = commands.getstatusoutput('/usr/sbin/ntpdate ' + ntpserver)
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def get_timezone_list():
	global args
	global json_result

	zones = []
	curzone = ''

	scandir = '/usr/share/zoneinfo/'
	for root, dirs, files in os.walk(scandir):
		if root == scandir:
			for file in files:
				zones.append(file)
				#print file
			continue
		subdir = root.replace(scandir, '')
		if subdir[0].islower():
			continue
		for file in files:
			temp = subdir + '/' + file
			zones.append(temp)	
			#print temp
		#break

	ret = commands.getstatusoutput('cat /etc/sysconfig/clock')
	data = ret[1].split('=')[1]
	curzone = data.replace('"', '')

	#print zones
	json_result['ret'] = 0
	json_result['result'] = {}
	json_result['result']['curzone'] = curzone
	json_result['result']['zones'] = zones

####################################################
def set_timezone():
	global args
	global json_result

	timezone = args['timezone']

	cmd = "echo \"ZONE=\"%s\"\" /etc/sysconfig/clock"%(timezone)
	ret = commands.getstatusoutput(cmd)

	#print zones
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

	if args['opt'] == 'get_sys_date_time':
		get_sys_date_time()
	elif args['opt'] == 'set_sys_date_time':
		set_sys_date_time()
	elif args['opt'] == 'datetime_systohc':
		datetime_systohc()
	elif args['opt'] == 'get_hw_date_time':
		get_hw_date_time()
	elif args['opt'] == 'datetime_hctosys':
		datetime_hctosys()
	elif args['opt'] == 'get_ntpserver_list':
		get_ntpserver_list()
	elif args['opt'] == 'ntpdate':
		ntpdate()
	elif args['opt'] == 'get_timezone_list':
		get_timezone_list()
	elif args['opt'] == 'set_timezone':
		set_timezone()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


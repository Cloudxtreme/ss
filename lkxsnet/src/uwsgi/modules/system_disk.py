#!/usr/bin/env python
#coding=utf-8
import platform
import os
import sys
import time
import json
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

####################################################
def get_fs_info(path):
	info = os.statvfs(path)
	total = round(float(info.f_bsize * info.f_blocks)/1024/1024/1024, 2)
	free = round(float(info.f_bsize * info.f_bavail)/1024/1024/1024, 2)
	used = total - free
	return {'total': total,
            'free': free,
            'used': used}

####################################################
def get_disk_info():	
	global json_result

	disk_info = []
	lines = open('/proc/mounts').readlines()
	for line in lines:
		if not line.startswith('/dev/'):
			continue
		temp = line.split()
		if len(temp) < 2:
			continue
		fs_info = get_fs_info(temp[1])
		disk_info.append({'path':temp[1], 'type':temp[2], 'fs_info':fs_info})
	json_result['ret'] = 0
	json_result['result'] = disk_info	

####################################################
def fs_view():	
	global json_result
	global args

	res = {}
	res['dirs'] = []
	res['files'] = []
	chroot = args['chroot']
	for root, dirs, files in os.walk(chroot):
		if root == chroot:
			for dir in dirs:
				if dir[0] != '.':
					res['dirs'].append(dir)
			for file in files:
				if file[0] != '.':
					res['files'].append(file)
		break

	json_result['ret'] = 0
	json_result['result'] = res

####################################################
if __name__ == '__main__':
	global json_result

	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {
		'disk_info' : '磁盘信息', 'fs_info' : '文件系统信息', 'path' : '路径', 
		'type' : '类型', 'total' : '总值', 'used' : '已使用', 'free' : '空余',
	}

	init()

	if args['opt'] == 'show_disk':
		get_disk_info()
	elif args['opt'] == 'fs_view':
		fs_view()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


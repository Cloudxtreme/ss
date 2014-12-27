#!/usr/bin/env python
#coding=utf-8
import platform
import os
import sys
import time
import json
import commands

args = {}
result = {}

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
def get_memory_info():
	global result

	memory_info = {}
	lines = open('/proc/meminfo').readlines()
	for line in lines:
		temp = line.split(':')
		key = temp[0]
		value = temp[1].split()[0]
		memory_info[key] = int(value)

	memory_info['MemUsed'] = memory_info['MemTotal'] - memory_info['MemFree'] - memory_info['Buffers'] - memory_info['Cached']
	memory_info['SwapUsed'] = memory_info['SwapTotal'] - memory_info['SwapFree']

	result['mem_info'] = {}
	result['mem_info']['mem_total'] = round(float(memory_info['MemTotal'])/1024, 2)
	result['mem_info']['swap_total'] = round(float(memory_info['SwapTotal'])/1024, 2)
	result['mem_info']['mem_used_per'] = mem_used_per = int(float(memory_info['MemUsed']) / float(memory_info['MemTotal']) * 100)
	result['mem_info']['swap_used_per'] = mem_swap_used_per = int(float(memory_info['SwapUsed']) / float(memory_info['SwapTotal']) * 100) 

####################################################
def get_proc_stat_cpu():
	lines = open('/proc/stat').readlines()
	ret = []
	for line in lines:
		temp = line.split()[1:]
		for v in temp:
			ret.append(int(v))
		break
	return ret

def get_cpu_info():
	global result

	cpu_info = {}
	#cpu base info
	cpu_info['base_info'] = {}
	lines = open('/proc/cpuinfo').readlines()
	for line in lines:
		temp = line.split(':')
		if len(temp) != 2:
			continue
		key = temp[0].strip()
		value = temp[1].strip()
		cpu_info[key] = value
		#print key, value
	#print cpu_info
	result['cpu_info'] = {}
	result['cpu_info']['model_name'] = cpu_info['model name']
	result['cpu_info']['vendor_id'] = cpu_info['vendor_id']
	result['cpu_info']['processor'] = int(cpu_info['processor']) + 1

	#cpu stat
	t1 = get_proc_stat_cpu()
	time.sleep(1)
	t2 = get_proc_stat_cpu()
	#print t1, t2
	total = sum(t2) - sum(t1)
	used = (sum(t2) - t2[3]) - (sum(t1) - t1[3])
	uper = int(float(used) / float(total) * 100)
	result['cpu_info']['cpu_used_per'] =  uper

####################################################
def get_fs_info(path):
	info = os.statvfs(path)
	total = round(float(info.f_bsize * info.f_blocks)/1024/1024/1024, 2)
	free = round(float(info.f_bsize * info.f_bavail)/1024/1024/1024, 2)
	used = total - free
	return {'total': total,
            'free': free,
            'used': used}

def get_disk_info():	
	global result

	disk_info = {}
	lines = open('/proc/mounts').readlines()
	for line in lines:
		if not line.startswith('/dev/'):
			continue
		temp = line.split()
		if len(temp) < 2:
			continue
		fs_info = get_fs_info(temp[1])
		disk_info[temp[0]] = {'path':temp[1], 'type':temp[2], 'fs_info':fs_info}
	result['disk_info'] = {}
	result['disk_info'] = disk_info	


####################################################
def get_os_info():
	global result
	result['os_info'] = {}
	result['os_info']['system'] = platform.system()
	result['os_info']['release'] = platform.release()
	result['os_info']['machine'] = platform.machine()
	info = platform.dist()
	result['os_info']['dist'] = "%s %s %s"%(info[0], info[1], info[2])

####################################################
def get_uptime():
	global result

	ret = commands.getstatusoutput('uptime')
	info = ret[1]
	result['uptime'] = info

####################################################
if __name__ == '__main__':
	global result

	json_result = {}
	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {
		'cpu_info' : 'CPU信息', 'cpu_used_per' : 'CPU使用率', 'vendor_id' : '供应商', 'model_name' : '型号', 'processor' : '处理器个数',
		'disk_info' : '磁盘信息', 'fs_info' : '文件系统信息', 'path' : '路径', 'type' : '类型', 'total' : '总值', 'used' : '已使用', 'free' : '空余',
		'mem_info' : '内存信息', 'swap_total' : '交互内存总值', 'mem_total' : '物理内存总值', 
		'swap_used_per' : '交换内存使用率', 'mem_used_per' : '物理内存使用率',
		'os_info' : '操作系统信息', 'system' : '系统', 'release' : '发行版', 'machine' : '机器', 'dist' : '发行版',
		'uptime' : '开机信息'
	}

	init()

	if args['opt'] != 'showinfo':
		json_result['ret'] = 1
		json_result['error'] = 'opt error!'
		print json.dumps(json_result)
		exit(1)

	get_os_info()
	
	get_memory_info()

	get_cpu_info()

	get_disk_info()

	get_uptime()

	json_result['ret'] = 0
	json_result['result'] = result

	print json.dumps(json_result)


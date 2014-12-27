#!/usr/bin/env python
import sys
import os
import time
import json
import zmq

interval = 5 * 60 #5 min

sid = sys.argv[1]

def snmpwalk_dev_list(cmd):
	idx = 1
	devs = {}
	proc = os.popen("snmpwalk -v 2c -c public 127.0.0.1 ifDescr|awk '{print $4}'")
	ret = proc.read()
	for dev in ret.split():
		if dev != '':
			devs[idx] = dev
			idx += 1
	#print devs
	return devs

def snmpwalk_dev_inout(cmd):
	res = {}
	proc = os.popen(cmd)
	ret = proc.read()
	for line in ret.split("\n"):
		if line == '':
			continue
		line = line.replace('IF-MIB::ifInOctets.', '')	
		line = line.replace('IF-MIB::ifOutOctets.', '')	
		line = line.replace('= Counter32: ', '')	
		if line == '':
			continue
		x = line.split()
		idx = int(x[0])
		value = int(x[1])
		res[idx] = value
	#print res
	return res

def get_proc_stat_cpu():
	lines = open('/proc/stat').readlines()
	ret = []
	for line in lines:
		temp = line.split()[1:]
		for v in temp:
			ret.append(int(v))
		break
	return ret

def get_memory_info():
    m = {}
    lines = open('/proc/meminfo').readlines()
    for line in lines:
        temp = line.split(':')
        key = temp[0]
        value = temp[1].split()[0]
        m[key] = int(value)
    muper = 100 - int(float(m['MemFree']) / float(m['MemTotal']) * 100)
    msuper = 100 - int(float(m['SwapFree']) / float(m['SwapTotal']) * 100)
    return muper, msuper

def get_disk_uper(path):
	info = os.statvfs(path)
	total = round(float(info.f_bsize * info.f_blocks)/1024/1024/1024, 2)
	free = round(float(info.f_bsize * info.f_bavail)/1024/1024/1024, 2)
	return 100 - int(float(free) / float(total) * 100)

def get_disk_info():	
	disk_info = {}
	lines = open('/proc/mounts').readlines()
	for line in lines:
		if not line.startswith('/dev/'):
			continue
		temp = line.split()
		if len(temp) < 2:
			continue
		disk_info[temp[1]] = get_disk_uper(temp[1])
	return disk_info

############# main ###############
try:
	context = zmq.Context()
	snmppush = context.socket(zmq.PUSH)
	snmppush.connect("tcp://61.142.208.98:5565")

	devs = snmpwalk_dev_list("snmpwalk -v 2c -c public 127.0.0.1 ifDescr|awk '{print $4}'")
	ins1 = outs1 = ins2 = outs2 = {}
	while True:
		#1.net dev in/out rate
		#############################################################################
		ins1 = snmpwalk_dev_inout("snmpwalk -v 2c -c public 127.0.0.1 ifinOctets")
		outs1 = snmpwalk_dev_inout("snmpwalk -v 2c -c public 127.0.0.1 ifoutOctets")
		#2.cpu
		#############################################################################
		cpu1 = get_proc_stat_cpu()

		#-----------------------
		time.sleep(interval)
		#-----------------------

		#1.net dev in/out rate
		#############################################################################
		pushdata = []
		ins2 = snmpwalk_dev_inout("snmpwalk -v 2c -c public 127.0.0.1 ifinOctets")
		outs2 = snmpwalk_dev_inout("snmpwalk -v 2c -c public 127.0.0.1 ifoutOctets")
		for idx, desc in devs.items():
			try:
				inv = (ins2[idx] - ins1[idx]) / interval
				outv = (outs2[idx] - outs1[idx]) / interval
				#print idx, desc, inv, outv
				pushdata.append({'desc':desc, 'in':inv, 'out':outv})
			except Exception, e:
				continue
		print pushdata
		ins1 = ins2
		outs1 = outs2
		snmppush.send_multipart([sid, b'netdev', json.dumps(pushdata)])

		#2.cpu
		#############################################################################
		cpu2 = get_proc_stat_cpu()
		total = sum(cpu2) - sum(cpu1)
		used = (sum(cpu2) - cpu2[3]) - (sum(cpu1) - cpu1[3])
		uper = int(float(used) / float(total) * 100)
		snmppush.send_multipart([sid, b'cpu', str(uper)])

		#3.memory usedper
		#############################################################################
		pushdata = {}
		muper, msuper = get_memory_info()
		pushdata['uper'] = muper
		pushdata['super'] = msuper
		snmppush.send_multipart([sid, b'mem', json.dumps(pushdata)])

		#4.disk usedper
		#############################################################################
		pushdata = get_disk_info()
		snmppush.send_multipart([sid, b'disk', json.dumps(pushdata)])

except Exception, e:
	print e



#!/usr/bin/env python
import os
import sys
import time

logpath = '/opt/haproxy_log/'
hostname_listfile = '/opt/squid_tools/hostname_list.txt'
hostname_list = {}

try:
	f = open(hostname_listfile, 'r')
	lines = f.readlines()
	f.close()
	for line in lines:
		hostname = line.replace('\n', '')
		hostname_list[hostname] = hostname
except:
	pass

#print hostname_list

day = time.strftime('%Y-%m-%d', time.localtime(time.time()-1800))
hour = time.strftime('%H', time.localtime(time.time()-1800))
min = time.strftime('%M', time.localtime(time.time()-1800))
minidx = (int)(min)/30 + 1

daylogpath = logpath + day + '_h'
ziplogpath = "%s/%s_%s" %(daylogpath, hour, minidx)
if not os.path.exists(ziplogpath):
	os.mkdir(ziplogpath)

#print day, hour, min, minidx, daylogpath

for root, dirs, files in os.walk(daylogpath):
	hostname = root.replace(daylogpath+'/', '')
	logname = "%s_%s" %(hour, minidx)
	#print hostname, logname
	if not logname in files:
		continue
	logfile = "%s/%s/%s" %(daylogpath, hostname, logname)
	newlogfile = "%s/%s" %(ziplogpath, hostname)
	try:
		os.rename(logfile, newlogfile)
	except:
		continue

hm = "%s_%s" %(hour, minidx)
cmd = "cd %s && /bin/tar -zcf %s.tar.gz %s" %(daylogpath, hm, hm)
print cmd
os.system(cmd)


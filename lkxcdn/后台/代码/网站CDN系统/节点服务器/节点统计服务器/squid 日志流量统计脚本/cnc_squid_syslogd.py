#!/usr/bin/env python

import urllib
import httplib
import os
import socket
import sys
import time
import simplejson

def sync_data():
	global hostname_list, rateinfo

	temp_hostname_list = {}

	try:
		f = open(hostname_listfile, 'r')
		lines = f.readlines()
		f.close()
		for line in lines:
			hostname = line.replace('\n', '')
			temp_hostname_list[hostname] = hostname

		hostname_list = temp_hostname_list

	except Exception, e:
		pass

	try:
		print rateinfo
		params = urllib.urlencode({'rateinfo': simplejson.dumps(rateinfo)})
		headers = {"Content-type": "application/x-www-form-urlencoded", "Accept": "text/plain"}
		conn = httplib.HTTPConnection(posthost)
		conn.request("POST", posturl, params, headers)
		response = conn.getresponse()
		data = response.read()
		print(data)
		conn.close()
		rateinfo.clear()
	except:
		rateinfo.clear()
			

if len(sys.argv) != 3:
	print(sys.argv)
	sys.exit(1)

hostname_listfile = '/opt/squid_tools/hostname_list.txt'
hostname_list = {}

try:
	f = open(hostname_listfile, 'r')
	lines = f.readlines()
	f.close()
	for line in lines:
		hostname = line.replace('\n', '')
		hostname_list[hostname] = hostname
except Exception, e:
	print e
	sys.exit(1)

print hostname_list
#for hostname in hostname_list:
#	print hostname
#sys.exit(1)

posthost = sys.argv[1]
posturl = sys.argv[2]

synctimeout = 300
timeout = 5
socket.setdefaulttimeout(timeout)

try:
    sock = socket.socket(socket.AF_INET,socket.SOCK_DGRAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    sock.bind(("112.90.177.77", 514))
except:
	print("error bind")
	sock.close()
	sys.exit(1)

rateinfo = {}
last_time = now_time = int(time.time())

try:
	while 1:
		try:
			now_time = int(time.time())
			if now_time - last_time >= synctimeout:
				last_time = now_time
				sync_data()

			line, addr = sock.recvfrom(4096)
			line = line.split(' 127.0.0.1 ')
			if len(line) != 2:
				continue
			line = line[1].split()
			if len(line) < 4:
				continue
			#print line

			# TCP_MEM_HIT/200 63391 GET http://cdn.qiyou.com/swf/020.swf - NONE/- application/x-shockwave-flash
			httpstatus = line[0]
			httpsize = line[1]
			httpmethod = line[2]
			httpurl = line[3]
			#print httpstatus, httpsize, httpmethod, httpurl

			httpstatus = httpstatus.split('/')
			if len(httpstatus) !=  2:
				continue
			httpurl = httpurl.split('/')
			if len(httpurl) < 3:
				continue
			domain = httpurl[2]

			found = 0
			for hostname in hostname_list:
				if domain.find(hostname) >= 0:
					found = 1
					break
			if found == 0:
				continue
			
			if not rateinfo.has_key(domain):
				rateinfo[domain] = {}
				rateinfo[domain]['sent'] = 0
				rateinfo[domain]['cnt'] = 0
				rateinfo[domain]['hit_cnt'] = 0
				rateinfo[domain]['hit_sent'] = 0

			rateinfo[domain]['sent'] += int(httpsize)
			rateinfo[domain]['cnt'] += 1
			
			if httpstatus[0].find('HIT') > 0:
				rateinfo[domain]['hit_cnt'] += 1
				rateinfo[domain]['hit_sent'] += int(httpsize)

			#print httpstatus, httpsize, domain

		except socket.timeout, e:
			#print e
			pass

		except socket.error, e:
			#print e
			pass

except IOError, e:
	print e
	sock.close()
	sys.exit(1)

except KeyboardInterrupt:
	sock.close()
	sync_data()
	sys.exit(0)

sock.close()
sync_data()
sys.exit(0)

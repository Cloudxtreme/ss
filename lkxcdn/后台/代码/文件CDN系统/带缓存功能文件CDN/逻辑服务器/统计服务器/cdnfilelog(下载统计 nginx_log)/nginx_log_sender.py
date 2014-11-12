#!/bin/env python
import os
import sys
import time
import zmq
import socket
import string
import traceback

collectorip = '183.60.46.163'

scan_time = 60
post_time = 5 * 60
reopen_time = 60

ngx_log_dir = '/opt/nginx_cache/nginx/logs/'
log_filter = ('access.log', 'error.log', 'nginx.pid')
fps = {}
stats = {}

def get_curtime():
	st = time.localtime(time.time())
	return "%d-%02d-%02d %02d:%02d:%02d"%(st.tm_year, st.tm_mon, st.tm_mday, st.tm_hour, st.tm_min, st.tm_sec)

def is_ip(ip):
	try: 
		socket.inet_aton(ip)
		return True
	except:
		return False

def check_file(file):
	if not os.access(file, os.F_OK):
		print("File '%s' does not exist" % (file))
		return False
	if not os.access(file, os.R_OK):
		print("File '%s' not readable" % (file))
		return False
	if os.path.isdir(file):
		print("File '%s' is a directory" % (file))
		return False
	return True

def deal_url(url):
	try:
		pos = url.index('?')
		return url[:pos]
	except:
		return url

def get_url_host_uri(url):
	host = ''
	uri = url
	try:
		host = url
		if url[0] == '/':
			host = url[1:]
		pos = host.index('/')
		host = host[:pos]
		uri = url.replace(host, '')
		if len(uri) >= 2 and uri[0] == '/' and uri[1] == '/':
			uri = uri[1:]
	except:	
		return host, uri
	return host, uri

def deal_line(receiver, client, line):

	### '$time_local|$remote_addr|$request|'
	### '$status|$request_time|$body_bytes_sent|$http_range|$sent_http_content_range|$host|$http_referer|'
	### '$http_user_agent';
	
	try:
		if line.index('127.0.0.1') or line.index('rjkjcdn-purge'):
			return
	except:
		pass
	receiver.send_multipart([client, line])

################ main ####################

last_scan_time = time.time() - scan_time
last_post_time = time.time()

receiverip = sys.argv[1]

try:
	context = zmq.Context()
	receiver = context.socket(zmq.PUSH)
	receiver.connect("tcp://%s:6121"%(receiverip))
except Exception, e:
	print e
	sys.exit(1)

while True:
	#scan log file list
	###################################################
	try:
		if time.time() - last_scan_time >= scan_time:
			last_scan_time = time.time()
			logs = os.listdir(ngx_log_dir)
			for log in logs:
				if log in log_filter:
					continue
				if not log.endswith('.log'):
					continue
				logfile = ngx_log_dir + log
				client = log.replace('.log', '')
				if not client in fps:
					if check_file(logfile):
						fps[client] = {}
						fp = open(logfile)
						fp.seek(0, 2)
						fps[client]['fp'] = fp
						fps[client]['lasttime'] = time.time()
						fps[client]['filepath'] = logfile
						print get_curtime() + " " + logfile
				if not client in fps:
					continue
				info = fps[client]
				fp = info['fp']
				logfile = info['filepath']
				if time.time() - info['lasttime'] >= reopen_time:
					if check_file(logfile):
						#reopen log again
						#print "%s reopen %s"%(get_curtime(), logfile)
						fp.close()
						fp = open(logfile)
						fp.seek(0, 2)
						fps[client]['fp'] = fp
						fps[client]['lasttime'] = time.time()
			#print fps
	except Exception as e:
		exstr = traceback.format_exc()
		print exstr

	#read log file line
	###################################################
	try:
		cur = 0
		max_per_read_line = 10
		for client, info in fps.items():
			fp = info['fp']
			read_line = 0
			while True:
				cur = fp.tell()
				line = fp.readline()
				if not line:
					fp.seek(cur)
					break
				else:
					fps[client]['lasttime'] = time.time()
					deal_line(receiver, client, line)
					read_line += 1
					if read_line >= max_per_read_line:
						break
	except Exception as e:
		fp.close()
		del fps[client]
		exstr = traceback.format_exc()
		print exstr

	time.sleep(0.5)


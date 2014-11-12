#!/bin/env python
import os
import sys
import time
import zmq
import socket
import string
import json
import traceback

myip = ''
collectorip = '183.60.46.163'

scan_time = 60
post_time =  5 * 60
reopen_time =  60

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

def deal_line(client, line):
	global myip, stats

	### '$time_local|$remote_addr|$request|'
	### '$status|$request_time|$body_bytes_sent|$http_range|$sent_http_content_range|$host|$http_referer|'
	### '$http_user_agent';

	temp = line.split('|')
	if len(temp) < 10:
		return
	peerip = temp[1]
	if peerip == myip or myip == '127.0.0.1':
		#print line
		return
	http_req = temp[2].split()
	if len(http_req) < 3:
		return
	http_url = deal_url(http_req[1])
	http_status = string.atoi(temp[3])
	http_req_time = string.atof(temp[4])
	http_sent = string.atof(temp[5])
	http_host = temp[8]

	if is_ip(http_host):
		#not 80 port
		http_host, http_url = get_url_host_uri(http_url)
		#print client, http_host, http_req_time, http_sent, http_url
		pass
	else:
		#80 port
		#print client, http_host, http_req_time, http_sent, http_url
		pass

	if http_host == '':
		return

	post_time_sent = http_sent
	if http_req_time > post_time:
		post_time_sent = int(http_sent / http_req_time * post_time)
		#print client, http_host, http_req_time, http_sent, post_time_sent

	if not client in stats:
		stats[client] = {}
	if not http_host in stats[client]:
		stats[client][http_host] = 0

	stats[client][http_host] += post_time_sent

################ main ####################

last_scan_time = time.time() - scan_time
last_post_time = time.time()

myip = sys.argv[1]
collectorip = sys.argv[2]

try:
	context = zmq.Context()
	collector = context.socket(zmq.PUSH)
	collector.connect("tcp://%s:6120"%(collectorip))
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
						fp = open(logfile)
						fp.seek(0, 2)
						fps[client] = {}
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
					deal_line(client, line)
					read_line += 1
					if read_line >= max_per_read_line:
						break
	except Exception as e:
		fp.close()
		del fps[client]
		exstr = traceback.format_exc()
		print exstr

	#post stats 
	###################################################
	try:
		if time.time() - last_post_time >= post_time:
			json_data = {}
			json_data['ip'] = myip
			json_data['data'] = {}
			last_post_time = time.time()
			for client, infos in stats.items():
				json_data['data'][client] = []
				for host, sent in infos.items():
					json_data['data'][client].append({'host':host, 'sent':int(sent)})
					#print client, host, int(sent/1024), int(sent/post_time/1024)
			stats.clear()
			print get_curtime() + " " + json.dumps(json_data)
			collector.send(json.dumps(json_data))

	except Exception as e:
		exstr = traceback.format_exc()
		print exstr

	time.sleep(0.5)

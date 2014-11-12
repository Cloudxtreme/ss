#!/usr/bin/env python
import os
import socket
import sys
import time
import threading
import Queue

synctimeout = 300
timeout = 5
host_packet_buf_each_count = 100
socket.setdefaulttimeout(timeout)

logpath = '/opt/squid_log/'
hostname_listfile = '/opt/squid_tools/hostname_list.txt'

packet_queue = Queue.Queue()
host_queue = Queue.Queue()



class ThreadLogWrite(threading.Thread):
	def __init__(self, host_queue):
		threading.Thread.__init__(self)
		self.host_queue = host_queue

	def run(self):
		global logpath
		file_handles = {}
		while True:
			host_name = self.host_queue.get()
			host_content = self.host_queue.get()

			day = time.strftime('%Y-%m-%d',time.localtime(time.time()))
			daypath = logpath + day

			try:
				if not os.path.exists(daypath):
					os.mkdir(daypath)
			except OSError, e:
				print e
				pass

			filename = """%s/%s""" %(daypath, host_name)

			file_handles[host_name] = open(filename, 'a')

			#cmd = """/bin/echo "%s" """ %(host_content)
			#os.system(cmd)

			file_handles[host_name].write(host_content)
			file_handles[host_name].close()

		


class ThreadLogBuf(threading.Thread):
	def __init__(self, packet_queue, host_queue):
		threading.Thread.__init__(self)
		self.packet_queue = packet_queue
		self.host_queue = host_queue

	def run(self):
		global synctimeout, host_packet_buf_each_count, hostname_listfile

		hostname_list = {}
		host_packet_count = {}
		host_packet_content = {}

		hostname_list = sync_hostname_list()
		last_time = now_time = int(time.time())
		
		while True:
			now_time = int(time.time())
			if now_time - last_time >= synctimeout:
				last_time = now_time
				hostname_list = sync_hostname_list()
		
			hostname = self.packet_queue.get()
			line = self.packet_queue.get()

			found = 0
			for name in hostname_list:
				if hostname.find(name) >= 0:
					found = 1
					break
			if found == 0:
				continue

			if not host_packet_count.get(hostname):
				host_packet_count[hostname] = 0
			if not host_packet_content.get(hostname):
				host_packet_content[hostname] = ""

			host_packet_count[hostname] += 1
			host_packet_content[hostname] += line
			host_packet_content[hostname] += "\n"

			if host_packet_count[hostname] > host_packet_buf_each_count:
				host_queue.put(hostname)
				host_queue.put(host_packet_content[hostname])
				host_packet_count[hostname] = 0
				host_packet_content[hostname] = ""



def sync_hostname_list():
	global hostname_listfile
	temp_hostname_list = {}
	try:
		f = open(hostname_listfile, 'r')
		lines = f.readlines()
		f.close()
		for line in lines:
			hostname = line.replace('\n', '')
			temp_hostname_list[hostname] = hostname
		return temp_hostname_list
	except:
		return





# __main__
try:
    sock = socket.socket(socket.AF_INET,socket.SOCK_DGRAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    sock.bind(("0.0.0.0", 514))
except:
	print("error bind")
	sock.close()
	sys.exit(1)


try:
	log_thread_buf = ThreadLogBuf(packet_queue, host_queue)
	log_thread_buf.setDaemon(True)
	log_thread_buf.start()

	log_thread_write = ThreadLogWrite(host_queue)
	log_thread_write.setDaemon(True)
	log_thread_write.start()
	
	while 1:
		try:
			line, addr = sock.recvfrom(4096)

			temp = line.split(' 127.0.0.1 ')
			if len(temp) != 2:
				continue
			temp = temp[1].split()
			if len(temp) < 4:
				continue
			#print line

			# TCP_MEM_HIT/200 63391 GET http://cdn.qiyou.com/swf/020.swf - NONE/- application/x-shockwave-flash
			httpstatus = temp[0]
			httpsize = temp[1]
			httpmethod = temp[2]
			httpurl = temp[3]
			#print httpstatus, httpsize, httpmethod, httpurl

			httpstatus = httpstatus.split('/')
			if len(httpstatus) !=  2:
				continue

			squid_status = httpstatus[0]
			http_ret = httpstatus[1]
			if squid_status.find('HIT') < 0 :
				continue;

			temp = httpurl.split('/')
			if len(temp) < 3:
				continue
			domain = temp[2]

			rooturl = """http://%s/""" %(domain)
			if rooturl == httpurl :
				continue

			packet_queue.put(domain)
			packet_queue.put(httpurl)

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
sys.exit(0)


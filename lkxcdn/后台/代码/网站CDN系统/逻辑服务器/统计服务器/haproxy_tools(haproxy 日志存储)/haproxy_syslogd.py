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

logpath = '/opt/haproxy_log/'
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
			hour = time.strftime('%H', time.localtime(time.time()))
			minute = time.strftime('%M', time.localtime(time.time()))

			daypath = logpath + day
			hour_path = daypath + '_h'
			host_path = hour_path + '/' + host_name

			try:
				if not os.path.exists(daypath):
					os.mkdir(daypath)
				if not os.path.exists(hour_path):
					os.mkdir(hour_path)
				if not os.path.exists(host_path):
					os.mkdir(host_path)
			except OSError, e:
				print e
				pass

			filename = """%s/%s""" %(daypath, host_name)
			if minute < '30':
				hourfile = host_path + '/' + hour + '_1'
			else:
				hourfile = host_path + '/' + hour + '_2'

			file_handles[host_name] = open(filename, 'a')
			file_handles[host_path] = open(hourfile, 'a')

			#cmd = """/bin/echo "%s" >> %s""" %(host_content, filename)
			#os.system(cmd)

			file_handles[host_name].write(host_content)
			file_handles[host_name].close()
			file_handles[host_path].write(host_content)
			file_handles[host_path].close()

			#self.host_queue.task_done()

		


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

			line = line.replace('&', '\&')
			line = line.replace('haproxy', 'tcpproxy')
			line = line.replace('squid', 'cache')

			if not host_packet_count.get(hostname):
				host_packet_count[hostname] = 0
			if not host_packet_content.get(hostname):
				host_packet_content[hostname] = ""

			host_packet_count[hostname] += 1
			host_packet_content[hostname] += line

			if host_packet_count[hostname] > host_packet_buf_each_count:
				host_queue.put(hostname)
				host_queue.put(host_packet_content[hostname])
				host_packet_count[hostname] = 0
				host_packet_content[hostname] = ""


			#self.packet_queue.task_done()



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
    sock.bind(("0.0.0.0", 515))
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

			temp = line
			temp = line.split('haproxy')
			if len(temp) != 2:
				continue
			info = temp[1]
			info = info.split(' ')
			if len(info) < 14:
				continue

			temp = info[13]
			temp = temp.replace('{', '')
			temp = temp.replace('}', '')
			temp = temp.split('|')
			if len(temp) < 1:
				continue
			hostname = temp[0]
			if len(hostname) == 0:
				continue
			#print hostname
			#print line

			packet_queue.put(hostname)
			packet_queue.put(line)

		except socket.timeout, e:
			#print e
			pass

		except socket.error, e:
			#print e
			pass

except IOError, e:
	print e
	#host_queue.join()
	#packet_queue.join()
	sock.close()
	sys.exit(1)

except KeyboardInterrupt:
	#host_queue.join()
	#packet_queue.join()
	sock.close()
	sync_data()
	sys.exit(0)

#host_queue.join()
#packet_queue.join()
sock.close()
sys.exit(0)

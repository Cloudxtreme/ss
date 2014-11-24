#!/usr/bin/python

import socket, IN
import time
import sys

pppmax = 160
pppindex = 0
sendrate = 100* 1024
addr = ("192.168.22.199", 8888)

fds = {}
senddata = ""
senddatalen = 1024
i = 0
while i < senddatalen:
	senddata += "0"
	i += 1
print(len(senddata))

while pppindex < pppmax:
	
	fd = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	
	ifdesc = """ppp%d""" %(pppindex)
	pppindex = pppindex + 1
	#print(ifdesc)
	
	try:
		fd.setsockopt(socket.SOL_SOCKET, IN.SO_BINDTODEVICE, ifdesc)
	except socket.error, e:
		print(e)
		fd.close()
	
	fds[fd] = {'status' : True}
	
#print(fds)	
#sys.exit(0)

while 1:
	
	starttime = time.time() * 1000

	for fd in fds:
		fdinfo = fds[fd]
		if fdinfo['status'] == False:
			continue
			
		i = 0
		while i	< sendrate/senddatalen:
			try:
				fd.sendto(senddata, addr)
			except socket.error, e:
				fdinfo['status'] = False
				fd.close()
				print(e)
				break		
			i += 1
	
	endtime = time.time() * 1000
	
	#print((1 - (endtime - starttime)/1000))	
#	if (1 - (endtime - starttime)/1000) > 0:
#		time.sleep((1 - (endtime - starttime)/1000))
	
	time.sleep(0)

for fd in fds:
	fd.close()


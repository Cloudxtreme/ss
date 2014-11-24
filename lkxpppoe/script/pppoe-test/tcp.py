#!/usr/bin/python

import socket, IN
import select
import time
import sys

pppmax = 80
pppindex = 0
addr = ("192.168.22.7", 80)

fds = {}

senddata = "POST /movie.rmvb HTTP/1.1\r\n";
senddata += "Host: 192.168.22.7\r\n";
senddata += "Connection: Close\r\n\r\n";
senddata += "\r\n";

while pppindex < pppmax:
	ifdesc = """ppp%d""" %(pppindex)
	pppindex = pppindex + 1
	fd = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
	try:
		fd.setsockopt(socket.SOL_SOCKET, IN.SO_BINDTODEVICE, ifdesc)
		fd.connect(addr)
		fd.send(senddata)
		fds[fd] = {'status' : True,'receivelen' : 0}
	except socket.error, e:
		print(e)
		fd.close()
		fds[fd] = {'status' : False,'receivelen' : 0}

complete = False
receivelen = 0
while complete == False:
	readfd = [];
	complete = True
	for fd in fds:
		fdinfo = fds[fd]
		if fdinfo['status'] == False:
			continue
		readfd.append(fd)
		complete = False
	if complete == True:
		continue
	infds,outfds,errfds = select.select(readfd,[],[],5)
	if len(infds) != 0:
		for fd in fds:
			recievedata = fd.recv(1024)
			receivelen = len(recievedata)
			if receivelen <= 0:
				fds[fd]['status'] = False
			else:
				fds[fd]['receivelen'] += receivelen
for fd in fds:
	fdinfo = fds[fd]
	print fdinfo["status"],':',fdinfo["receivelen"]
fd.close()  

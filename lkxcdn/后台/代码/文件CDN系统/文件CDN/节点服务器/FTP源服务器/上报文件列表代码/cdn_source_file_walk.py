#!/usr/bin/env python

import os
import sys
import httplib
import urllib
import errno
import socket

if len(sys.argv) != 2:
	print(sys.argv)
	sys.exit(1)

config = sys.argv[1]
print(config)

try:
    confile=open(config, "r")
except IOError,message:
    print >> sys.stderr, "open ",message
    sys.exit(1)

timeout = 60
socket.setdefaulttimeout(timeout)

while 1:

	try:
	
		line = confile.readline()
		if len(line) == 0:
			break
		line = line.split()
		if len(line) != 4:
			continue

		print(line)
		
		host = line[0]
		url = line[1]
		myip = line[2]
		walkpath = line[3]		
		
		filelist = []
		
		for root, dirs, files in os.walk(walkpath):
	
			for file in files:
			
				if file[0] == '.':
					continue
				
				filepath = "%s/%s" %(root, file)

				try:

					fileinfo = os.stat(filepath)

				except OSError, e:
					if e.errno == errno.ENOENT:
						os.remove(filepath)
					continue

				fileinfo = "%s:%d:%s" %(file, fileinfo.st_size, root)
				filelist.append(fileinfo)
	
		print(filelist)
		params = urllib.urlencode({'myip': myip, 'nodepath':walkpath, 'filelist': filelist})
		#print(params);
		headers = {"Content-type": "application/x-www-form-urlencoded", "Accept": "text/plain"}
		conn = httplib.HTTPConnection(host)
		conn.request("POST", url, params, headers)
		response = conn.getresponse()
		data = response.read()
		print(data)
		conn.close()

	except ValueError, e:
		continue

	except IndexError, e:
		continue

	except socket.timeout, e:
		confile.close()
		sys.exit(1)

	except IOError, e:
		print >> sys.stderr, "readline ",e
		confile.close()
		sys.exit(1)

confile.close()		




#!/usr/bin/env python 

import urllib
import httplib
import os
import sys
import time

if len(sys.argv) != 7:
	print(sys.argv)
	sys.exit(1)

posthost = sys.argv[1]
app = sys.argv[2]
ifdesc = sys.argv[3]
myhost = sys.argv[4]
port1 = sys.argv[5]
port2 = sys.argv[6]

if port1 > port2 :
	print("port error")
	sys.exit(1)

rate_cmd = "%s \"%s\" \"%s\" %s %s" %(app, ifdesc, myhost, port1, port2)
p = os.popen(rate_cmd)
rate = p.read()
p.close()
rate = rate.split("\n")
print(rate)

ratepost = []
for rateinfo in rate:
	if len(rateinfo) == 0:
		continue
	
	port, outrate, inrate = rateinfo.split()
	
	if port < port1 or port > port2:
		continue
	
	ratepack = "%s:%s:%s|" % (port, outrate, inrate)
	ratepost.append(ratepack)

url = "/cdnmgr/cdn_stats/portrate_post_ex.php"
headers = {"Content-type": "application/x-www-form-urlencoded", "Accept": "text/plain"}
params = urllib.urlencode({'ratepost':ratepost})
conn = httplib.HTTPConnection(posthost)
conn.request("POST", url, params, headers)
response = conn.getresponse()
data = response.read()
print(data)
conn.close()


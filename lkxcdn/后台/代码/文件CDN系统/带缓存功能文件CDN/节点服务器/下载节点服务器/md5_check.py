#!/usr/bin/env python
import os
import sys

cache = '/opt/nginx_cache_data/path'

md5 = sys.argv[1]
d1 = md5[-1]
d2 = md5[-3:-1]
#print d1, d2

cdir = "%s/%s/%s"%(cache, d1, d2)
cfile = cdir + '/' + md5
#print cfile

try:
	filesize = os.path.getsize(cfile)
	rf = open(cfile, 'rb')
	data = rf.read(1024*10)
	pos = data.index("\r\n\r\n")
	if pos <= 0:
		rf.close()
		sys.exit(1)
	pos += 4;
	#print pos

	tempfile = '/tmp/' + md5
	wf = open(tempfile, 'wb')
	rf.seek(pos)
	wf.seek(0)
	i = 0
	while i < filesize:
		data = rf.read(1024*64)
		wf.write(data)
		i += 1024*10

	rf.close()
	wf.close()

	cmd = 'md5sum ' + tempfile
	output = os.popen(cmd)
	ret = output.read()
	output.close()
	os.remove(tempfile)
	ret = ret.split()
	print ret[0]
	sys.exit(0)

except:
	sys.exit(1)



#!/bin/env python
import os
import sys

cache_dir = '/opt/nginx_cache_data/path'

match = sys.argv[1]
opt = sys.argv[2]

c1s = os.listdir(cache_dir)
#print c1
for c1 in c1s:
	c2s = os.listdir(cache_dir + '/' + c1)
	#print c2s 
	for c2 in c2s:
		files = os.listdir(cache_dir + '/' + c1 + '/' + c2)
		for file in files:
			filepath = cache_dir + '/' + c1 + '/' + c2 + '/' + file
			#print filepath
			try:
				rf = open(filepath, 'r')
				data = rf.read(512)
				rf.close()
				pos1 = data.index('KEY: ')
				pos2 = data.index("\n", pos1+5)
				filekey = data[pos1+5:pos2]
				#print filekey
				if filekey.index(match) > 0:
					if opt == 'del':
						os.remove(filepath)
					print filepath, filekey
			except:
				continue
		#break
	#break


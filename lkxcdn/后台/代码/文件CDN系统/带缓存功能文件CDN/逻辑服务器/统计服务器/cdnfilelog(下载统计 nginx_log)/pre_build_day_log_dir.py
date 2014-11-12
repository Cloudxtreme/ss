#!/bin/env python
import os
import sys
import time

logdir = '/opt/cdn_file_log_file'

st = time.localtime(time.time())

day = "%d-%02d-%02d"%(st.tm_year, st.tm_mon, st.tm_mday)

try:
	os.mkdir("%s/%s"%(logdir, day))
except Exception as e:
	pass

for hour in range(0, 24):
	try:
		os.mkdir("%s/%s/%02d"%(logdir, day, hour))
	except Exception as e:
		pass
	for min in range(0, 12):
		try:
			os.mkdir("%s/%s/%02d/%d"%(logdir, day, hour, min))
		except Exception as e:
			pass




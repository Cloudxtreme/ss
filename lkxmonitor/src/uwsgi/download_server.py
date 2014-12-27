#!/usr/bin/env python
#coding=utf-8
import sys
import os
import web
import time
import json
import urllib

urls = ('/download', 'Download')

def daemon():

	stdin='/dev/null'
	stdout='/dev/null'
	stderr='/dev/null'

	try:   
		pid = os.fork()   
		if pid > 0:  
			# exit first parent  
			sys.exit(0)   
	except OSError, e:   
		print >>sys.stderr, "fork #1 failed: %d (%s)" % (e.errno, e.strerror)   
		sys.exit(1)  

	# decouple from parent environment  
	os.chdir("/")   
	os.setsid()   
	os.umask(0)   

	# do second fork  
	try:   
		pid = os.fork()   
		if pid > 0:  
			# exit from second parent, print eventual PID before  
			print "Daemon PID %d" % pid   
			sys.exit(0)   
	except OSError, e:   
		print >>sys.stderr, "fork #2 failed: %d (%s)" % (e.errno, e.strerror)   
		sys.exit(1)   

	sys.stdout.flush()
	sys.stderr.flush()
	si = file(stdin, 'r')
	so = file(stdout, 'a+')
	se = file(stderr, 'a+', 0)
	os.dup2(si.fileno(), sys.stdin.fileno())
	os.dup2(so.fileno(), sys.stdout.fileno())
	os.dup2(se.fileno(), sys.stderr.fileno())

class Download:
	def GET(self):
		user_data = web.input()
		file = user_data['file']
		filename = file
		pos = filename.rfind('/')
		if pos >= 0:
			filename = filename[pos+1:]
		print filename
		try:
			f = open(file, "rb")
			web.header('Content-Type','application/octet-stream')
			web.header('Content-disposition', 'attachment; filename=%s' % filename)
			while True:
				data = f.read(64*1024)
				if data:
					yield data
				else:
					break
		except Exception, e:
			print e
			yield 'Error'
		finally:
			if f:
				f.close()
			
if __name__ == "__main__":
	daemon()
	app = web.application(urls, globals()) 
	app.run()

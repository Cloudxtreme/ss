#!/usr/bin/env python
#coding=utf-8
import sys
import os
import web
import json
import urllib

urls = ('/upload', 'Upload')

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

class Upload:
	def GET(self):
		web.header("Content-Type","text/html; charset=utf-8")
		return """<html><head></head><body>
				<form method="POST" enctype="multipart/form-data" action="">
				<input type="file" name="myfile" />
				<input type="text" name="filedir" />
				<input type="submit" />
				</form>
				</body></html>"""

	def POST(self):
		x = web.input(myfile={})

		filedir = '/opt/webadmin/upload'
		if 'filedir' in x:
			if x.filedir != '':
				filedir = urllib.unquote(x.filedir)

		url = ''
		if 'url' in x:
			url = urllib.unquote(x.url)

		if 'myfile' in x: # to check if the file-object is created
			filepath=x.myfile.filename.replace('\\','/') # replaces the windows-style slashes with linux ones.
			filename=filepath.split('/')[-1] # splits the and chooses the last part (the filename with extension)
			fout = open(filedir +'/'+ filename,'w') # creates the file where the uploaded file should be stored
			fout.write(x.myfile.file.read()) # writes the uploaded file to the newly created file.
			fout.close() # closes the file, upload complete.
			#web.header("Content-Type", "text/html; charset=utf-8")
			#raise web.seeother('/upload')

			#json_result = {}
			#json_result['ret'] = 0
			#json_result['error'] = ''
			#json_result['result'] = ''
			#json_result['descmap'] = {}
			#return json.dumps(json_result)
			web.header("Content-Type","text/html; charset=utf-8")
			return "Upload Success! <a href=\"%s\" target=\"_self\">Upload Again</a>"%(url)

if __name__ == "__main__":
	daemon()
	app = web.application(urls, globals()) 
	app.run()

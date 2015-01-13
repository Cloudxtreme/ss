#!/usr/bin/env python 
import web 
import os

urls = ("/.*", "main")

class main:
    def GET(self):
	query = web.ctx.env['QUERY_STRING']
	info = query.split('&')
	if len(info) < 1 :
		return ''
	module = info[0].split('=')
	if len(module) < 1 :
		return ''
	module = module[1]
	if module.endswith('.py'):
		cmd = """python ./modules/%s \"%s\""""%(module, query)
	else:
		cmd = """./modules/%s \"%s\""""%(module, query)
	print cmd
	ret = os.popen(cmd)
	return ret.read()
 
app = web.application(urls, globals())

application = app.wsgifunc()


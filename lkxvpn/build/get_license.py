#!/bin/env python
import time
import zmq
import MySQLdb
import hashlib
import os
import sys
from M2Crypto import EVP, RSA, util

context = zmq.Context()
socket = context.socket(zmq.REP)
socket.bind("tcp://*:5556")

while True:
	try:
		#  Wait for next request from client
		sid = socket.recv()
		#print(sid)

		try:
			conn = MySQLdb.connect(host='localhost', user='root', passwd='qwer', db='skynet', port=3306)
			cur = conn.cursor()
			sql_cmd = """select license_file from node_license where `sid` = '%s'"""%(sid)
			count = cur.execute(sql_cmd)
			if(count > 0):
				license = ''
				rows = cur.fetchall()
				for row in rows:
					license = row[0]
				license_info = ''
				if(os.path.isfile(license)):
					input = open(license, 'rb')
					license_info = input.read()
					#print(license_info)
					input.close()
				else:
					license_info = 'waiting for license'
				socket.send(license_info)
			else:
				socket.send('fail')
			cur.close()
			conn.close()
		except MySQLdb.Error, e:
			print "Mysql Error %d: %s" % (e.args[0], e.args[1])
			cur.close()
			conn.close()
			sys.exit(0)
			#continue



	except Exception, e:
		print e
		sys.exit(0)
		#continue

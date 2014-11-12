#!/usr/bin/env python

import os
import sys
import MySQLdb
import time

if len(sys.argv) != 5:
	print(sys.argv)
	sys.exit(1)

myip = sys.argv[1]
datetime = sys.argv[2]
clientdb = sys.argv[3]
logfile = sys.argv[4]
#print(sys.argv)

try:
	file = open(logfile, "r")
except IOError,message:
	print >> sys.stderr, "open ",message
	sys.exit(1)

try:
	conn = MySQLdb.connect(host = "localhost", user = "root", passwd = "rjkj@rjkj", db = clientdb)
	cursor = conn.cursor()

	#datetime = time.strftime('%Y-%m-%d',time.localtime(time.time()))
	print(datetime)
	
	#sqlcmd = """CREATE TABLE IF NOT EXISTS `%s` (
	#		`serverip` char(20) CHARACTER SET latin1 NOT NULL,
	#		`ip` char(20) CHARACTER SET latin1 NOT NULL,
	#		`request` varchar(256) CHARACTER SET latin1 NOT NULL,
	#		`status` smallint(6) NOT NULL,
	#		`sent` bigint(20) NOT NULL,
	#		`referer` varchar(256) CHARACTER SET latin1 NOT NULL
	#		) ENGINE=MyISAM DEFAULT CHARSET=utf8""" %(datetime)
	
	sqlcmd = """CREATE TABLE IF NOT EXISTS `%s` (
		`serverip` char(20) NOT NULL,
		`request` varchar(256) NOT NULL,
		`ip` char(20) NOT NULL,
		`cnt` int(32) unsigned NOT NULL,
		`sent` bigint(64) unsigned NOT NULL,
		UNIQUE KEY `request` (`serverip`, `request`,`ip`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;""" %(datetime)

	#print sqlcmd

	cursor.execute(sqlcmd)

except MySQLdb.Error, e:
	print "DB Error %d: %s" % (e.args[0], e.args[1])
	conn.close()
	sys.exit(1)

while 1:
		
	try:
		line = file.readline()
		if len(line) == 0: 
			break

		temp = line.split('|')
		if len(temp) < 4:
			continue

		ip = temp[0]
		request = temp[1]
		status = temp[2]
		sent = temp[3]

		request = request.split()
		if len(request) < 2 :
			continue
		request = request[1]

		#status = int(status)
		#if status != 200 and status != 206:
		#		continue

		#sqlcmd = """INSERT INTO `%s` (`serverip`, `ip`, `request`, `status`, `sent`, `referer`)
		#		VALUES('%s', '%s', '%s', '%s', '%s', '%s'); 
		#		""" %(datetime, myip, ip, request, status, sent, referer)

		sqlcmd = """insert into `%s` (serverip, request, ip, cnt, sent)
				values('%s', '%s', '%s', '%s', '%s') 
				on duplicate key update cnt = cnt + 1, sent = sent + %s;""" %(datetime, myip, request, ip, 1, sent, sent)

		#print(sqlcmd)
		cursor.execute(sqlcmd)

	except ValueError, e:
		continue

	except IndexError, e:
		continue

	except IOError, e:
		print >> sys.stderr, "readline ",e
		file.close()
		cursor.close()
		conn.close()
		sys.exit(1)

	except MySQLdb.Error, e:
		print >> sys.stderr, "db ",e
		file.close()
		cursor.close()
		conn.close()
		sys.exit(1)
	
cursor.close()
conn.close()

#print(sys.argv)
#print 'finish!'





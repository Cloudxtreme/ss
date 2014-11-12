import os
import sys
import time
import zmq
import MySQLdb
import json

# Prepare our context and publisher
context   = zmq.Context()

collector = context.socket(zmq.PULL)
collector.bind("tcp://*:6120")

def main_loop():

	while True:
		try:
			msg = collector.recv(zmq.DONTWAIT)
			msg = json.loads(msg)
			print msg['ip']
			conn = MySQLdb.connect(host='localhost', user='root', passwd='rjkj@rjkj', db='cdn_file_host_flow', port=3306)
			cur = conn.cursor(cursorclass = MySQLdb.cursors.DictCursor)
			for client in msg['data']:
				print client
				for info in msg['data'][client]:
					print info['host'], info['sent']
					cur.execute("INSERT INTO `cdn_file_host_flow`.`daytable` (`ip` ,`owner` ,`hostname` ,`flow` ,`time`) VALUES ('%s', '%s', '%s', '%s', now());"%(msg['ip'], client, info['host'], info['sent']))
			cur.close();
			conn.close()

		except zmq.Again:
			time.sleep(1)
		except zmq.ZMQError as e:
			print "check feedback zmq Error"
			print e
		except MySQLdb.Error, e:
			print e
		except Exception , e:
			print "check feedback Exception"
			print e
			
def main():
	"""main method"""

	while True:
		main_loop()
	
	collector.close()
	context.term()

if __name__ == "__main__":
	main()


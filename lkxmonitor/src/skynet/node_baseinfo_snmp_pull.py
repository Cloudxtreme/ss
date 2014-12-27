import time
import zmq
import MySQLdb
import json

# Prepare our context and publisher
context   = zmq.Context()

snmppull = context.socket(zmq.PULL)
snmppull.bind("tcp://*:5565")

def main_loop():

	while True:
		try:
			#msg = feedback.recv(zmq.DONTWAIT)
			[sid, snmptype, snmpdata] = snmppull.recv_multipart()
			snmpdata = json.loads(snmpdata)
			print sid, snmptype, snmpdata

			if snmptype == 'netdev':
				conn = MySQLdb.connect(host='61.142.208.98', user='root', passwd='rjkj@rjkj', db='skynet', port=3306)
				cur = conn.cursor(cursorclass = MySQLdb.cursors.DictCursor)
				for x in snmpdata:
					if x['in'] < 0 or x['out'] < 0:
						continue
					cur.execute("insert into `node_netdev_data`(`sid`, `dev`, `in`, `out`, `timestamp`) values('%s', '%s', '%d', '%d', now())"
								%(sid, x['desc'], x['in']*8, x['out']*8))
				cur.close();
				conn.close()
				continue
			if snmptype == 'cpu':
				conn = MySQLdb.connect(host='61.142.208.98', user='root', passwd='rjkj@rjkj', db='skynet', port=3306)
				cur = conn.cursor(cursorclass = MySQLdb.cursors.DictCursor)
				cur.execute("insert into `node_cpu_data`(`sid`, `uper`, `timestamp`) values('%s', '%s', now())"
							%(sid, snmpdata))
				cur.close();
				conn.close()
				continue
			if snmptype == 'mem':
				conn = MySQLdb.connect(host='61.142.208.98', user='root', passwd='rjkj@rjkj', db='skynet', port=3306)
				cur = conn.cursor(cursorclass = MySQLdb.cursors.DictCursor)
				muper = snmpdata['uper']
				msuper = snmpdata['super']
				cur.execute("insert into `node_mem_data`(`sid`, `uper`, `super`, `timestamp`) values('%s', '%d', '%d', now())"
							%(sid, muper, msuper))
				cur.close();
				conn.close()
				continue
			if snmptype == 'disk':
				conn = MySQLdb.connect(host='61.142.208.98', user='root', passwd='rjkj@rjkj', db='skynet', port=3306)
				cur = conn.cursor(cursorclass = MySQLdb.cursors.DictCursor)
				for dev,uper in snmpdata.items():
					cur.execute("insert into `node_disk_data`(`sid`, `dev`, `uper`, `timestamp`) values('%s', '%s', '%d', now())"
							%(sid, dev, uper))
				cur.close();
				conn.close()
				continue

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
	
	publisher.close()
	feedback.close()
	context.term()

if __name__ == "__main__":
	main()


import time
import zmq
import MySQLdb

# Prepare our context and publisher
context   = zmq.Context()

publisher = context.socket(zmq.PUB)
publisher.bind("tcp://*:5563")

feedback = context.socket(zmq.PULL)
feedback.bind("tcp://*:5564")

broadcast_timeout = 60
broadcast_lasttime = time.time()

def main_loop():
	global broadcast_lasttime

	max_timeout = 5
	print '-----------------------start task------------------------'
	##1
	#check task to publish
	try:
		conn = MySQLdb.connect(host='61.142.208.98', user='root', passwd='rjkj@rjkj', db='skynet', port=3306)
		cur = conn.cursor(cursorclass = MySQLdb.cursors.DictCursor)

		#get task info first
		tasks = []
		cur.execute("select distinct taskid from `node_task_feedback` where `status` = 'ready'")
		rows = cur.fetchall()
		for row in rows:
			tasks.append(row['taskid'])
		#print tasks

		for taskid in tasks:
			#get task info
			count = cur.execute("select * from node_task where `id` = %s" %(taskid))
			row = cur.fetchone()
			tasktype = row['type']
			timeout = str(row['timeout'])
			taskdata = row['data']
                
			#get task node
			count = cur.execute("select sid from node_task_feedback where `taskid` = %s and `status` = 'ready'" 
					%(taskid))
			rows = cur.fetchall()
			sids = ""
			for row in rows:
				sid = row['sid']
				sids += "%s;"%(sid)
				#update db status
				count = cur.execute("update node_task_feedback set `status` = 'doing', `starttime` = now() where `taskid` = %s and `sid` = '%s'" 
						%(taskid, sid))
			#print sids

			print([sids, tasktype, timeout, str(taskid), taskdata])
			publisher.send_multipart([sids, tasktype, timeout, str(taskid), taskdata])
     
	except MySQLdb.Error, e:
		print "Mysql Error "
		print e
	except zmq.ZMQError, e:
		print "zmq Error "
		print e
	except Exception, e:
		#print "Error %d: %s" % (e.args[0], e.args[1])
		print e
   

	#check if time to broadcast live to node
	if time.time() - broadcast_lasttime > broadcast_timeout:
		print '---------------------broadcast live----------------------'
		broadcast_lasttime = time.time()
		publisher.send_multipart([b'all', b'checklive', b'0', b'0', b'0'])

    ##2
	#check feedback
	spent_time = 0
	print '--------------------waitting feedback--------------------'
	while spent_time < max_timeout:
		try:
			#msg = feedback.recv(zmq.DONTWAIT)
			[sid, wanip, tasktype, taskid, taskret, taskerr] = feedback.recv_multipart(zmq.DONTWAIT)
			#msg = feedback.recv()
			taskret = MySQLdb.escape_string(taskret)
			taskerr = MySQLdb.escape_string(taskerr)
			print sid, wanip, tasktype, taskid, taskret, taskerr
			if tasktype == 'shell':
				cur.execute("update node_task_feedback set `status` = 'finish', `result` = '%s', `error` = '%s', `finishtime` = now() where `taskid` = %s and `sid` = '%s'"
							%(taskret, taskerr, taskid, sid))
			if tasktype == 'checklive':
				cur.execute("update node_list set `wanip` = '%s', `lasttime` = now() where `sid` = '%s'"%(wanip, sid))
			continue
		except zmq.Again:
			print 'zmq.Again'
			spent_time += 1
			time.sleep(1)
			continue
		except zmq.ZMQError as e:
			print "check feedback zmq Error"
			print e
		except MySQLdb.Error, e:
			print e
		except Exception , e:
			print "check feedback Exception"
			print e

	#3
	#check node task timeout and retry publish next loop
	try:
		cur.execute("select * from node_task_feedback where `status` = 'doing'")
		rows = cur.fetchall()
		for row in rows:
			id = row['id']
			timeout = int(row['timeout'])
			retry = int(row['retry'])
			tried = int(row['try'])
			print str(row['starttime'])
			stime = time.strptime(str(row['starttime']), "%Y-%m-%d %H:%M:%S")
			stime = time.mktime(stime)
			if time.time() - stime > timeout:
				if tried > retry:
					cur.execute("update node_task_feedback set `status` = 'timeout' where `id` = %s"%(id))
				else:
					cur.execute("update node_task_feedback set `status` = 'doing', try = try + 1 where `id` = %s"%(id))
			
	except MySQLdb.Error, e:
		print e

	cur.close()
	conn.close()
	time.sleep(5)

def main():
	"""main method"""

	while True:
		main_loop()
	
	publisher.close()
	feedback.close()
	context.term()

if __name__ == "__main__":
	main()


import zmq
import sys
import subprocess
import time

sid = sys.argv[1]
wanip = sys.argv[2]

def main():
	""" main method """
	global sid, wanip

	# Prepare our context and publisher
	context = zmq.Context()

	subscriber = context.socket(zmq.SUB)
	subscriber.connect("tcp://61.142.208.98:5563")
	subscriber.setsockopt(zmq.SUBSCRIBE, b"")

	feedback = context.socket(zmq.PUSH)
	feedback.connect("tcp://61.142.208.98:5564")

	broadcast_timeout = 60
	broadcast_lasttime = time.time()

	poll_timeout = 3
	tasks = {}

	while True:
		#server maybe restart,that reconnect to server
		if time.time() - broadcast_lasttime > broadcast_timeout * 3:
			print 'connect to pub again'
			broadcast_lasttime = time.time()
			#no receive broadcast check live
			subscriber.close()
			feedback.close()
			#create again
			subscriber = context.socket(zmq.SUB)
			subscriber.connect("tcp://61.142.208.98:5563")
			subscriber.setsockopt(zmq.SUBSCRIBE, b"")
			feedback = context.socket(zmq.PUSH)
			feedback.connect("tcp://61.142.208.98:5564")

		#poll task result
		shell_start_time = time.time()	
		while time.time() - shell_start_time < poll_timeout:
			taskids = tasks.keys()
			for taskid in taskids:
				task = tasks[taskid]
				print taskid, task
				try:
					ret = subprocess.Popen.poll(task['fd'])
					print ret
					if ret is None:
						if time.time() - tasks[taskid]['runtime'] > tasks[taskid]['timeout']:
							task['fd'].kill()
							task['fd'].wait()
							del tasks[taskid]
						continue
					else:
						taskret = task['fd'].stdout.read()
						taskerr = task['fd'].stderr.read()
						print taskret, taskerr
						feedback.send_multipart([sid, wanip, task['type'], taskid, taskret, taskerr])
						del tasks[taskid]
				except Exception, e:
					print e
			time.sleep(0.5)
		
		#recv task 
		try:
			[sids, tasktype, timeout, taskid, taskdata] = subscriber.recv_multipart(zmq.DONTWAIT)
		#except zmq.Again:
		#	time.sleep(1)
		#	continue
		except zmq.ZMQError, e:
			if e.errno == zmq.EAGAIN:
				time.sleep(1)
			else:
				print e
			continue
		except Exception, e:
			print e
			continue

		timeout = int(timeout)

		try:
			sids.index(sid)
		except ValueError, e:
			if sids != 'all':
				continue

		print("tasktype=[%s] timeout=[%s] taskid=[%s] data=[%s]" % (tasktype, timeout, taskid, taskdata))

		if tasktype == 'shell':
			try:
				if not tasks.has_key(taskid):
					p = subprocess.Popen(taskdata + ' > /tmp/snt 2>&1 && cat /tmp/snt', 
										stdin = subprocess.PIPE, stdout = subprocess.PIPE, stderr = subprocess.PIPE, shell = True)
					tasks[taskid] = {'fd':p, 'timeout':timeout, 'type':tasktype, 'data':taskdata, 'runtime':time.time(), 'status':'run'}
			except Exception, e:
				print e
		
		if tasktype == 'checklive':
			broadcast_lasttime = time.time()
			try:
				feedback.send_multipart([sid, wanip, tasktype, b'', b'', b''])
			except Exception, e:
				print e

	# We never get here but clean up anyhow
	subscriber.close()
	feedback.close()
	context.term()


if __name__ == "__main__":
	main()


import os
import sys
import time
import zmq
import json

log_dir = '/opt/cdn_file_log_file'

# Prepare our context and publisher
context   = zmq.Context()
combiner = context.socket(zmq.PULL)
combiner.bind("tcp://*:6121")

client_logs = {}

def main_loop():

	while True:
		try:
			[client, log] = combiner.recv_multipart(zmq.DONTWAIT)

			st = time.localtime(time.time())
			day = "%d-%02d-%02d"%(st.tm_year, st.tm_mon, st.tm_mday)
			hour = "%02d"%(st.tm_hour)
			min = "%02d"%(st.tm_min)
			minidx = int(min) / 5
			logfile = "%s/%s/%s/%d/%s.log"%(log_dir, day, hour, minidx, client)

			fd = None
			if client not in client_logs:
				client_logs[client] = {}
				client_logs[client]['logfile'] = logfile
				try:
					client_logs[client]['fd'] = None
					fd = open(logfile, 'a')
					client_logs[client]['fd'] = fd
				except Exception as e:
					continue
			else:
				if client_logs[client]['logfile'] != logfile:
					try:
						if client_logs[client]['fd'] != None:
							client_logs[client]['fd'].close()
							client_logs[client]['fd'] = None
						fd = open(logfile, 'a')
						client_logs[client]['fd'] = fd
					except Exception as e:
						continue
				else:
					fd = client_logs[client]['fd']
			
			if fd != None:
				fd.write(log)
				
		except zmq.Again:
			time.sleep(1)
		except zmq.ZMQError as e:
			print "check feedback zmq Error"
			print e
		except Exception , e:
			print "check feedback Exception"
			print e
			
def main():
	"""main method"""

	while True:
		main_loop()
	
	combiner.close()
	context.term()

if __name__ == "__main__":
	main()


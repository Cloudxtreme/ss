#!/usr/bin/env python
#coding=utf-8
import commands
import os
import sys
import time
import json
import urllib

args = {}

####################################################
def init():
	global args
	global result

	temp = sys.argv[1]
	temp = temp.split('&')
	for info in temp:
		#print info	
		ks = info.split('=')
		args[ks[0]] = ks[1]
	#print args

####################################################
def show_tables():
	global args
	global json_result

	res = []
	ret = commands.getstatusoutput('egrep "^\*" /etc/sysconfig/iptables')
	data = ret[1].split("\n")
	for line in data:
		res.append(line[1:])
	json_result['ret'] = ret[0]
	json_result['result'] = res

####################################################
def show_rules():
	global args
	global json_result

	tables = []
	ret = commands.getstatusoutput('egrep "^\*" /etc/sysconfig/iptables')
	data = ret[1].split("\n")
	for line in data:
		tables.append(line[1:])

	res = {}
	for table in tables:
		cmd = "iptables -t %s -L -n --line-numbers"%(table)
		ret = commands.getstatusoutput(cmd)
		data = ret[1].split("\n")
		res[table] = {}

		is_chain_start = 0
		is_rule_start = 0
		chain = ''
		cols = []

		for line in data:
			line = line.strip()
			if line == '':
				is_chain_start = 0
				is_rule_start = 0
				chain = ''
				continue
			if line.startswith('Chain'):
				temp = line.split()
				chain = temp[1]
				is_chain_start = 1
				res[table][chain] = []
				continue
			if is_chain_start == 1 and is_rule_start == 0 and line.startswith('num'):
				is_rule_start = 1
				cols = line.split()
				#print cols
				continue
			
			rule = {}
			if is_chain_start == 1 and is_rule_start == 1:
				temp = line.split()
				for i in range(len(cols)):
					rule[cols[i]] = temp[i]
				condition = ''
				for x in temp[i+1:]:
					condition += "%s "%(x)
				rule['condition'] = condition.strip()
				res[table][chain].append(rule)
	
	json_result['ret'] = ret[0]
	json_result['result'] = res


####################################################
def add_rule():
	global args
	global json_result

# 'src': '', 'dst_sel': 'ig', 'protocol': 'All', 'target': 'ACCEPT', 'chain': 'INPUT', 
#'out_interface': 'lo', 'dst': '', 'src_sel': 'ig', 
#'state': '', 'module': 'network_firewall.py', 'out_sel': 'ig', 'dport_sel': 'ig', 
#'sport_sel': 'ig', 'in': '', 'dport': '', 'table': 'filter', 'sport': '', 
#'state_sel': 'ig', 'in_interface': 'lo', 'in_sel': 'ig', 'out': ''}

	#print args
	table = args['table']
	chain = args['chain']
	num = args['num']

	protocol = args['protocol']
	target = args['target']

	src_sel = args['src_sel']
	src = args['src']
	dst_sel = args['dst_sel']
	dst = args['dst']

	in_sel = args['in_sel']
	indesc = args['in']
	in_interface = args['in_interface']

	out_sel = args['out_sel']
	outdesc = args['out']
	out_interface = args['out_interface']

	sport_sel = args['sport_sel']
	sport = args['sport']

	dport_sel = args['dport_sel']
	dport = args['dport']

	state_sel = args['state_sel']
	state = args['state']

	ext_module = urllib.unquote(args['ext_module'])
	ext_param = urllib.unquote(args['ext_param'])

	#append
	if num == '0':
		cmd = "iptables -t %s -A %s "%(table, chain)
	else:
		cmd = "iptables -t %s -I %s %s "%(table, chain, num)

	cmd += "-p %s "%(protocol)

	iseq = ''
	if src_sel != 'ig':
		if src_sel == 'nq':
			iseq = '!'
		cmd += "-s %s%s "%(iseq, src)

	iseq = ''
	if dst_sel != 'ig':
		if dst_sel == 'nq':
			iseq = '!'
		cmd += "-d %s%s "%(iseq, dst)

	iseq = ''
	if in_sel != 'ig':
		setdesc = in_interface
		if indesc != '':
			setdesc = indesc
		if in_sel == 'nq':
			iseq = '!'
		cmd += "-i %s%s "%(iseq, setdesc)

	iseq = ''
	if out_sel != 'ig':
		setdesc = out_interface
		if outdesc != '':
			setdesc = outdesc
		if out_sel == 'nq':
			iseq = '!'
		cmd += "-o %s%s "%(iseq, setdesc)

	iseq = ''
	if sport_sel != 'ig' and sport != '':
		if sport_sel == 'nq':
			iseq = '!'
		if sport.find('-') <= 0:
			cmd += "--sport %s%s "%(iseq, sport)
		else:
			sport = sport.split('-')
			cmd += "--sport %s%s-%s "%(iseq, sport[0], sport[1])

	iseq = ''
	if dport_sel != 'ig' and dport != '':
		if dport_sel == 'nq':
			iseq = '!'
		if dport.find('-') <= 0:
			cmd += "--dport %s%s "%(iseq, dport)
		else:
			dsport = dport.split('-')
			cmd += "--dport %s%s-%s "%(iseq, dport[0], dport[1])

	if state_sel != 'ig':
		cmd += "--state %s "%(state)

	cmd += "-j %s %s %s && /etc/init.d/iptables save"%(target, ext_module, ext_param)

	ret = commands.getstatusoutput(cmd)

	json_result['ret'] = ret[0]
	if ret[0] != 0:
		json_result['error'] = ret[1]
	else:
		json_result['result'] = ret[1]

####################################################
def del_rule():
	global args
	global json_result

	#print args
	table = args['table']
	chain = args['chain']
	num = args['num']

	cmd = "iptables -t %s -D %s %s && /etc/init.d/iptables save"%(table, chain, num)

	ret = commands.getstatusoutput(cmd)

	json_result['ret'] = ret[0]
	if ret[0] != 0:
		json_result['error'] = ret[1]
	else:
		json_result['result'] = ret[1]

####################################################
def move_rule():
	global args
	global json_result

	#print args
	table = args['table']
	chain = args['chain']
	num = args['num']
	tonum = args['tonum']

	table_start = chain_start = 0
	id = 1
	rules = {}

	ret = commands.getstatusoutput('cat /etc/sysconfig/iptables')
	lines = ret[1].split("\n")
	for line in lines:
		line = line.strip()
		if line == '':
			continue
		if line[0] == '#':
			continue
		if line[0] == '*' and line[1:] == table:
			table_start = 1
			continue
		if table_start == 1 and line[0] == ':' and line[1:].startswith(chain):
			chain_start = 1
			continue
		if line == 'COMMIT':
			table_start = chain_start = 0
			continue
		if line.find(chain) <= 0:
			continue
		if table_start and chain_start and line[0] == '-':
			#print("%d => %s"%(id, line))
			rules[str(id)] = line
			id += 1

	#print rules[num]
	#print rules[tonum]

	num_rule = rules[tonum].replace('-A', '')
	num_rule = num_rule.replace(chain, '').strip()
	tonum_rule = rules[num].replace('-A', '').strip()
	tonum_rule = tonum_rule.replace(chain, '').strip()
	cmd = "iptables -t %s -R %s %s %s"%(table, chain, num, num_rule)
	cmd += " && iptables -t %s -R %s %s %s"%(table, chain, tonum, tonum_rule)
	cmd += " && /etc/init.d/iptables save"
	#print cmd

	ret = commands.getstatusoutput(cmd)

	json_result['ret'] = ret[0]
	if ret[0] != 0:
		json_result['error'] = ret[1]
	else:
		json_result['result'] = ret[1]

####################################################
if __name__ == '__main__':

	json_result = {}
	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {}

	init()

	if args['opt'] == 'show_tables':
		show_tables()
	elif args['opt'] == 'show_rules':
		show_rules()
	elif args['opt'] == 'add_rule':
		add_rule()
	elif args['opt'] == 'del_rule':
		del_rule()
	elif args['opt'] == 'move_rule':
		move_rule()

	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


#!/usr/bin/env python
#coding=utf-8
import commands
import os
import sys
import time
import json

args = {}
groups = {}
groups_id = {}
users = {}
users_shadow = {}
json_result = {}

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
def init_users_groups():
	global json_result
	global groups, groups_id
	global users, users_shadow

	ret = commands.getstatusoutput('cat /etc/group')
	for line in ret[1].split("\n"):
		info = line.split(':')
		groups[info[0]] = {}
		groups[info[0]]['group'] = info[0]
		groups[info[0]]['groupid'] = info[2]
		groups[info[0]]['member'] = info[3].split(',')
		groups_id[info[2]] = info[0]
	#print groups
	#print groups_id

	ret = commands.getstatusoutput('cat /etc/shadow')
	for line in ret[1].split("\n"):
		info =  line.split(':')
		user = info[0]
		passwd = info[1]
		users_shadow[user] = passwd

	ret = commands.getstatusoutput('cat /etc/passwd')
	for line in ret[1].split("\n"):
		info =  line.split(':')
		users[info[0]] = {}
		user = info[0]
		users[info[0]]['user'] = info[0]
		users[info[0]]['userid'] = info[2]
		users[info[0]]['group'] = groups_id[info[3]]
		users[info[0]]['home'] = info[5]
		users[info[0]]['shell'] = info[6]
		passwd = users_shadow[user]
		users[info[0]]['lock'] = passwd[0] == '!'
	#print users

####################################################
def show_groups():
	global json_result
	global groups, groups_id
	global users

	result = []
	for value in groups.values():
		result.append(value)

	json_result['ret'] = 0
	json_result['result'] = result

####################################################
def show_users():
	global json_result
	global groups, groups_id
	global users

	result = []
	for value in users.values():
		result.append(value)

	json_result['ret'] = 0
	json_result['result'] = result

####################################################
def add_user():
	global args
	global json_result

	user = args['user']
	passwd = args['passwd']
	if len(user) == 0 or len(passwd) == 0:
		return

	cmd = "useradd %s"%(user)
	#print cmd
	ret = commands.getstatusoutput(cmd)
	if ret[0] != 0:
		json_result['ret'] = ret[0]
		json_result['error'] = ret[1]
		return

	cmd = """echo "%s":"%s" | chpasswd"""%(user, passwd)
	ret = commands.getstatusoutput(cmd)
	#print cmd
	json_result['ret'] = ret[0]
	json_result['error'] = ret[1]

####################################################
def del_user():
	global args
	global json_result

	user = args['user']
	if len(user) == 0:
		return
	cmd = "userdel -rf %s"%(user)
	#print cmd
	ret = commands.getstatusoutput(cmd)
	json_result['ret'] = ret[0]
	json_result['error'] = ret[1]

####################################################
def chpasswd_user():
	global args
	global json_result

	user = args['user']
	passwd = args['passwd']
	if len(user) == 0 or len(passwd) == 0:
		return

	cmd = """echo "%s":"%s" | chpasswd"""%(user, passwd)
	ret = commands.getstatusoutput(cmd)
	#print cmd
	json_result['ret'] = ret[0]
	json_result['error'] = ret[1]

####################################################
def lock_user():
	global args
	global json_result

	user = args['user']
	lock = args['lock']
	if len(user) == 0 or len(lock) == 0:
		return

	if lock == 'lock':
		lock = '-L'
	elif lock == 'unlock': 
		lock = '-U'
	else:
		return

	cmd = """usermod %s %s"""%(lock, user)
	ret = commands.getstatusoutput(cmd)
	#print cmd
	json_result['ret'] = ret[0]
	json_result['error'] = ret[1]

####################################################
if __name__ == '__main__':

	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = { 'lock' : '锁定', 'group' : '组', 'groupid' : '组ID', 
			'shell' : 'shell', 'userid' : '用户ID', 'user' : '用户名', 'home' : '用户目录',
			'member' : '成员' }

	init()
	init_users_groups()

	if args['opt'] == 'show_users':
		show_users()
	elif args['opt'] == 'show_groups':
		show_groups()
	elif args['opt'] == 'add_user':
		add_user()
	elif args['opt'] == 'del_user':
		del_user()
	elif args['opt'] == 'chpasswd_user':
		chpasswd_user()
	elif args['opt'] == 'lock_user':
		lock_user()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)


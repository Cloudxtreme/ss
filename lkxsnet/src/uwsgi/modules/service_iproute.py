#!/usr/bin/env python
#coding=utf-8
import sys
import os
import web
import time
import json
import urllib
import commands
import socket
from struct import pack, unpack

args = {}
json_result = {}
ipinfo = None

class IPInfo(object):
    '''QQWry.Dat数据库查询功能集合
    '''
    def __init__(self, dbname):
        ''' 初始化类，读取数据库内容为一个字符串，
        通过开始8字节确定数据库的索引信息'''
        
        self.dbname = dbname
        f = file(dbname, 'r')
        self.img = f.read()
        f.close()

        # QQWry.Dat文件的开始8字节是索引信息,前4字节是开始索引的偏移值，
        # 后4字节是结束索引的偏移值。
        (self.firstIndex, self.lastIndex) = unpack('II', self.img[:8])
        # 每条索引长7字节，这里得到索引总个数
        self.indexCount = (self.lastIndex - self.firstIndex) / 7 + 1
	
    def getString(self, offset = 0):
        ''' 读取字符串信息，包括"国家"信息和"地区"信息

        QQWry.Dat的记录区每条信息都是一个以'\0'结尾的字符串'''
        
        o2 = self.img.find('\0', offset)
        #return self.img[offset:o2]
        # 有可能只有国家信息没有地区信息，
        gb2312_str = self.img[offset:o2]
        try:
            utf8_str = unicode(gb2312_str,'gb2312').encode('utf-8')
        except:
            return '未知'
        return utf8_str

    def getLong3(self, offset = 0):
        '''QQWry.Dat中的偏移记录都是3字节，本函数取得3字节的偏移量的常规表示
        QQWry.Dat使用“字符串“存储这些值'''
        s = self.img[offset: offset + 3]
        s += '\0'
        # unpack用一个'I'作为format，后面的字符串必须是4字节
        return unpack('I', s)[0]

    def getAreaAddr(self, offset = 0):
        ''' 通过给出偏移值，取得区域信息字符串，'''
        
        byte = ord(self.img[offset])
        if byte == 1 or byte == 2:
            # 第一个字节为1或者2时，取得2-4字节作为一个偏移量调用自己
            p = self.getLong3(offset + 1)
            return self.getAreaAddr(p)
        else:
            return self.getString(offset)

    def getAddr(self, offset, ip = 0):
        img = self.img
        o = offset
        byte = ord(img[o])

        if byte == 1:
            # 重定向模式1
            # [IP][0x01][国家和地区信息的绝对偏移地址]
            # 使用接下来的3字节作为偏移量调用字节取得信息
            return self.getAddr(self.getLong3(o + 1))
		
        if byte == 2:
            # 重定向模式2
            # [IP][0x02][国家信息的绝对偏移][地区信息字符串]
            # 使用国家信息偏移量调用自己取得字符串信息
            cArea = self.getAreaAddr(self.getLong3(o + 1))
            o += 4
            # 跳过前4字节取字符串作为地区信息
            aArea = self.getAreaAddr(o)
            return (cArea, aArea)
			
        if byte != 1 and byte != 2:
            # 最简单的IP记录形式，[IP][国家信息][地区信息]
            # 重定向模式1有种情况就是偏移量指向包含国家和地区信息两个字符串
            # 即偏移量指向的第一个字节不是1或2,就使用这里的分支
            # 简单地说：取连续取两个字符串！

            cArea = self.getString(o)
            #o += len(cArea) + 1
            # 我们已经修改cArea为utf-8字符编码了，len取得的长度会有变，
            # 用下面方法得到offset
            o = self.img.find('\0',o) + 1
            aArea = self.getString(o)
            return (cArea, aArea)

    def find(self, ip, l, r):
        ''' 使用二分法查找网络字节编码的IP地址的索引记录'''
        if r - l <= 1:
            return l

        m = (l + r) / 2
        o = self.firstIndex + m * 7
        new_ip = unpack('I', self.img[o: o+4])[0]
        if ip <= new_ip:
            return self.find(ip, l, m)
        else:
            return self.find(ip, m, r)
		
    def getIPAddr(self, ip):
        ''' 调用其他函数，取得信息！'''
        # 使用网络字节编码IP地址
        ip = unpack('!I', socket.inet_aton(ip))[0]
        # 使用 self.find 函数查找ip的索引偏移
        i = self.find(ip, 0, self.indexCount - 1)
        # 得到索引记录
        o = self.firstIndex + i * 7
        # 索引记录格式是： 前4字节IP信息+3字节指向IP记录信息的偏移量
        # 这里就是使用后3字节作为偏移量得到其常规表示（QQWry.Dat用字符串表示值）
        o2 = self.getLong3(o + 4)
        # IP记录偏移值+4可以丢弃前4字节的IP地址信息。
        (c, a) = self.getAddr(o2 + 4)
        return (c, a)
		
    def output(self, first, last):
        for i in range(first, last):
            o = self.firstIndex +  i * 7
            ip = socket.inet_ntoa(pack('!I', unpack('I', self.img[o:o+4])[0]))
            offset = self.getLong3(o + 4)
            (c, a) = self.getAddr(offset + 4)
            print "%s %d %s/%s" % (ip, offset, c, a)


####################################################
def init():
	global args
	global ipinfo

	temp = sys.argv[1]
	temp = temp.split('&')
	for info in temp:
		#print info	
		ks = info.split('=')
		args[ks[0]] = ks[1]
	#print args

	ipinfo = IPInfo('/opt/uwsgi/modules/qqwry.dat')

# sed '3d' -i conf
# sed '3ixxxxxx' -i conf

####################################################
def show_tables():
	global json_result

	ret = commands.getstatusoutput('cat /etc/iproute2/rt_tables')
	tt = {}
	tables = []
	lines = ret[1].split("\n")
	for line in lines:
		line = line.strip()
		if line[0] == '#':
			continue
		line = line.replace("\t", ' ')
		info = line.split()
		if len(info) != 2:
			continue
		tt[info[0].strip()] = info[1].strip()
	keys = tt.keys()
	keys.sort()
	for key in keys:
		tables.append({'id':key, 'table':tt[key]})
	#print tables
	json_result['ret'] = ret[0]
	json_result['result'] = tables

####################################################
def add_table():
	global json_result

	id = args['id']
	table = args['table']
	conf = '/etc/iproute2/rt_tables'
	ret = commands.getstatusoutput('cat /etc/iproute2/rt_tables')
	tables = {}
	lines = ret[1].split("\n")
	for line in lines:
		line = line.strip()
		if line[0] == '#':
			continue
		info = line.split()
		if len(info) != 2:
			continue
		tid = info[0].strip()
		tt = info[1].strip()
		if id == tid or table == tt:
			json_result['ret'] = 1
			json_result['error'] = """id [%s] or table [%s] already in"""%(id, table)
			return

	cmd = """echo "%s\t%s" >> /etc/iproute2/rt_tables"""%(id, table)
	ret = commands.getstatusoutput(cmd)

	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def del_table():
	global json_result

	id = args['id']
	cmd = """sed -i '/^%s/d' /etc/iproute2/rt_tables"""%(id)
	ret = commands.getstatusoutput(cmd)
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def route_add():
	global json_result

	dst = urllib.unquote(args['dst'])
	nexthop = urllib.unquote(args['nexthop'])
	dev = urllib.unquote(args['dev'])
	table = args['table']
	cmd = """ip route replace %s via %s dev %s table %s"""%(dst, nexthop, dev, table)
	ret = commands.getstatusoutput(cmd)
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def route_del():
	global json_result

	dst = urllib.unquote(args['dst'])
	table = args['table']
	cmd = """ip route del %s table %s"""%(dst, table)
	ret = commands.getstatusoutput(cmd)
	json_result['ret'] = ret[0]
	json_result['result'] = ret[1]

####################################################
def list_table():
	global json_result
	global ipinfo

	table = args['table']
	cmd = 'ip route list table ' + table
	ret = commands.getstatusoutput(cmd)
	lines = ret[1].split("\n")
	data = []
	for line in lines:
		line = line.strip()
		if line.find(' via ') <= 0:
			continue
		info = line.split()
		if len(info) != 5:
			continue 
		if info[1] != 'via':
			continue
		dstip = dst = info[0]
		nexthop = info[2]
		dev = info[4]
		if dst.find('/') > 0:
			dstip = dst.split('/')[0]

		dlocal = dtype = nlocal = ntype = ''
		if dstip.count('.') == 3:
			(dlocal, dtype) = ipinfo.getIPAddr(dstip)
		if nexthop.count('.') == 3:
			(nlocal, ntype) = ipinfo.getIPAddr(nexthop)

		data.append({'dst':dst, 'dstinfo':'%s/%s'%(dlocal, dtype),
					'nexthop':nexthop, 'nexthopinfo':'%s/%s'%(nlocal, ntype),
					'dev':dev})
	#print tables
	json_result['ret'] = ret[0]
	json_result['result'] = data

####################################################
def parse_route_conf():
	global json_result
	global ipinfo

	conf = urllib.unquote(args['conf'])
	conf = '/opt/uwsgi/upload/iproute/' + conf
	data = []
	file = open(conf)
	lines = file.readlines()
	for line in lines:
		line = line.strip()
		line = line.replace("\t", ' ')
		if line.find(' via ') <= 0:
			continue
		info = line.split()
		if len(info) != 5:
			continue 
		if info[1] != 'via':
			continue

		dstip = dst = info[0]
		nexthop = info[2]
		dev = info[4]
		if dst.find('/') > 0:
			dstip = dst.split('/')[0]
		#print dstip
		(dlocal, dtype) = ipinfo.getIPAddr(dstip)
		(nlocal, ntype) = ipinfo.getIPAddr(nexthop)
		data.append({'dst':dst, 'dstinfo':'%s/%s'%(dlocal, dtype), 
					'nexthop':nexthop, 'nexthopinfo':'%s/%s'%(nlocal, ntype)})
		#print dst, nexthop, dev
	#print tables
	json_result['ret'] = 0
	json_result['result'] = data

####################################################
def import_route_conf():
	global json_result
	global ipinfo

	table = args['table']
	conf = urllib.unquote(args['conf'])
	conf = '/opt/uwsgi/upload/iproute/' + conf
	file = open(conf)
	lines = file.readlines()
	for line in lines:
		line = line.strip()
		line = line.replace("\t", ' ')
		if line.find(' via ') <= 0:
			continue
		info = line.split()
		if len(info) != 5:
			continue 
		if info[1] != 'via':
			continue

		dst = info[0]
		nexthop = info[2]
		dev = info[4]
		#print dst, nexthop, dev
		cmd = """ip route replace %s via %s dev %s table %s"""%(dst, nexthop, dev, table)
		#print cmd
		ret = commands.getstatusoutput(cmd)
		if ret[0] != 0:
			json_result['ret'] = ret[0]
			json_result['result'] = ret[1]
			return
	json_result['ret'] = 0
	json_result['result'] = ''

if __name__ == '__main__':

	json_result['ret'] = 1
	json_result['error'] = ''
	json_result['result'] = ''
	json_result['descmap'] = {'dst':'目标地址', 'nexthop':'下一跳', 
								'dstinfo':'目标地址ISP信息', 'nexthopinfo':'下一跳ISP信息',
								'dev':'接口', 'default':'默认'}

	init()

	if args['opt'] == 'show_tables':
		show_tables()
	elif args['opt'] == 'add_table':
		add_table()
	elif args['opt'] == 'del_table':
		del_table()
	elif args['opt'] == 'list_table':
		list_table()
	elif args['opt'] == 'route_add':
		route_add()
	elif args['opt'] == 'route_del':
		route_del()
	elif args['opt'] == 'parse_route_conf':
		parse_route_conf()
	elif args['opt'] == 'import_route_conf':
		import_route_conf()
	else:
		json_result['error'] = 'opt error!'

	print json.dumps(json_result)



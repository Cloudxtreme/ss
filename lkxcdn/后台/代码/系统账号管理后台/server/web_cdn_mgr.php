<?php

/* 
	万恶的mysql主从，记得不要用数据库前缀 !!! 
*/

require_once('usercheck.php');

define("SQUID_DNS_CT", 'squid_dns_ct');
define("SQUID_DNS_CNC", 'squid_dns_cnc');

define("NODE_DNS_CREATE_TABLE_SQL", "CREATE TABLE IF NOT EXISTS `%s` (
  									`id` int(11) NOT NULL auto_increment,
  									`name` varchar(255) default NULL,
  									`ttl` int(11) default NULL,
  									`rdtype` varchar(255) default NULL,
  									`rdata` varchar(255) default NULL,
  									PRIMARY KEY  (`id`)
										) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
										
define("NODE_DNS_DROP_TABLE_SQL", "DROP TABLE IF EXISTS `%s`;");										
										
define("NODE_DNS_SOA_INSERT_SQL", "INSERT INTO `%s` (`name`, `ttl`, `rdtype`, `rdata`) VALUES
															('%s', 60, 'SOA', 'ns.%s. root.%s. 2011012600 3600 900 3600 300'),
															('%s', 60, 'NS', 'ns.%s.');");

define("NODE_DNS_NS_INSERT_SQL", "INSERT INTO `%s` (`name`, `ttl`, `rdtype`, `rdata`) VALUES
																('ns.%s', 300, 'A', '%s');");

define("NODE_DNS_A_INSERT_SQL", "INSERT INTO `%s` (`name`, `ttl`, `rdtype`, `rdata`) VALUES																
																('%s', 60, 'A', '%s');");

define("BIND_DNS_SH", "/opt/cdn_node_dns/bind_dns_mgr.sh");

define("DIG_DNS_SH", "/usr/bin/dig @%s %s +short");

$all_server_info = array();
$dns_master = array();
$node_dns_master = array();
$node_dns = array();

//print_r($_POST);

$userid = check_user();
if( ! $userid ) 
{
	print("check user fail!\n");
	exit;
}

if( ! isset($_POST['opcode']) || 
		! isset($_POST['skey']) || 
		! isset($_POST['client_name']) ||
		! isset($_POST['squid_name']) ) 
{
	print("post data error!\n");
	exit;
}
	
$user = $_POST['user'];
$opcode = $_POST['opcode'];
$skey = $_POST['skey'];
$client_name = $_POST['client_name'];
$squid_name = $_POST['squid_name'];

if( strlen($client_name) > 0 && strstr($client_name, '*') )
{
	print("$client_name is dangerous!!!");
	exit;
}

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error()."\n");
	exit;
}

$dbobj->query("set names utf8;");

if( ! init() ) 
{
	print("init error\n");	
	exit;
}

//print_r($all_server_info);
//print_r($node_dns_master);
//print_r($node_dns);

switch( $opcode )
{
	case 'get_client_list': get_client_list(); break;
	case 'add_client': add_client(); break;
	case 'del_client': del_client(); break;
	case 'add_node_dns': add_node_dns(); break;
	case 'del_node_dns': del_node_dns(); break;
	case 'add_bind_dns': add_bind_dns(); break;	
	case 'del_bind_dns': del_bind_dns(); break;
	case 'check_bind_dns': check_bind_dns(); break;	
	case 'reload_bind_dns': reload_bind_dns(); break;			
	case 'test_bind_dns': test_bind_dns(); break;	
		
	//new post
	case 'post_node_update_haproxy': post_node_update_haproxy(); break;
	case 'check_node_cmd': check_node_cmd(); break;				
	case 'check_client_setting': check_client_setting(); break;
	case 'client_add_cdn_domain': client_add_cdn_domain(); break;
	case 'active_client_cdn_domain': set_isactive_client_cdn_domain('true'); break;
	case 'deactive_client_cdn_domain': set_isactive_client_cdn_domain('false'); break;
	case 'client_del_cdn_domain': client_del_cdn_domain(); break;
	case 'check_haproxy_setting':	check_haproxy_setting(); break;		
	case 'export_haproxy_conf': export_haproxy_conf(); break;		
}

function init()
{
	global $dbobj;
	global $all_server_info;
	global $dns_master;
	global $node_dns_master;
	global $node_dns;
	global $skey;

	//get all server ssh info
	$query = "select * from cdn_server_admin.server_list;";
						
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		return false;
	}
	if( ! mysql_num_rows($result) ) 
	{
		print($dbobj->error()."\n");
		return false;
	}

	while( ($row = mysql_fetch_array($result)) ) 
	{
		$ip = $row['ip'];
		$all_server_info[$ip]['type'] = $row['type'];
		$all_server_info[$ip]['nettype'] = $row['nettype'];
		$all_server_info[$ip]['port'] = $row['port'];
		$all_server_info[$ip]['user'] = $row['user'];
		
		$pass = $row['pass'];
		$pass = mydecrypt($skey, $pass);
		$all_server_info[$ip]['pass'] = $pass;
	}
	mysql_free_result($result);	
	
	//get dns master info
	$query = "select * from cdn_web.server_info where serverip in(
					select ip from cdn_web.server_list where type = 'dns_master')
					and `key` = 'dbinfo';";
						
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		return false;
	}
	if( ! mysql_num_rows($result) ) 
	{
		print("$query\n");
		return false;
	}

	while( ($row = mysql_fetch_array($result)) ) 
	{
		$value = $row['value'];
		$value = explode(';', $value);
		//print_r($value);
		$ip = $value[0]; $ip = explode('=', $ip); $ip = $ip[1];
		$user = $value[2]; $user = explode('=', $user); $user = $user[1];
		$pass = $value[3]; $pass = explode('=', $pass); $pass = $pass[1];
		$db = $value[4]; $db = explode('=', $db); $db = $db[1];
		
		$dns_master['ip'] = $ip;
		$dns_master['user'] = $user;
		$dns_master['pass'] = $pass;
		$dns_master['db'] = $db;
	}
	mysql_free_result($result);
	//print_r($dns_master);
			
	//get node dns master info
	$query = "select * from cdn_web.server_info where serverip in(
					select ip from cdn_web.server_list where type = 'internal_dns_master')
					and `key` = 'dbinfo';";
						
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		return false;
	}
	if( ! mysql_num_rows($result) ) 
	{
		print("$query\n");
		return false;
	}

	while( ($row = mysql_fetch_array($result)) ) 
	{
		$value = $row['value'];
		$value = explode(';', $value);
		//print_r($value);
		$ip = $value[0]; $ip = explode('=', $ip); $ip = $ip[1];
		$user = $value[2]; $user = explode('=', $user); $user = $user[1];
		$pass = $value[3]; $pass = explode('=', $pass); $pass = $pass[1];
		$db = $value[4]; $db = explode('=', $db); $db = $db[1];
		
		$node_dns_master[$db]['ip'] = $ip;
		$node_dns_master[$db]['user'] = $user;
		$node_dns_master[$db]['pass'] = $pass;
	}
	mysql_free_result($result);

	//get node dns
	$query = "select * from cdn_web.server_list where `type` = 'internal_dns_ct' or `type` = 'internal_dns_cnc';";
						
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		return false;
	}
	if( ! mysql_num_rows($result) ) 
	{
		print("$query\n");
		return false;
	}

	while( ($row = mysql_fetch_array($result)) ) 
	{
		$ip = $row['ip'];
		$type = $row['type'];
		
		$node_dns[$type][] = $ip;
	}
	mysql_free_result($result);
	
	return true;
}

function get_client_list()
{
	global $dbobj;	

	$query = "select * from `cdn_web`.`domain_info` order by `squidport`;";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		return;
	}	

	if( ! mysql_num_rows($result) ) 
	{
		print("$query\n");
		return;
	}

	print("------------------------------------------");
	print("------------------------------------------");
	print("------------------------------------------");
	print("------------------------------------------\n");
	printf("%-18s | %-50s | %-15s | %-10s | %-18s | %-10s | %-6s | %-s \n", 
	'hostname', 'cname', 'squidname', 'squidport', 'tablename', 'owner', 'status', 'desc');
				
	while( ($row = mysql_fetch_array($result)) ) 
	{
		print("------------------------------------------");
		print("------------------------------------------");
		print("------------------------------------------");
		print("------------------------------------------\n");

		$hostname = $row['hostname'];
		$cname = $row['cname'];
		$squidname = $row['squidname'];
		$squidport = $row['squidport'];
		$tablename = $row['tablename'];
		$owner = $row['owner'];
		$status = $row['status'];
		$desc = $row['desc'];
		
		printf("%-18s | %-50s | %-15s | %-10s | %-18s | %-10s | %-6s | %-s \n", 
		$hostname, $cname, $squidname, $squidport, $tablename, $owner, $status, $desc);
	}
	mysql_free_result($result);

	print("------------------------------------------");
	print("------------------------------------------");
	print("------------------------------------------");
	print("------------------------------------------\n");
}

function add_client()
{
	global $dbobj, $client_name;
	
	if( ! isset($_POST['client_desc']) ) 
	{
		print("post data error add_client()");
		exit;
	}
	$client_desc = $_POST['client_desc'];	

	$query = "select * from `cdn_web`.`user` where `user` = '$client_name';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	
	if( ! mysql_num_rows($result) )
	{
		$query = "insert into `cdn_web`.`user`(`user`, `pass`, `status`, `desc`) values
							('$client_name', md5('$client_name'), 'true', '$client_desc');";
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			exit;
		}
		print("add user $client_name succ!\n");
	}
	else
	{
		mysql_free_result($result);	
		print("user $client_name already exists\n");
	}
}

function del_client()
{
	global $dbobj, $client_name;

	$query = "select * from `cdn_web`.`user` where `user` = '$client_name';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	
	if( mysql_num_rows($result) )
	{
		mysql_free_result($result);	
		
		$query = "update `cdn_web`.`user` set `status` = 'false' where `user` = '$client_name';";
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			exit;
		}
		print("del user $client_name succ!\n");		
	}
	else
	{
		print("no user $client_name !\n");
	}
}

function add_node_dns()
{
	global $node_dns_master;
	global $node_dns;

	if( ! isset($_POST['client_domain']) || ! isset($_POST['client_cdn_domain']) ) 
	{
		print("post data error add_node_dns()");
		exit;
	}
	
	if( ! isset($_POST['ct_ip_data']) || ! isset($_POST['cnc_ip_data']) ) 
	{
		print("post data error add_node_dns()");
		exit;
	}
	
	$client_domain = $_POST['client_domain'];
	$table_name = str_replace('.', '_', $client_domain);
	$table_name = str_replace('-', '_', $table_name);
	$client_cdn_domain = $_POST['client_cdn_domain'];
	
	$ct_ip_data = $_POST['ct_ip_data'];
	$cnc_ip_data = $_POST['cnc_ip_data'];

	$ct_ip_data = explode(';', $ct_ip_data);
	$cnc_ip_data = explode(';', $cnc_ip_data);
	//print_r($ct_ip_data);
	//print_r($cnc_ip_data);
	
	foreach( $node_dns_master as $db => $dbinfo )
	{
		if( $db == SQUID_DNS_CT )
		{
			//print("电信 $db");
			if( array_key_exists('internal_dns_ct', $node_dns) ) {
				if( ! add_node_dns_internal($dbinfo, SQUID_DNS_CT, $table_name, $client_domain, $client_cdn_domain, $node_dns['internal_dns_ct'], $ct_ip_data) ) {
					print('add_node_dns_internal error ct');exit;
				}
			}
		}
		else if( $db == SQUID_DNS_CNC )
		{
			//print("网通 $db");
			if( array_key_exists('internal_dns_cnc', $node_dns) ) {
				if( ! add_node_dns_internal($dbinfo, SQUID_DNS_CNC, $table_name, $client_domain, $client_cdn_domain, $node_dns['internal_dns_cnc'], $cnc_ip_data) ) {
					print('add_node_dns_internal error cnc');exit;
				}
			}
		}
		else {
		}
	}
}

function add_node_dns_internal($dbinfo, $db_name, $table_name, $client_domain, $client_cdn_domain, $nsips, $ips)
{
	$dbobj = new DBObj;
	if( ! $dbobj->conn2($dbinfo['ip'], $dbinfo['user'], $dbinfo['pass']) ) 
	{
		print($dbobj->error()."\n");
		return false;
	}
	$dbobj->query("set names utf8;");
	$dbobj->select_db($db_name);// import !!!

	//check table 	
	$query = "show tables like '$table_name';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( mysql_num_rows($result) )
	{
		mysql_free_result($result);	
		print("$db_name $table_name already exists\n");
		exit;
	}
			
	$query = sprintf(NODE_DNS_CREATE_TABLE_SQL, $table_name);
	//print($query);
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		return false;
	}
		
	$query = sprintf(NODE_DNS_SOA_INSERT_SQL, $table_name, $client_domain, $client_domain, 
									$client_domain, $client_domain, $client_domain);
	//print($query);
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		return false;
	}
	
	foreach( $nsips as $ip ) 
	{
		$query = sprintf(NODE_DNS_NS_INSERT_SQL, $table_name, $client_domain, $ip);
		//print($query);
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			return false;
		}
	}	
	
	foreach( $ips as $ip ) 
	{
		$query = sprintf(NODE_DNS_A_INSERT_SQL, $table_name, $client_cdn_domain, $ip);
		//print($query);
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			return false;
		}
	}	
	
	return true;
}

function del_node_dns()
{
	global $node_dns_master;

	if( ! isset($_POST['client_domain']) ) 
	{
		print("post data error del_node_dns()");
		exit;
	}
	
	$client_domain = $_POST['client_domain'];
	$table_name = str_replace('.', '_', $client_domain);
	$table_name = str_replace('-', '_', $table_name);
	
	foreach( $node_dns_master as $db => $dbinfo )
	{
		//print_r($db);
		//print_r($dbinfo);
		$dbobj = new DBObj;
		if( ! $dbobj->conn2($dbinfo['ip'], $dbinfo['user'], $dbinfo['pass']) ) 
		{
			print($dbobj->error()."\n");
			return false;
		}
		$dbobj->query("set names utf8;");
		$dbobj->select_db($db);
		
		$query = sprintf(NODE_DNS_DROP_TABLE_SQL, $table_name);
		//print_r($query);
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			return false;
		}
	}
}

function add_bind_dns()
{
	global $node_dns;
	
	if( ! isset($_POST['client_domain']) || ! isset($_POST['client_cdn_domain']) ) 
	{
		print("post data error add_bind_dns()");
		exit;
	}
	
	$client_domain = $_POST['client_domain'];
	$table_name = str_replace('.', '_', $client_domain);
	$table_name = str_replace('-', '_', $table_name);
	$client_cdn_domain = $_POST['client_cdn_domain'];

	$cmd = BIND_DNS_SH . " add $client_domain $table_name";
	
	bind_server_run($cmd);
}

function del_bind_dns()
{
	global $node_dns;

	if( ! isset($_POST['client_domain']) ) 
	{
		print("post data error del_bind_dns()");
		exit;
	}
	
	$client_domain = $_POST['client_domain'];
		
	$cmd = BIND_DNS_SH . " del $client_domain";
	
	bind_server_run($cmd);	
}

function check_bind_dns()
{
	global $node_dns;
	
	$cmd = BIND_DNS_SH . " check";
	
	bind_server_run($cmd);
}

function reload_bind_dns()
{
	global $node_dns;
	
	$cmd = BIND_DNS_SH . " reload";
	
	bind_server_run($cmd);
}

function test_bind_dns()
{
	global $node_dns;

	if( ! isset($_POST['client_cdn_domain']) ) 
	{
		print("post data error test_bind_dns()");
		exit;
	}
	$client_cdn_domain = $_POST['client_cdn_domain'];
		
	foreach( $node_dns as $ips )
	{
		foreach( $ips as $ip )
		{
			$cmd = sprintf(DIG_DNS_SH, $ip, $client_cdn_domain, $client_cdn_domain);
			print("$ip $cmd\n");
			system($cmd);
		}
	}
}

function bind_server_run($cmd)
{
	global $node_dns;
	global $all_server_info;
	
	print("$cmd\n");
	
	if( ! array_key_exists('internal_dns_ct', $node_dns) ) 
	{
		print('! array_key_exists(internal_dns_ct, $node_dns)');
		exit;
	}
	
	$ips = $node_dns['internal_dns_ct'];
	$bindinfo = array();
	foreach( $ips as $ip ) 
	{
		if( ! array_key_exists($ip, $all_server_info) ) 
		{
			print('! array_key_exists($ip, $all_server_info)');
			exit;
		}
		$bindinfo[$ip] = $all_server_info[$ip];
	}
	
	foreach( $bindinfo as $ip => $info )
	{
		$port = $info['port'];
		$user = $info['user'];
		$pass = $info['pass'];
		//print("$ip $port $user $pass");
		
		$conn = ssh2_connect($ip, $port);
		if( ! $conn ) 
		{
			print("ssh2_connect $ip $port\n");
			continue;
		}
		if( ! ssh2_auth_password($conn, $user, $pass) ) 
		{
			print("ssh2_auth_password $ip $port $user $pass \n");
			continue;
		}
		$stream = ssh2_exec($conn, $cmd);
		if( ! $stream ) 
		{
			print("ssh2_exec $ip $port $cmd \n");
			continue;
		}
		stream_set_blocking($stream, true);
		$ret = stream_get_contents($stream);
		print("$ip\n$ret\n\n");
	}
}

function this_server_run_cmd($cmd)
{
	$port = 22;
	$user = '';
	$pass = '';
	
	print("$cmd\n");

	$conn = ssh2_connect('127.0.0.1', $port);
	if( ! $conn ) 
	{
		print("ssh2_connect $port\n");
		return;
	}
	if( ! ssh2_auth_password($conn, $user, $pass) ) 
	{
		print("ssh2_auth_password $port $user $pass \n");
		return;
	}
	$stream = ssh2_exec($conn, $cmd);
	if( ! $stream ) 
	{
		print("ssh2_exec $port $cmd \n");
		return;
	}
	stream_set_blocking($stream, true);
	$ret = stream_get_contents($stream);
	print("$ret\n\n");	
	return $ret;
}

function _add_client_cdn_domain()
{
	global $dbobj;	
	global $client_name, $squid_name;

	if( ! isset($_POST['client_port']) || 
			! isset($_POST['client_domain']) ||
			! isset($_POST['client_cname_domain']) ||
			! isset($_POST['client_desc']) 
			) 
	{
		print("post data error add_client()");
		exit;
	}

	$client_port = $_POST['client_port'];
	$client_domain = $_POST['client_domain'];
	$table_name = str_replace('.', '_', $client_domain);
	$table_name = str_replace('-', '_', $table_name);
	$client_cname_domain = $_POST['client_cname_domain'];
	$client_desc = $_POST['client_desc'];

	$query = "select * from `cdn_web`.`domain_info` where `squidport` = '$client_port';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( mysql_num_rows($result) )
	{
		while( ($row = mysql_fetch_array($result)) ) 
		{
			if( $client_domain == $row['hostname'] ) 
			{
				print("$client_domain already exists \n");
				exit;
			}
			if( $client_cname_domain != $row['cname'] )
			{
				print("$client_cname_domain != $row[cname] \n");
				exit;
			}
			if( $squid_name != $row['squidname'] )
			{
				print("$squid_name != $row[squidname] \n");
				exit;			
			}
			if( $table_name == $row['tablename'] )
			{
				print("$table_name $row[tablename] already exists \n");
				exit;
			}
			if( $client_name != $row['owner'] )
			{
				print("$client_name != $row[owner] \n");
				exit;			
			}
		}
		mysql_free_result($result);		
	}
	
	$query = "select * from `cdn_web`.`domain_info` where `hostname` = '$client_domain' and `cname` = '$client_cname_domain';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( ! mysql_num_rows($result) )
	{
		$query = "insert into `cdn_web`.`domain_info`(`hostname`, `cname`, `squidname`, `squidport`, `tablename`, `owner`, `status`, `desc`)
						values('$client_domain', '$client_cname_domain', '$squid_name', '$client_port',
									'$table_name', '$client_name', 'false', '$client_desc');";
							
		if( ! ($result = $dbobj->query($query)) ) {
			print($dbobj->error()."\n"); exit;
		}
		print("succ !!! \n");
	}
	else
	{
		mysql_free_result($result);	
		
		print("cname $client_cname_domain already exists\n");
	}
}

function _del_client_cdn_domain()
{/*
	global $dbobj;	
	global $client_name, $squid_name;

	if( ! isset($_POST['client_port']) || 
			! isset($_POST['client_domain']) ||
			! isset($_POST['client_cname_domain']) ||
			! isset($_POST['client_desc']) 
			) 
	{
		print("post data error add_client()");
		exit;
	}

	$client_port = $_POST['client_port'];
	$client_domain = $_POST['client_domain'];
	$table_name = str_replace('.', '_', $client_domain);
	$table_name = str_replace('-', '_', $table_name);
	$client_cname_domain = $_POST['client_cname_domain'];
	$client_desc = $_POST['client_desc'];

	$query = "update `cdn_web`.`domain_info` 
						set `status` = 'false' where 
						`squidname` = '$squid_name' and 
						`squidport` = '$client_port' and
						`owner` = '$client_name'";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	$rows = mysql_affected_rows($dbobj->link);
	print("affected_rows $rows > 0 for succ ! \n");*/
}

function _post_node_create_client()
{
	global $dbobj;	
	global $squid_name;
	global $all_server_info;
	
	if( ! isset($_POST['client_port']) ) 
	{
		print("post data error create_squid()");
		exit;
	}
	$client_port = $_POST['client_port'];	
	if( $client_port == '3306' ) 
	{
		print("mysql port !!! \n");
		exit;		
	}
	if( $client_port < 3000 || $client_port > 4000 ) 
	{
		print("squid port 3000 ~ 4000 !!! \n");
		exit;				
	}
	
	$query = "select * from `cdn_web`.`domain_info` where 
						`status` = 'true' and
						(`squidname` = '$squid_name' or `squidport` = '$client_port');";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( mysql_num_rows($result) )
	{
		mysql_free_result($result);	
		print("squidname $squid_name or squidport $client_port already used\n");
		exit;
	}

	$query = "select * from `cdn_server_admin`.`server_cmd` where `type` = 'webcdn';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( mysql_num_rows($result) )
	{
		mysql_free_result($result);	
		print("node server cmd is not empty! \n");
		exit;
	}

	$cmdargs = "$squid_name $client_port";

	foreach( $all_server_info as $ip => $info )
	{
		if( $info['type'] != 'webnode' ) { continue; }
		
		$query = "insert into `cdn_server_admin`.`server_cmd`(`ip`, `type`, `status`, `cmd`, `args`, `timestamp`) 
		values('$ip', 'webcdn', 'ready', 'node_create_client', '$cmdargs', now());";
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			exit;
		}
	}
}

function _post_node_delete_client()
{
	global $dbobj;	
	global $squid_name;
	global $all_server_info;
	
	if( ! isset($_POST['client_port']) ) 
	{
		print("post data error create_squid()");
		exit;
	}
	$client_port = $_POST['client_port'];	

	$query = "select * from `cdn_web`.`domain_info` where `squidname` = '$squid_name' or `squidport` = '$client_port';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( ! mysql_num_rows($result) )
	{
		print("no squidname $squid_name and squidport $client_port !!!\n");
		exit;
	}
	mysql_free_result($result);	

	$query = "select * from `cdn_server_admin`.`server_cmd` where `type` = 'webcdn';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( mysql_num_rows($result) )
	{
		mysql_free_result($result);	
		print("node server cmd is not empty! \n");
		exit;
	}

	$cmdargs = "$squid_name";

	foreach( $all_server_info as $ip => $info )
	{
		if( $info['type'] != 'webnode' ) { continue; }
		
		$query = "insert into `cdn_server_admin`.`server_cmd`(`ip`, `type`, `status`, `cmd`, `args`, `timestamp`) 
		values('$ip', 'webcdn', 'ready', 'node_delete_client', '$cmdargs', now());";
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			exit;
		}
	}	
}

function post_node_update_haproxy()
{
	global $dbobj;	
	global $all_server_info;
	
	$query = "select * from `cdn_server_admin`.`server_cmd` where `type` = 'webcdn';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( mysql_num_rows($result) )
	{
		mysql_free_result($result);	
		print("node server cmd is not empty! \n");
		exit;
	}

	foreach( $all_server_info as $ip => $info )
	{
		if( $info['type'] != 'webnode' ) { continue; }
		
		$query = "insert into `cdn_server_admin`.`server_cmd`(`ip`, `type`, `status`, `cmd`, `args`, `timestamp`) 
		values('$ip', 'webcdn', 'ready', 'node_update_haproxy', '', now());";
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			exit;
		}
	}		
}

function check_node_cmd()
{
	global $dbobj;	

	$query = "select * from `cdn_server_admin`.`server_cmd` where `type` = 'webcdn';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( ! mysql_num_rows($result) ) {
		exit;
	}

	print("------------------------------------------");
	print("------------------------------------------");
	print("------------------------------------------\n");

	while( ($row = mysql_fetch_array($result)) ) 
	{
		$ip = $row['ip'];
		$status = $row['status'];
		$cmd = $row['cmd'];
		$args = $row['args'];
		$ret = $row['ret'];
		$timestamp = $row['timestamp'];

		printf("%-18s | %10s | %25s | %20s | %20s | %18s \n", 
		$ip, $status, $cmd, $args, $ret, $timestamp);
	}
	mysql_free_result($result);	

	print("------------------------------------------");
	print("------------------------------------------");
	print("------------------------------------------\n");	
}

function check_client_setting()
{
	global $dbobj;	
	global $client_name;

	$query = "select * from `cdn_web`.`domain_info` where `owner` = '$client_name';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( ! mysql_num_rows($result) )
	{
		print("no this $client_name client\n");
		exit;
	}

	print("------------------------------------------");
	print("------------------------------------------");
	print("------------------------------------------\n");
	printf("%-20s | %-50s | %-20s | %-10s | %-5s \n", 'hostname', 'cname', 'squidname', 'squidport', 'status');

	$ports = array();
	while( ($row = mysql_fetch_array($result)) ) 
	{
		print("------------------------------------------");
		print("------------------------------------------");
		print("------------------------------------------\n");
		
		$hostname = $row['hostname'];
		$cname = $row['cname'];
		$squidname = $row['squidname'];
		$squidport = $row['squidport'];
		$status = $row['status'];
		printf("%-20s | %-50s | %-20s | %-10s | %-5s \n", $hostname, $cname, $squidname, $squidport, $status);
		
		$ports[$squidport] = $squidport;
	}
	mysql_free_result($result);	

	print("------------------------------------------");
	print("------------------------------------------");
	print("------------------------------------------\n");	
}

function client_add_cdn_domain()
{
	global $dbobj;	
	global $client_name, $squid_name;

	if( ! isset($_POST['client_domain']) || ! isset($_POST['client_port']) ) 
	{
		print("post data error client_add_cdn_domain()");
		exit;
	}
	
	$client_domain = $_POST['client_domain'];
	$client_port = $_POST['client_port'];
	
	$query = "select * from `cdn_server_admin`.`server_cmd` where `type` = 'webcdn';";
	if( ! ($result = $dbobj->query($query)) ) { print($dbobj->error()."\n"); exit; }
	if( mysql_num_rows($result) ) 
	{
		print("node server cmd is not empty! \n");
		mysql_free_result($result);
		exit;
	}
	
	//check if exist squid	
	$query = "select * from `cdn_web`.`domain_info` where 
					`squidname` = '$squid_name' and
					`squidport` = '$client_port' and
					`owner` = '$client_name';";
	//print($query);					
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	$rows = mysql_num_rows($result); 
	if( $rows > 0 ) { mysql_free_result($result); }
	
	$backendname = _haproxy_get_backend_name($client_port);
	if( ! $backendname ) 
	{ 
		//print("no squidport $client_port backend name\n"); exit;
		$backendname = $squid_name;
	}

	if( $rows > 0 )	
	{
		_add_client_cdn_domain();
		_haproxy_add_domain($client_domain, $client_port, $backendname, false);
		post_node_update_haproxy();	// squid was exist, so only set haproxy conf
	} 
	else 
	{
		_add_client_cdn_domain();
		_haproxy_add_domain($client_domain, $client_port, $backendname, true);
		_post_node_create_client();	// create squid and set haproxy conf
	}
}

function set_isactive_client_cdn_domain($isactive)
{
	global $dbobj;	
	global $client_name, $squid_name;

	if( ! isset($_POST['client_port']) || 
			! isset($_POST['client_domain']) ||
			! isset($_POST['client_cname_domain']) ||
			! isset($_POST['client_desc']) 
			) 
	{
		print("post data error add_client()");
		exit;
	}

	$client_port = $_POST['client_port'];
	$client_domain = $_POST['client_domain'];
	$table_name = str_replace('.', '_', $client_domain);
	$table_name = str_replace('-', '_', $table_name);
	$client_cname_domain = $_POST['client_cname_domain'];
	$client_desc = $_POST['client_desc'];

	$query = "select * from `cdn_web`.`domain_info` where 
						`hostname` = '$client_domain' and 
						`cname` = '$client_cname_domain' and
						`squidname` = '$squid_name' and
						`squidport` = '$client_port' and
						`owner` = '$client_name';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( ! mysql_num_rows($result) )
	{
		print("no this $client_domain or data not all match !\n");
		exit;
	}
	else
	{
		mysql_free_result($result);	
		
		$query = "update `cdn_web`.`domain_info` set `status` = '$isactive' where 
							`hostname` = '$client_domain' and 
							`cname` = '$client_cname_domain' and
							`squidname` = '$squid_name' and
							`squidport` = '$client_port' and
							`owner` = '$client_name';";
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			exit;
		}
		print("succ!\n");
	}	
}

function client_del_cdn_domain()
{
	global $dbobj;	
	global $client_name, $squid_name;

	if( ! isset($_POST['client_domain']) || 
			! isset($_POST['client_port']) ) 
	{
		print("post data error client_add_cdn_domain()");
		exit;
	}

	$client_domain = $_POST['client_domain'];
	$client_port = $_POST['client_port'];

	$query = "select * from `cdn_server_admin`.`server_cmd` where `type` = 'webcdn';";
	if( ! ($result = $dbobj->query($query)) ) { print($dbobj->error()."\n"); exit; }
	if( mysql_num_rows($result) ) 
	{
		print("node server cmd is not empty! \n");
		mysql_free_result($result);
		exit;
	}	
	
	//check if exist squid	
	$query = "select * from `cdn_web`.`domain_info` where 
					`status` = 'true' and
					`squidname` = '$squid_name' and
					`squidport` = '$client_port' and
					`owner` = '$client_name';";
					
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	$rows = mysql_num_rows($result); 
	if( $rows > 0 ) { 
		mysql_free_result($result); 
	}

	$backendname = _haproxy_get_backend_name($client_port);
	if( ! $backendname ) { 
		print("no squidport $client_port backend name\n"); exit;
	}
	
	if( $rows > 0 )	
	{
		_del_client_cdn_domain();
		_haproxy_del_domain($client_domain, $client_port, $backendname, false);
		post_node_update_haproxy();	// squid was exist, so only set haproxy conf
	} 
	else 
	{
		_del_client_cdn_domain();
		_haproxy_del_domain($client_domain, $client_port, $backendname, true);
		_post_node_delete_client();	// delet squid and set haproxy conf
	}
}

function check_haproxy_setting()
{
	global $dbobj;	

	$ports = array();
	$acls = array();
	$use_backends = array();
	$backends = array();
	$owners = array();
	
	//	1	... get haproxy conf
	$query = "select * from cdn_server_admin.haproxy_conf where `status` = 'true';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( ! mysql_num_rows($result) )
	{
		print("no result! \n");
		exit;
	}
	
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$session = $row['session'];
		$name = $row['name'];
		$key = $row['key'];
		$operator = $row['operator'];
		$value = $row['value'];
		
		if( $session == 'backend' ) 
		{
			$ports[$value]['backend'] = $name;
			$backends[$name]['port']	= $value;
		}
		else if( $session == 'use_backend' ) 
		{
			$use_backends[$name][] = $value;
		}
		else if( $session == 'acl' ) 
		{
			$hostname = explode(' ', $value);
			$acls[$name] = $hostname[1];
		}
	}
	mysql_free_result($result);	
	
	//	2 ... get user conf
	$query = "select * from `cdn_web`.`domain_info` where `status` = 'true';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( ! mysql_num_rows($result) )
	{
		print("no result\n");
		exit;
	}

	while( ($row = mysql_fetch_array($result)) ) 
	{
		$owner = $row['owner'];
		$port = $row['squidport'];
		
		$ports[$port]['owner'] = $owner;
		$owners[$owner][$port] = $port;
	}
	mysql_free_result($result);	
	
	//print_r($ports);
	//print_r($backends);
	//print_r($use_backends);
	//print_r($acls);
	//print_r($owners);

	foreach( $owners as $owner => $portlist )
	{
		print("[$owner]\n");
		foreach( $portlist as $port )
		{
			$backname = $ports[$port]['backend'];
			print("\t[$backname:$port] => ");
			foreach( $use_backends[$backname] as $acl )
			{
				$hostname = $acls[$acl];
				print("[$hostname] ");
			}
			print("\n");
		}
		print("\n");
	}
}

function export_haproxy_conf()
{
	$ret = this_server_run_cmd('cd /var/www/html/webadmin/server/ && php /var/www/html/webadmin/server/haproxy_conf_export.php');
	$ret = ltrim($ret); $ret = rtrim($ret);
	print("ret = [$ret] \n");
	if( $ret == '' ) {
		print("succ!\n");
	} else {
		exit;
	}
		
	$ret = this_server_run_cmd('/opt/haproxy/sbin/haproxy -c -f /var/www/html/webadmin/server/haproxy.cfg');
	$ret = ltrim($ret); $ret = rtrim($ret);
	print("ret = [$ret] \n");
	if( $ret == 'Configuration file is valid' ) {
		print("succ!\n");
	} else {
		exit;
	}	
}

function _haproxy_get_backend_name($squidport)
{
	global $dbobj;	
	
	/*
	session 			name 						key 		operator 				value
	
	acl 					is.96mmo.com 						hdr_end(host) 	-i 96mmo.com
	use_backend 	96mmo.com 	  					if 							is.96mmo.com
	backend 			96mmo.com 			port 	  								3210
	*/
	$backendname = false;
	$query = "select * from cdn_server_admin.haproxy_conf where 
						`status` = 'true' and 
						`session` = 'backend' and 
						`key` = 'port' and
						`value` = '$squidport';";
	if( ! ($result = $dbobj->query($query)) ) {
		print($dbobj->error()." $query \n"); return false;
	}
	if( ! mysql_num_rows($result) ) {
		print("no result !\n"); return false;
	}
	$row = mysql_fetch_array($result);
	if( $row )
	{
		$backendname = $row['name'];
		mysql_free_result($result);			
	}
	return $backendname;
}

function _haproxy_add_domain($domain, $squidport, $backendname, $is_create_backend)
{
	global $dbobj;	
	
	/*
	session 			name 						key 		operator 				value
	
	acl 					is.96mmo.com 						hdr_end(host) 	-i 96mmo.com
	use_backend 	96mmo.com 	  					if 							is.96mmo.com
	backend 			96mmo.com 			port 	  								3210
	*/
	
	// 1.check domain and acl
	$query = "select * from cdn_server_admin.haproxy_conf where `status` = 'true' and `session` = 'acl';";
	if( ! ($result = $dbobj->query($query)) ) {
		print($dbobj->error()." $query \n"); exit;
	}
	if( ! mysql_num_rows($result) ) {
		print("no result !\n"); exit;
	}
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$hostname = explode(' ', $row['value']);
		$hostname = $hostname[1];
		if( strstr($domain, $hostname) ) {
			print("acl $row[value] already exist!\n"); exit;
		}
	}
	mysql_free_result($result);		
	
	$aclname = 'is.'.$domain;
	$domainmatch = "-i $domain";
	
	if( $is_create_backend )
	{
		// create backend
		
		// check squid port
		$query = "select * from cdn_server_admin.haproxy_conf where `status` = 'true' and 
							`session` = 'backend' and 
							( `name` = '$backendname' or `value` = '$squidport' );";
		if( ! ($result = $dbobj->query($query)) ) {
			print($dbobj->error()." $query \n"); exit;
		}
		if( mysql_num_rows($result) ) {
			print("squidport $squidport was used !\n"); exit;
		}
		mysql_free_result($result);	
		
		$query = "insert into cdn_server_admin.haproxy_conf(`session`, `name`, `key`, `operator`, `value`, `status`) values
							('acl', '$aclname', '', 'hdr_end(host)', '$domainmatch', 'true'),
							('use_backend', '$backendname', '', 'if', '$aclname', 'true'),
							('backend', '$backendname', 'port', '', '$squidport', 'true');";
		//print($query);							
					
		if( ! ($result = $dbobj->query($query)) ) {
			print($dbobj->error()." $query \n"); exit;
		} else { 
			print("succ!!!\n");
		}
	}
	else
	{
		// add domain to backend

		// check backend
		$query = "select * from cdn_server_admin.haproxy_conf where `status` = 'true' and 
							`session` = 'backend' and 
							`name` = '$backendname' and
							`value` = '$squidport';";
		if( ! ($result = $dbobj->query($query)) ) {
			print($dbobj->error()." $query \n"); exit;
		}
		if( ! mysql_num_rows($result) ) {
			print("no $backendname:$squidport !\n"); exit;
		}
		mysql_free_result($result);	
		
		$query = "insert into cdn_server_admin.haproxy_conf(`session`, `name`, `key`, `operator`, `value`, `status`) values
							('acl', '$aclname', '', 'hdr_end(host)', '$domainmatch', 'true'),
							('use_backend', '$backendname', '', 'if', '$aclname', 'true');";
		//print($query);										
		
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()." $query \n"); exit;
		} else {
			print("succ!!!\n");
		}		
	}
	
	export_haproxy_conf();
}

function _haproxy_del_domain($domain, $squidport, $backendname, $is_delete_backend)
{
	global $dbobj;	

	/*
	session 			name 						key 		operator 				value
	
	acl 					is.96mmo.com 						hdr_end(host) 	-i 96mmo.com
	use_backend 	96mmo.com 	  					if 							is.96mmo.com
	backend 			96mmo.com 			port 	  								3210
	*/
	
	// 1.check domain and acl
	$query = "select * from cdn_server_admin.haproxy_conf where `status` = 'true' and `session` = 'acl';";
	if( ! ($result = $dbobj->query($query)) ) {
		print($dbobj->error()."\n"); exit;
	}
	if( ! mysql_num_rows($result) ) {
		print("no result !\n"); exit;
	}
	$found = false;
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$hostname = explode(' ', $row['value']);
		$hostname = $hostname[1];
		if( $domain == $hostname ) {
			$found = true; break;
		}
	}
	mysql_free_result($result);		
	
	if( ! $found ) { print("not found $domain acl \n"); exit; }
	
	$aclname = 'is.'.$domain;
	$domainmatch = "-i $domain";
	
	// check squid port
		$query = "select * from cdn_server_admin.haproxy_conf where `status` = 'true' and 
							`session` = 'backend' and 
							`name` = '$backendname' and
							`value` = '$squidport';";
	if( ! ($result = $dbobj->query($query)) ) {
		print($dbobj->error()."\n"); exit;
	}
	if( ! mysql_num_rows($result) ) {
		print("no $backendname:$squidport !\n"); exit;
	}
	mysql_free_result($result);		
	
	if( $is_delete_backend )
	{
		// delete backend
		$query = "update cdn_server_admin.haproxy_conf set `status` = 'false' where `session` = 'acl' and `name` = '$aclname';";
		if( ! ($result = $dbobj->query($query)) ) { print($dbobj->error()."\n"); exit; }
		$query = "update cdn_server_admin.haproxy_conf set `status` = 'false' where `session` = 'use_backend' and `name` = '$backendname';";
		if( ! ($result = $dbobj->query($query)) ) { print($dbobj->error()."\n"); exit; }
		$query = "update cdn_server_admin.haproxy_conf set `status` = 'false' where `session` = 'backend' and `name` = '$backendname';";
		if( ! ($result = $dbobj->query($query)) ) { print($dbobj->error()."\n"); exit; }
					
		print("succ!!!\n");
	}
	else
	{
		// delete domain from backend
		$query = "update cdn_server_admin.haproxy_conf set `status` = 'false' where `session` = 'acl' and `name` = '$aclname';";
		if( ! ($result = $dbobj->query($query)) ) { print($dbobj->error()."\n"); exit; }
		$query = "update cdn_server_admin.haproxy_conf set `status` = 'false' where `session` = 'use_backend' and `value` = '$aclname';";
		if( ! ($result = $dbobj->query($query)) ) { print($dbobj->error()."\n"); exit; }							
					
		print("succ!!!\n");
	}
		
	export_haproxy_conf();
}

?>

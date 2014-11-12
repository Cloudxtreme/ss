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
															('%s', 300, 'SOA', 'ns.%s. root.%s. 2011012600 3600 900 3600 300'),
															('%s', 300, 'NS', 'ns.%s.');");

define("NODE_DNS_NS_INSERT_SQL", "INSERT INTO `%s` (`name`, `ttl`, `rdtype`, `rdata`) VALUES
																('ns.%s', 300, 'A', '%s');");

define("NODE_DNS_A_INSERT_SQL", "INSERT INTO `%s` (`name`, `ttl`, `rdtype`, `rdata`) VALUES																
																('%s', 300, 'A', '%s');");

define("BIND_DNS_SH", "/opt/cdn_node_dns/bind_dns_mgr.sh");

define("DIG_DNS_SH", "/usr/bin/dig @%s %s +short");

$all_server_info = array();
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
		! isset($_POST['client_name']) ) 
{
	print("post data error!\n");
	exit;
}
	
$user = $_POST['user'];
$opcode = $_POST['opcode'];
$skey = $_POST['skey'];
$client_name = $_POST['client_name'];

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
$dbobj->select_db('cdn_web');// import !!!

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
		
	case 'check_client_setting': check_client_setting(); break;
	case 'client_add_cdn_domain': client_add_cdn_domain(); break;
	case 'client_del_cdn_domain': client_del_cdn_domain(); break;
}

function init()
{
	global $dbobj;
	global $all_server_info;
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
	
	//get node dns master info
	$query = "select * from server_info where serverip in(
					select ip from server_list where `type` in ('internal_dns_master', 'internal_dns_master_bk', 'internal_dns_master_nb_bk'))
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
		
		$node_dns_master[$db][$ip]['ip'] = $ip;
		$node_dns_master[$db][$ip]['user'] = $user;
		$node_dns_master[$db][$ip]['pass'] = $pass;
	}
	mysql_free_result($result);
	//print_r($node_dns_master);

	//get node dns
	$query = "select * from server_list where `type` in
					( 'internal_dns_ct', 'internal_dns_cnc', 'internal_dns_ct_bk', 'internal_dns_cnc_bk', 'internal_dns_nb_ct_bk', 'internal_dns_nb_cnc_bk' );";
						
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
	//print_r($node_dns);
	
	return true;
}

function get_client_list()
{
	global $dbobj;	

	$query = "select * from `user_hostname`;";
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

	$userinfo = array();
	//id	hostname 	domainname 	cname 	owner 	status
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$status = $row['status'];
		$owner = $row['owner'];
		$domainname = $row['domainname'];
		$hostname = $row['hostname'];
		
		$userinfo[$owner][$domainname][$hostname] = $hostname;
	}
	mysql_free_result($result);
	
	foreach( $userinfo as $owner => $infos )
	{
		printf("%s \n", $owner);
		
		foreach( $infos as $domain => $hostnames )
		{
			printf("\t%s\n", $domain);
			foreach( $hostnames as $hostname ) {
				printf("\t\t\t\t%s\n", $hostname);
			}
			printf("\n");
		}
	}
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

	$query = "select * from `user` where `user` = '$client_name';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	
	if( ! mysql_num_rows($result) )
	{
		$query = "insert into `user`(`user`, `pass`, `status`, `desc`) values
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

	$query = "select * from `user` where `user` = '$client_name';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	
	if( mysql_num_rows($result) )
	{
		mysql_free_result($result);	
		
		$query = "update `user` set `status` = 'false' where `user` = '$client_name';";
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
	
	foreach( $node_dns_master as $db => $dbinfo_list )
	{
		if( $db == SQUID_DNS_CT )
		{
			//print("电信 $db");
			foreach( $dbinfo_list as $ip => $dbinfo )
			{
				//print_r($dbinfo);
				
				if( $ip == 'squiddns.data.efly.cc' )
				{
					if( array_key_exists('internal_dns_ct', $node_dns) ) {
						if( ! add_node_dns_internal($dbinfo, SQUID_DNS_CT, $table_name, $client_domain, $client_cdn_domain, $node_dns['internal_dns_ct'], $ct_ip_data) ) {
							print('add_node_dns() add_node_dns_internal error ct');exit;
						}
					}
				}
				else if( $ip == 'cdn.fsbackup2.efly.cc' )
				{
					if( array_key_exists('internal_dns_ct_bk', $node_dns) ) {
						if( ! add_node_dns_internal($dbinfo, SQUID_DNS_CT, $table_name, $client_domain, $client_cdn_domain, $node_dns['internal_dns_ct_bk'], $ct_ip_data) ) {
							print('add_node_dns() internal_dns_ct_bk error ct');exit;
						}
					}					
				}
				else if( $ip == '115.238.154.2' )
				{
					if( array_key_exists('internal_dns_nb_ct_bk', $node_dns) ) {
						if( ! add_node_dns_internal($dbinfo, SQUID_DNS_CT, $table_name, $client_domain, $client_cdn_domain, $node_dns['internal_dns_nb_ct_bk'], $ct_ip_data) ) {
							print('add_node_dns() internal_dns_nb_ct_bk error ct');exit;
						}
					}					
				}
				else {
				}
			}
		}
		else if( $db == SQUID_DNS_CNC )
		{
			//print("网通 $db");
			foreach( $dbinfo_list as $ip => $dbinfo )
			{
				//print_r($dbinfo);
				
				if( $ip == 'squiddns.data.efly.cc' )
				{
					if( array_key_exists('internal_dns_cnc', $node_dns) ) {
						if( ! add_node_dns_internal($dbinfo, SQUID_DNS_CNC, $table_name, $client_domain, $client_cdn_domain, $node_dns['internal_dns_cnc'], $cnc_ip_data) ) {
							print('add_node_dns() internal_dns_cnc error cnc');exit;
						}
					}
				}
				else if( $ip == 'cdn.fsbackup2.efly.cc' )
				{
					if( array_key_exists('internal_dns_cnc_bk', $node_dns) ) {
						if( ! add_node_dns_internal($dbinfo, SQUID_DNS_CNC, $table_name, $client_domain, $client_cdn_domain, $node_dns['internal_dns_cnc_bk'], $ct_ip_data) ) {
							print('add_node_dns() internal_dns_cnc_bk error cnc');exit;
						}
					}					
				}
				else if( $ip == '60.12.209.2' )
				{
					if( array_key_exists('internal_dns_nb_cnc_bk', $node_dns) ) {
						if( ! add_node_dns_internal($dbinfo, SQUID_DNS_CNC, $table_name, $client_domain, $client_cdn_domain, $node_dns['internal_dns_nb_cnc_bk'], $ct_ip_data) ) {
							print('add_node_dns() internal_dns_cnc_nb_bk error cnc');exit;
						}
					}					
				}
			
				else {
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
	
	foreach( $node_dns_master as $db => $dbinfo_list )
	{
		foreach( $dbinfo_list as $dbinfo )
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
}

function add_bind_dns()
{
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
	$cmd = BIND_DNS_SH . " check";
	
	bind_server_run($cmd);
}

function reload_bind_dns()
{
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
	
	$ips = array_merge($node_dns['internal_dns_ct'], $node_dns['internal_dns_ct_bk'], $node_dns['internal_dns_nb_ct_bk']);
	//print_r($ips);
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

function check_client_setting()
{
	global $dbobj;	
	global $client_name;

	$query = "select * from `user_hostname` where `owner` = '$client_name';";
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

	$userinfo = array();
	//id	hostname 	domainname 	cname 	owner 	status
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$status = $row['status'];
		$owner = $row['owner'];
		$domainname = $row['domainname'];
		$hostname = $row['hostname'];
		
		$userinfo[$owner][$domainname][$hostname] = $hostname;
	}
	mysql_free_result($result);
	
	foreach( $userinfo as $owner => $infos )
	{
		printf("%s \n", $owner);
		
		foreach( $infos as $domain => $hostnames )
		{
			printf("\t%s\n", $domain);
			foreach( $hostnames as $hostname ) {
				printf("\t\t\t\t%s\n", $hostname);
			}
			printf("\n");
		}
	}
}

function client_add_cdn_domain()
{
	global $dbobj;	
	global $client_name;

	if( ! isset($_POST['client_domain']) || ! isset($_POST['client_cdn_domain'])) 
	{
		print("post data error client_add_cdn_domain()");
		exit;
	}
	
	$client_domain = $_POST['client_domain'];
	$client_cdn_domain = $_POST['client_cdn_domain'];
	$table_name = str_replace('.', '_', $client_domain);
	$table_name = str_replace('-', '_', $table_name);
	
	//check if exist 
	$query = "select * from `user_hostname` where 
					`domainname` = '$client_domain' and
					`owner` = '$client_name';";
	//print($query);					
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	$rows = mysql_num_rows($result); 
	if( $rows > 0 ) 
	{ 
		mysql_free_result($result); 
		print("user $client_name and domainname $client_domain already exists\n");		
		exit;
	}

	//temp code ...
	$query = "INSERT INTO `domain_info` (`id`, `hostname`, `cname`, `squidname`, `squidport`, `tablename`, `owner`, `status`, `desc`) 
						VALUES(NULL, '$client_domain', '', '$client_name', '3128', '$table_name', '$client_name', 'true', '');";
	$ret = $dbobj->query($query);	
	if( $ret == true ) {
		print("succ!\n");
	} else {
		print("fail! $query \n");
	}	
	
	$query = "insert into user_hostname(`hostname`, `domainname`, `tablename`, `owner`, `status`)
					values('$client_cdn_domain', '$client_domain', '$table_name', '$client_name', 'true');";
	$ret = $dbobj->query($query);	
	if( $ret == true ) {
		print("succ!\n");
	} else {
		print("fail! $query \n");
	}	
}

function client_del_cdn_domain()
{
	global $dbobj;	
	global $client_name;

	if( ! isset($_POST['client_domain']) ) 
	{
		print("post data error client_add_cdn_domain()");
		exit;
	}
	$client_domain = $_POST['client_domain'];
	
	$query = "update `domain_info`
						set `status` = 'false'
						where `hostname` = '$client_domain' and
						`owner` = '$client_name';";
	$ret = $dbobj->query($query);	

	
	$query = "delete from `user_hostname` where 
					`domainname` = '$client_domain' and
					`owner` = '$client_name';";
	$ret = $dbobj->query($query);	
	if( $ret == true ) {
		print("succ!\n");
	} else {
		print("fail! $query \n");
	}	
}

?>

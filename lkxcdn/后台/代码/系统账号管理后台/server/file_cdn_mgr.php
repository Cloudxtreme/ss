<?php

/* 
	万恶的mysql主从，记得不要用数据库前缀 !!! 
*/

require_once('usercheck.php');

$all_server_info = array();
$file_stats_info = array();

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
		! isset($_POST['client_port']) ) 
{
	print("post data error!\n");
	exit;
}
	
$user = $_POST['user'];
$opcode = $_POST['opcode'];
$skey = $_POST['skey'];
$client_name = $_POST['client_name'];
$client_port = $_POST['client_port'];
$ftp_pass = $_POST['ftp_pass'];

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
$dbobj->select_db('cdn_file');//import!!!

if( ! init() ) 
{
	print("init error\n");	
	exit;
}

//print_r($all_server_info);

switch( $opcode )
{
	case 'add_ftp':
		add_ftp();
		break;
		
	case 'del_ftp':
		del_ftp();
		break;
		
	case 'get_client_list':
		get_client_list();
		break;
		
	case 'add_nginx':
		add_nginx();
		break;	

	case 'del_nginx':
		del_nginx();
		break;
	
	case 'update_nginx':
		update_nginx();
		break;
		
	case 'check_nginx':
		check_nginx();
		break;
		
	case 'reload_nginx':
		reload_nginx();
		break;
		
	case 'add_port_rate':
		add_port_rate();
		break;
		
	case 'del_port_rate':
		del_port_rate();
		break;		
	
	case 'update_port_rate':
		update_port_rate();
		break;
		
	case 'create_client_flux_db':
		create_client_flux_db();
		break;
	
	case 'drop_client_flux_db':
		drop_client_flux_db();
		break;
		
	case 'add_client_flux':
		add_client_flux();
		break;
	
	case 'del_client_flux':
		del_client_flux();
		break;
	
	case 'update_client_flux':
		update_client_flux();	
		break;
		
	case 'add_client':
		add_client();
		break;
		
	case 'del_client':
		del_client();
		break;		
}

function init()
{
	global $dbobj;
	global $all_server_info;
	global $file_stats_info;
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
	
	//get cdn file stats db info
	$query = "select * from cdn_file.server_list, cdn_file.server_info where `type` = 'cdn_file_stats' and ip = serverip;";
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

	$row = mysql_fetch_array($result);
	if( $row )
	{
		$value = $row['value'];
		mysql_free_result($result);		
		
		$value = explode(';', $value);
		foreach( $value as $info )
		{
			$info = explode('=', $info);
			$file_stats_info[$info[0]] = $info[1];
		}
	}
	//print_r($file_stats_info);
	
	return true;
}

function get_client_list()
{
	global $dbobj;	

	$query = "select * from `cdn_file`.`user` where `type` = 'cdnfile' order by `nginxport`;";
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

	while( ($row = mysql_fetch_array($result)) ) 
	{
		print("------------------------------------------");
		print("------------------------------------------");
		print("------------------------------------------");
		print("------------------------------------------\n");
		$clientname = $row['user'];
		$nginxport = $row['nginxport'];
		$status = $row['status'];
		$path = $row['path'];
		$stats = $row['stats'];
		$desc = $row['desc'];
		
		printf("%-10s | %4s | %5s | %20s | %30s | %s \n", $clientname, $nginxport, $status, $path, $stats, $desc);
	}
	mysql_free_result($result);
	
	print("------------------------------------------");
	print("------------------------------------------");
	print("------------------------------------------");
	print("------------------------------------------\n");
}

function add_ftp()
{
	global $client_name;
	
	if( ! isset($_POST['ftp_pass']) ) 
	{
		print("post data error!\n");
		exit;
	}
	
	$ftp_pass = $_POST['ftp_pass'];
	$cmd = "/opt/cdn_ftp_mgr.sh add $client_name $ftp_pass";

	ftp_server_run_cmd($cmd);		
}

function del_ftp()
{
	global $client_name;
	
	$cmd = "/opt/cdn_ftp_mgr.sh del $client_name";
	
	ftp_server_run_cmd($cmd);
}

function add_nginx()
{
	global $client_name, $client_port;

	$cmd = "/var/www/html/webadmin/server/nginx_conf_mgr.sh add $client_name $client_port";
	this_server_run_cmd($cmd);
}

function del_nginx()
{
	global $client_name, $client_port;

	$cmd = "/var/www/html/webadmin/server/nginx_conf_mgr.sh del $client_name";
	this_server_run_cmd($cmd);	
}

function update_nginx()
{
	//$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	//$url = str_replace('file_cdn_mgr.php', 'nginx.conf', $url);
	
	$cmd = "/bin/cp -f /var/www/html/webadmin/server/nginx.conf /var/ftp/upload/nginx.conf";
	this_server_run_cmd($cmd);
	
	//$url = 'ftp://s1.efly.cc/upload/nginx.conf';
	$url = 'http://s1.efly.cc/webadmin/server/nginx.conf';
	$cmd = "wget $url -O /opt/nginx/conf/nginx.conf";
	
	file_node_server_run_cmd('电信', $cmd);
	file_node_server_run_cmd('网通', $cmd);
	file_node_server_run_cmd('移动', $cmd);			
}

function check_nginx()
{
	$cmd = "/opt/nginx/sbin/nginx -t -c /opt/nginx/conf/nginx.conf > /tmp/xxx 2>&1 && cat /tmp/xxx";
	file_node_server_run_cmd('电信', $cmd);
	file_node_server_run_cmd('网通', $cmd);
	file_node_server_run_cmd('移动', $cmd);
}

function reload_nginx()
{
	$cmd = "/opt/nginx/sbin/nginx -s reload";
	file_node_server_run_cmd('电信', $cmd);
	file_node_server_run_cmd('网通', $cmd);
	file_node_server_run_cmd('移动', $cmd);	
}

function add_port_rate()
{
	global $client_name, $client_port;

	$cmd = "/var/www/html/webadmin/server/file_port_rate_mgr.sh add $client_port";
	this_server_run_cmd($cmd);
}

function del_port_rate()
{
	global $client_name, $client_port;

	$cmd = "/var/www/html/webadmin/server/file_port_rate_mgr.sh del $client_port";
	this_server_run_cmd($cmd);	
}

function update_port_rate()
{
	$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	
	$ct_url = str_replace('file_cdn_mgr.php', 'file_port_rate_config_ct', $url);
	$cmd = "wget $ct_url -O /opt/cachemgr/port_rate_config";
	//print_r("$cmd\n");
	file_node_server_run_cmd('电信', $cmd);
	
	$cnc_url = str_replace('file_cdn_mgr.php', 'file_port_rate_config_cnc', $url);
	$cmd = "wget $cnc_url -O /opt/cachemgr/port_rate_config";
	//print_r("$cmd\n");
	file_node_server_run_cmd('网通', $cmd);			
}

function create_client_flux_db()
{
	global $client_name, $client_port;
	
	$cmd = "mysql -uroot -prjkj@rjkj -e \"CREATE DATABASE IF NOT EXISTS cdn_".
				$client_name."_nginx_stats default charset utf8 COLLATE utf8_general_ci;\"";
	//print_r("$cmd\n");
	file_node_server_run_cmd('电信', $cmd);
	file_node_server_run_cmd('网通', $cmd);			
	file_node_server_run_cmd('移动', $cmd);
}

function drop_client_flux_db()
{
	global $client_name, $client_port;

	global $client_name, $client_port;
	
	$cmd = "mysql -uroot -prjkj@rjkj -e \"DROP DATABASE cdn_".$client_name."_nginx_stats;\"";
	print_r("$cmd\n");
	file_node_server_run_cmd('电信', $cmd);
	file_node_server_run_cmd('网通', $cmd);
	file_node_server_run_cmd('移动', $cmd);				
}

function add_client_flux()
{
	global $client_name, $client_port;
	
	$cmd = "/var/www/html/webadmin/server/nginx_tools_conf_mgr.sh add $client_name";
	this_server_run_cmd($cmd);	
}

function del_client_flux()
{
	global $client_name, $client_port;

	$cmd = "/var/www/html/webadmin/server/nginx_tools_conf_mgr.sh del $client_name";
	this_server_run_cmd($cmd);	
}

function update_client_flux()
{
	global $client_name, $client_port;

	//wget http://119.145.254.42/cdndist/64/nginx_tools_config -O /opt/nginx_tools/config
	//$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	//$url = str_replace('file_cdn_mgr.php', 'nginx_tools_config', $url);
		
	$cmd = "/bin/cp -f /var/www/html/webadmin/server/nginx_tools_config /var/ftp/upload/nginx_tools_config";
	this_server_run_cmd($cmd);
	
	//$url = 'ftp://s1.efly.cc/upload/nginx_tools_config';
	$url = 'http://s1.efly.cc/webadmin/server/nginx_tools_config';
	$cmd = "wget $url -O /opt/nginx_tools/config";
	//print_r("$cmd\n");
	file_node_server_run_cmd('电信', $cmd);
	file_node_server_run_cmd('网通', $cmd);
	file_node_server_run_cmd('移动', $cmd);
}

function this_server_run_cmd($cmd)
{
	$port = 7997;
	$user = 'root';
	$pass = 'rjkj@rjkj';
	
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
}

function ftp_server_run_cmd($cmd)
{
	global $all_server_info;

	print("$cmd\n");	
	
	foreach( $all_server_info as $ip => $info )
	{
		if( $info['type'] == 'filecdnftp' ) 
		{
			$port = $info['port'];
			$user = $info['user'];
			$pass = $info['pass'];
			
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
}

function file_node_server_run_cmd($nettype, $cmd)
{
	global $all_server_info;
	
	print("$nettype $cmd\n");
	
	foreach( $all_server_info as $ip => $info )
	{
		if( $info['type'] == 'filenode' && $info['nettype'] == $nettype ) 
		{
			$port = $info['port'];
			$user = $info['user'];
			$pass = $info['pass'];
			
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
	return;
}

function add_client()
{
	global $dbobj;	
	global $client_name, $client_port, $ftp_pass;
	global $file_stats_info;
	
	$client_desc = '';
	if( isset($_POST['client_desc']) ) {
		$client_desc = $_POST['client_desc'];
	}

	$dbobj->select_db('cdn_file');// import !!!
	
	$query = "select * from `user` where `user` = '$client_name'";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		exit;
	}
	if( ! mysql_num_rows($result) )
	{
		mysql_free_result($result);	
		
		$path = "/var/ftp/pub/$client_name";
		$stats = "cdn_".$client_name."_nginx_stats";
		
		$query = "insert into `user`(`user`, `pass`, `type`, `nginxport`, `status`, `path`, `stats`, `desc`) 
							values('$client_name', md5('$ftp_pass'), 'cdnfile', '$client_port', 'true', '$path', '$stats', '$client_desc');";
		print("$query\n");
		
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			exit;
		}		
	}
	else
	{
		print("user $client_name already exists\n");
	}	

	$query = "select * from server_list where `type` = 'node' and lower(`nettype`) != 'bgp' and `nettype` != '移动'";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		return;
	}
	if( ! mysql_num_rows($result) ) 
	{
		print($dbobj->error()."\n");
		return;
	}
	
	$file_node_list = array();
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$file_node_list[] = $row['ip'];
	}
	mysql_free_result($result);	
		
	//print_r($file_node_list);
	
	$query = "select * from `user_nginx` where `user` = '$client_name';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		return;
	}
	if( ! mysql_num_rows($result) ) 
	{
		mysql_free_result($result);	
	
		$query = "INSERT INTO `user_nginx` (`user`, `ip`, `port`) VALUES";
		foreach( $file_node_list as $ip ) {
			$query = $query . "('$client_name', '$ip', '$client_port'),";
		}
		$query = substr($query, 0, -1);
		$query = $query . ";";
	
		print_r("$query\n");
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error()."\n");
			exit;
		}	
	}
	else
	{
		print("user $client_name user_nginx already exists\n");
	}

	//create client stats db
	//print_r($file_stats_info);
	$statsdb = new DBObj;
	if( ! $statsdb->conn2($file_stats_info['ip'], $file_stats_info['user'], $file_stats_info['pass']) ) 
	{
		print($statsdb->error()."\n");
		return false;
	}
	$statsdb->query("set names utf8;");

	$query = "CREATE DATABASE IF NOT EXISTS `cdn_".$client_name."_nginx_stats` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
	print("$query\n");
	if( ! ($result = $statsdb->query($query)) ) 
	{
		print($statsdb->error()."\n");
		exit;
	}

	$query = "GRANT ALL PRIVILEGES ON `cdn_".$client_name."_nginx_stats` . * TO 'cdn'@'%' WITH GRANT OPTION;";
	print("$query\n");
	if( ! ($result = $statsdb->query($query)) ) 
	{
		print($statsdb->error()."\n");
		exit;
	}	
}

function del_client()
{
	global $dbobj;	
	global $client_name;

	$dbobj->select_db('cdn_file');//import!!!

	$query = "delete from `user_nginx` where `user` = '$client_name';";
	print_r($query);
	$query = "delete from `user_hostname` where owner not in (select `user` from `user`);;";
	print_r($query);		
}

?>

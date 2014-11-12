<?php
//中文UTF-8
require_once('db.php');

//HTTPSQS_PUT_OK
define("HTTPSQS_PUT_FMT", "http://127.0.0.1:1218?name=dns_db_query&opt=%s&data=%s");

global $global_cdn_domain;
global $global_cdn_web_ip;
global $global_cdn_web_db;
global $global_databasename;

$node_list = array();
$cname_list = array();
$error_node_list = array();
$nettype_iplist = array();
$backup_iplist = array();

$dns_list = array();
$table_ip_list = array();

if( $argc != 2 ) { exit; }
$check_table_name = $argv[1];

$checktype = 'ct';
if( substr($check_table_name, 0, 4) == 'cnc_' ) {
	$checktype = 'cnc';	
}

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	myexit();
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

//get node list
//////////////////////////////////////////////////////////////////////////////////////////////////////
init_node_list();
//print_r($node_list); print_r($nettype_iplist); exit;


//check node port
//////////////////////////////////////////////////////////////////////////////////////////////////////
/*
foreach( $node_list as $ip )
{
	//check haproxy
	if( ! check_node_port($ip, 80) )
	{
		$error_node_list[$ip] = $ip;
		print(date('Y-m-d H:i:s'));
		print(" node $ip port error \n");		
		delete_node($ip);
		continue;
	}
}
*/

//print("$checktype\n");
foreach( $nettype_iplist[$checktype] as $ip )
{
	//check haproxy and squid
	$httpcode = 0;
	$checkname = "www.efly.cc.$global_cdn_domain";
	$ret = check_node($ip, '80', $checkname, $httpcode);
	//print("$ip $checkname $ret $httpcode \n");
	if( ! $ret )
	{
		$error_node_list[$ip] = $ip;
		print(date('Y-m-d H:i:s'));
		print(" node $ip $httpcode haproxy or squid error \n");		
		delete_node($ip);
		continue;
	}
}
//print_r($error_node_list); exit;

//get dns list
//////////////////////////////////////////////////////////////////////////////////////////////////////
$query = "select * from dns_list;";
if( ! ($result = db_query($dbobj, $query)) ) {
    print($dbobj->error()); myexit();
}

while( ($row = mysql_fetch_array($result)) )
{
	$id = $row['id'];
	$name = $row['name'];
	$status = $row['status'];
			
	$cname_list[$name] = $status;
			
	$dns_list[$name]['id'] = $id;
	$dns_list[$name]['status'] = $status;
	$dns_list[$name]['error_node_list'] = array();
	$dns_list[$name]['ip_list'] = array();
	$dns_list[$name]['check_ip_list'] = array();
}
mysql_free_result($result);

//print_r($cname_list); print_r($dns_list); exit;

$temp_list = $dns_list;
foreach( $temp_list as $name => $info )
{
	$id = $info['id'];
	$query = "select * from ip_list where `dnsid` = '$id' and `tablename` = '$check_table_name';";

	if( ! ($result = db_query($dbobj, $query)) ) {
		print($dbobj->error()); continue;
	}

	if( ! mysql_num_rows($result) ) { continue; }
	
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$id = $row['id'];
		$ip = $row['rdata'];
		$ttl = $row['ttl'];
		$table = $row['tablename'];

		if( array_key_exists($ip, $error_node_list) ) { continue; }

		$dns_list[$name]['check_ip_list'][$ip] = $ip;
		$dns_list[$name]['ip_list'][$id]['ip'] = $ip;
		$dns_list[$name]['ip_list'][$id]['ttl'] = $ttl;
		
		$dns_info['name'] = $name;
		$dns_info['ttl'] = $row['ttl'];
		$dns_info['rdtype'] = $row['rdtype'];
		$dns_info['rdata'] = $row['rdata'];
		
		$table_ip_list[] = $dns_info;
	}
	mysql_free_result($result);		
}

//print_r($dns_list); print_r($table_ip_list); exit;

//check node
//////////////////////////////////////////////////////////////////////////////////////////////////////
$temp_node_list = $node_list;
foreach( $temp_node_list as $ip )
{
	if( array_key_exists($ip, $error_node_list) ) { continue; }
	
	foreach( $cname_list as $cname => $status )
	{
		if( $status == 'false' ) { continue; }
		if( $status == 'nochecksrc' ) { continue; }
		
		//check my table only!
		if( ! array_key_exists($ip, $dns_list[$cname]['check_ip_list']) ) { continue; }
		
		//check my node ip only!
		if( ! array_key_exists($ip, $node_list) ) { continue; }
	
		$httpcode = 0;
		//$ret = check_node($ip, '80', $cname, $httpcode);
		$ret = true;
		//print("$cname $ip $httpcode $ret \n");
		if( ! $ret ) 
		{
			print("$cname $ip $httpcode $ret \n");
			delete_node_name($ip, $cname);
			$dns_list[$cname]['error_node_list'][$ip] = $ip;
		}
	}
}

//print_r($dns_list); exit;

//update node
//////////////////////////////////////////////////////////////////////////////////////////////////////
foreach( $dns_list as $name => $info )
{
	$status = $info['status'];
	if( $status == 'false' ) { continue; }

	foreach( $info['ip_list'] as $ipinfo )
	{
		$ip = $ipinfo['ip'];
		$ttl = $ipinfo['ttl'];
			
		$ret = ! array_key_exists($ip, $info['error_node_list']);
		//print("update_node $name $table $ip $ttl $ret \n");
		update_node($name, $ip, $ttl , $ret);
	}

	//check backup node
	//only status == true can backup 	
	if( $status == 'true' ) { 
		check_node_backup($name);
	}
}

//clear table item
//////////////////////////////////////////////////////////////////////////////////////////////////////
$query = "select * from `$check_table_name` where `rdtype` = 'A' and name not like 'ns.%' and name != '$global_cdn_domain';";
//print("$query \n");exit;

if( ! ($result = db_query($dbobj, $query)) ) {
	print($dbobj->error()); myexit();
}

if( ! mysql_num_rows($result) ) { continue; }

$item_list = array();
while( ($row = mysql_fetch_array($result)) ) 
{
	$id = $row['id'];
	$item_list[$id]['id'] = $row['id'];
	$item_list[$id]['name'] = $row['name'];
	$item_list[$id]['ttl'] = $row['ttl'];
	$item_list[$id]['rdtype'] = $row['rdtype'];
	$item_list[$id]['rdata'] = $row['rdata'];		
}	
mysql_free_result($result);
//print_r($item_list);
	
foreach( $item_list as $info )
{
	if( check_delete($info) ) 
	{
		$id = $info['id'];
		$ip = $info['rdata'];
		$query = "delete from `$check_table_name` where `id` = '$id';";
		$ret = db_query($dbobj, $query);
		print(date('Y-m-d H:i:s'));
		print(" $query ip = $ip $ret \n");
	}
}
	
//my function
//////////////////////////////////////////////////////////////////////////////////////////////////////

function myexit()
{
	exit;
}

function init_node_list()
{
	global $global_cdn_web_ip, $global_databaseuser, $global_databasepwd;
	global $global_cdn_web_db;
	global $node_list, $nettype_iplist;
	
	$dbobj = new DBObj;
	if( ! $dbobj->conn2($global_cdn_web_ip, $global_databaseuser, $global_databasepwd) ) {
		print($dbobj->error()); myexit();
	}
	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_cdn_web_db);
	
	$query = "select * from server_list where `type` = 'node' and (`nettype` = '电信' or `nettype` = '网通') ;";
	if( ! ($result = db_query($dbobj, $query)) ) {
		print($dbobj->error()); myexit();
	}
	
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$ip = $row['ip'];
		$nettype = $row['nettype'];
		if( $nettype == '电信' ) {
			$nettype = 'ct';
		} else {
			$nettype = 'cnc';
		}
			
		$node_list[$ip] = $ip;
		$nettype_iplist[$nettype][$ip] = $ip;
	}
	mysql_free_result($result);
}

function check_node_port($ip, $port)
{
	global $global_cdn_domain;
	global $global_server_status_try;
	
	$i = 0;
	while( $i++ < $global_server_status_try )
	{
		$fp = @fsockopen($ip, $port, $errno, $errstr, 5);
		if( ! $fp ) {
			continue;
		}
		fclose($fp);
		return true;
	}
	return false;
}

function check_node($ip, $port, $domain, &$httpcode)
{
	global $global_cdn_domain;
	global $global_server_status_try;
	
	$ret = $http_ret = '';
	$i = 0;
	
	$domain = str_replace($global_cdn_domain, '', $domain);
	$domain = substr($domain, 0, strlen($domain) - 1);
	$domain = "www.google.com";
	
	while( $i++ < $global_server_status_try )
	{
		$ret = $http_ret = '';
		$fp = fsockopen($ip, $port, $errno, $errstr, 5);
		if( ! $fp ) {
			continue;
		}
		$out = "HEAD / HTTP/1.1\r\n";
		$out .= "Host: $domain\r\n";
		$out .= "Connection: Close\r\n\r\n";

		$wret = fwrite($fp, $out);
		if( $wret === false) { fclose($fp); continue; }

		while( ! feof($fp) ) 
		{
			$temp = fgets($fp, 1024);
			if( $temp === false ) { break; }
			$ret .= $temp;
			if( $http_ret == '' ) { $http_ret = $ret; }
		}
		fclose($fp);
		if( stristr($ret, 'X-Cache') || stristr($ret, 'squid') ) { return true; }
		$http_ret = explode(' ', $http_ret);
		if( count($http_ret) < 2 ) { continue; }
		$httpcode = $http_ret[1];
		if( $httpcode >= '200' && $httpcode <= '503' ) { return true; }
		return false;
		
	}
	return false;
}

function delete_node($ip)
{
	global $dbobj;
	global $check_table_name;

	$query = "delete from `$check_table_name` where `rdata` = '$ip';";
	$ret = db_query($dbobj, $query);
	print(date('Y-m-d H:i:s'));
	print(" $query $ret \n");
}

function delete_node_name($ip, $name)
{
	global $dbobj;
	global $check_table_name;

	$query = "delete from `$check_table_name` where `name` = '$name' and `rdata` = '$ip';";
	$ret = db_query($dbobj, $query);
	print(date('Y-m-d H:i:s'));
	print(" $query $ret \n");
}

function update_node($name, $ip, $ttl, $isok)
{
	global $dbobj;
	global $check_table_name;
	
	$query = "select * from `$check_table_name` where `name` = '$name' and `ttl` = '$ttl' and `rdtype` = 'A' and `rdata` = '$ip';";
	if( ! ($result = db_query($dbobj, $query)) ) {
		print($dbobj->error()); myexit();
	}
	
	$cnt = mysql_num_rows($result);
	//print_r($cnt);
	if( $cnt ) { mysql_free_result($result); }
	
	if( $isok && $cnt ) { return; }
	
	if( ! $isok && $cnt )
	{
		//delete
		$query = "delete from `$check_table_name` where `name` = '$name' and `ttl` = '$ttl' and `rdtype` = 'A' and `rdata` = '$ip';";
		$ret = db_query($dbobj, $query);
		print(date('Y-m-d H:i:s'));
		print(" $query $ret \n");
	}	
	
	if( $isok && ! $cnt )
	{
			//$httpcode = 0;
 	    //$ret = check_node($ip, '80', $name, $httpcode);
			//add
			$query = "insert into `$check_table_name`(`name`, `ttl`, `rdtype`, `rdata`) values('$name', '$ttl', 'A', '$ip');";
			$ret = db_query($dbobj, $query);
			print(date('Y-m-d H:i:s'));
			print(" $query $ret \n");
	}
}

function check_node_backup($name)
{
	global $dbobj;
	global $nettype_iplist;
	global $node_list;
	global $backup_iplist;
	global $dns_list;
	global $error_node_list;
	global $check_table_name;

	if( ! array_key_exists($name, $dns_list) ) { return; }

	if( ! isset($dns_list[$name]['ip_list']) ) { return; } 

	$query = "select * from `$check_table_name` where `name` = '$name';";
	//print("check_node_backup $query \n");
	if( ! ($result = db_query($dbobj, $query)) ) {
		print($dbobj->error()); myexit();
	}

	$cnt = mysql_num_rows($result);
  //print_r($cnt);
	if( $cnt ) 
	{
		if( $cnt == 1 )
		{
			$row = mysql_fetch_array($result);
			$ip = $row['rdata'];
			$backup_iplist[$name][$ip] = $ip;
		}
		mysql_free_result($result);
		return;
	}

	$nettype = get_nettype($check_table_name);
	$temp_iplist = $nettype_iplist[$nettype];
	shuffle($temp_iplist);
	foreach( $temp_iplist as $ip )
	{
		if( array_key_exists($ip, $error_node_list) ) { continue; }
		
		//if( ! check_node($ip, '80', $name, $httpcode) ) { continue; }
		
		$query = "insert into `$check_table_name`(`name`, `ttl`, `rdtype`, `rdata`) values('$name', '300', 'A', '$ip');";
		$ret = db_query($dbobj, $query);
		print(date('Y-m-d H:i:s'));
		print("backupnode $query $ret \n");			
		$backup_iplist[$name][$ip] = $ip;
		//break;
	}
}

function check_delete($info)
{
	global $table_ip_list;
	global $backup_iplist;

	foreach( $table_ip_list as $dns_info )
	{
		if( $info['name'] == $dns_info['name'] &&
			$info['ttl'] == $dns_info['ttl'] &&
			$info['rdtype'] == $dns_info['rdtype'] &&
			$info['rdata'] == $dns_info['rdata'] ) {
				return false;
			}
	}

	if( array_key_exists($info['name'], $backup_iplist) &&
		array_key_exists($info['rdata'], $backup_iplist[$info['name']]) ) {
		return false;
	}

	return true;
}

function get_nettype($name)
{
	$nettype = explode('_', $name);
	return $nettype[0];
}

function db_query($dbobj, $query)
{
	/*
	if( stristr($query, 'delete') || strstr($query, 'insert') || strstr($query, 'update') ) {
		print("$query\n");
		return true;
	} else {
		return $dbobj->query($query);
	}
	*/

	return $dbobj->query($query);

/*
	if( stristr($query, 'delete') || strstr($query, 'insert') || strstr($query, 'update') ) 
	{
		$httpsqs_data = urlencode($query);

		$opts = array('http'=>array('method'=>"GET",'timeout'=>3,));
		$context = stream_context_create($opts);
		
		$url = sprintf(HTTPSQS_PUT_FMT, 'put', "$httpsqs_data");
		print("$url\n");
		$ret = file_get_contents("$url", false, $context);

		return $ret == 'HTTPSQS_PUT_OK';
	}
	else 
	{
		return $dbobj->query($query);
	}
*/	
}

?>

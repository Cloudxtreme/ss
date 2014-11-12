<?php
//中文UTF-8
require_once('db.php');

global $global_cdn_domain;
global $global_cdn_web_ip;
global $global_cdn_web_db;

$table_list = array();
$node_list = array();
$cname_list = array();
$error_node_list = array();
$nettype_iplist = array();
$backup_iplist = array();

$dns_list = array();
$table_ip_list = array();

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

//get table list
//////////////////////////////////////////////////////////////////////////////////////////////////////
$query = "select * from zone_table;";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( ! mysql_num_rows($result) ) {
	exit;
}

while( ($row = mysql_fetch_array($result)) ) 
{
	$tablename = $row['tablename'];
	$table_list[] = $tablename;

	$nettype = get_nettype($tablename);
	$nettype_iplist[$nettype]['iplist'] = array();
}
mysql_free_result($result);

//get node list
//////////////////////////////////////////////////////////////////////////////////////////////////////
$query = "select distinct rdata from ip_list;";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( mysql_num_rows($result) ) 
{
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$ip = $row[0];
		$node_list[$ip] = $ip;
	}
	mysql_free_result($result);
}

//check node port
//////////////////////////////////////////////////////////////////////////////////////////////////////
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
/*
print_r($table_list);
print_r($node_list);
print_r($error_node_list);
exit;
*/
//get dns list
//////////////////////////////////////////////////////////////////////////////////////////////////////
$query = "select * from dns_list where `status` = 'true';";
if( ! ($result = $dbobj->query($query)) )
{
    print($dbobj->error());
    exit;
}

if( mysql_num_rows($result) )
{
    while( ($row = mysql_fetch_array($result)) )
    {
			$id = $row['id'];
			$name = $row['name'];
			$cname_list[$name] = $name;
			$dns_list[$name]['id'] = $id;
			$dns_list[$name]['error_node_list'] = array();
			$dns_list[$name]['ip_list'] = array();
    }
    mysql_free_result($result);
}
/*
print_r($dns_list);
exit;
*/
$temp_list = $dns_list;

foreach( $temp_list as $name => $info )
{
	$id = $info['id'];
	$query = "select * from ip_list where `dnsid` = '$id';";

	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		continue;
	}

	if( ! mysql_num_rows($result) ) {
		continue;
	}
	
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$id = $row['id'];
		$ip = $row['rdata'];
		$ttl = $row['ttl'];
		$table = $row['tablename'];

		$nettype = get_nettype($table);
		$nettype_iplist[$nettype]['iplist'][$ip] = $ip;

		if( array_key_exists($ip, $error_node_list) ) 
		{
			if( ! isset($dns_list[$name]['ip_list'][$table]) ) {
				$dns_list[$name]['ip_list'][$table] = array();
			}
			continue;
		}

		$dns_list[$name]['ip_list'][$table][$id]['ip'] = $ip;
		$dns_list[$name]['ip_list'][$table][$id]['ttl'] = $ttl;
		
		$dns_info['name'] = $name;
		$dns_info['ttl'] = $row['ttl'];
		$dns_info['rdtype'] = $row['rdtype'];
		$dns_info['rdata'] = $row['rdata'];
		
		$table_ip_list[$table][] = $dns_info;
	}
	mysql_free_result($result);		
}

//print_r($cname_list);exit;

$temp_node_list = $node_list;
foreach( $temp_node_list as $ip )
{
	if( array_key_exists($ip, $error_node_list) ) {
		continue;
	}
	
	foreach( $cname_list as $cname )
	{
		$httpcode = 0;
		$ret = check_node($ip, '80', $cname, $httpcode);
		//print("$cname $ip $httpcode $ret \n");
		if( ! $ret ) 
		{
			print("$cname $ip $httpcode $ret \n");
			delete_node_name($ip, $cname);
			$dns_list[$cname]['error_node_list'][$ip] = $ip;
		}
	}
}

//print_r($dns_list);
//print_r($table_ip_list);
//exit;

//check node
//////////////////////////////////////////////////////////////////////////////////////////////////////
foreach( $dns_list as $name => $info )
{
	foreach( $info['ip_list'] as $table => $ip_list )
	{
		foreach( $ip_list as $ipinfo )
		{
			$ip = $ipinfo['ip'];
			$ttl = $ipinfo['ttl'];
			
			$ret = ! array_key_exists($ip, $info['error_node_list']);
			//print("$name $table $ip $ttl $ret \n");
			update_node($table, $name, $ip, $ttl , $ret);
		}
		//check backup node
		check_node_backup($table, $name);
	}
}

//clear table item
//////////////////////////////////////////////////////////////////////////////////////////////////////
foreach( $table_list as $table )
{
	$query = "select * from $table where `rdtype` = 'A' and name not like 'ns.%' and name != '$global_cdn_domain';";
	
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		exit;
	}

	if( ! mysql_num_rows($result) ) {
		continue;
	}

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
		if( check_delete($table, $info) ) 
		{
			$id = $info['id'];
			$query = "delete from $table where `id` = '$id';";
			$ret = $dbobj->query($query);
			print(date('Y-m-d H:i:s'));
			print(" $query $ret \n");
			print_r($info);			
		}
	}
	
}

//my function
//////////////////////////////////////////////////////////////////////////////////////////////////////

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
		fwrite($fp, $out);
		while( ! feof($fp) ) 
		{
			$ret .= fgets($fp, 1024);
			if( $http_ret == '' ) { $http_ret = $ret; }
		}
		fclose($fp);
		//print_r($out.$http_ret);
		$http_ret = explode(' ', $http_ret);
		if( count($http_ret) < 2 ) {
			continue;
		}
		$httpcode = $http_ret[1];
		if( $httpcode >= '200' && $httpcode < '500' ) { return true; }
		if( strstr($ret, 'X-Cache') || strstr($ret, 'squid') ) { return true; }
	}
	return false;
}

function delete_node($ip)
{
	global $dbobj;
	global $table_list;

	foreach( $table_list as $table )
	{
		$query = "delete from $table where rdata = '$ip';";
		$ret = $dbobj->query($query);
		//print(date('Y-m-d H:i:s'));
		//print(" $query $ret \n");
	}
}

function delete_node_name($ip, $name)
{
	global $dbobj;
	global $table_list;

	foreach( $table_list as $table )
	{
		$query = "delete from $table where `name` = '$name' and rdata = '$ip';";
		$ret = $dbobj->query($query);
		//print(date('Y-m-d H:i:s'));
		//print(" $query $ret \n");
	}
}

function update_node($table, $name, $ip, $ttl, $isok)
{
	global $dbobj;
	global $node_list;
	
	$query = "select * from $table where `name` = '$name' and `ttl` = '$ttl' and `rdtype` = 'A' and `rdata` = '$ip';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		exit;
	}
	
	$cnt = mysql_num_rows($result);
	//print_r($cnt);
	if( $cnt ) {
		mysql_free_result($result);
	}
	
	if( $isok && $cnt ) {
		return;
	}
	
	if( ! $isok && $cnt )
	{
		//delete
		$query = "delete from $table where `name` = '$name' and `ttl` = '$ttl' and `rdtype` = 'A' and `rdata` = '$ip';";
		$ret = $dbobj->query($query);
		print(date('Y-m-d H:i:s'));
		print(" $query $ret \n");
	}	
	
	if( $isok && ! $cnt )
	{
			//$httpcode = 0;
 	    //$ret = check_node($ip, '80', $name, $httpcode);
			//add
			$query = "insert into $table(`name`, `ttl`, `rdtype`, `rdata`) values('$name', '$ttl', 'A', '$ip');";
			$ret = $dbobj->query($query);
			print(date('Y-m-d H:i:s'));
			print(" $query $ret \n");
	}
}

function check_node_backup($table, $name)
{
	global $dbobj;
	global $nettype_iplist;
	global $node_list;
	global $backup_iplist;
	global $dns_list;
	global $error_node_list;

	if( ! array_key_exists($name, $dns_list) ||
		! array_key_exists($table, $dns_list[$name]['ip_list']) ) {
		return;
	}

	if( ! isset($dns_list[$name]['ip_list'][$table]) ) {
		return;
	} 

    $query = "select * from $table where `name` = '$name';";
    if( ! ($result = $dbobj->query($query)) )
    {
        print($dbobj->error());
        exit;
    }

    $cnt = mysql_num_rows($result);
    //print_r($cnt);
    if( $cnt ) 
	{
		if( $cnt == 1 )
		{
			$row = mysql_fetch_array($result);
			$ip = $row['rdata'];
			$backup_iplist[$table][$name][$ip] = $ip;
		}
        mysql_free_result($result);
		return;
    }

	$nettype = get_nettype($table);
	$temp_iplist = $nettype_iplist[$nettype]['iplist'];
	shuffle($temp_iplist);
	foreach( $temp_iplist as $ip )
	{
		if( array_key_exists($ip, $error_node_list) ) {
			continue;
		}
		$query = "insert into $table(`name`, `ttl`, `rdtype`, `rdata`) values('$name', '300', 'A', '$ip');";
		$ret = $dbobj->query($query);
		print(date('Y-m-d H:i:s'));
		print("backupnode $query $ret \n");			
		$backup_iplist[$table][$name][$ip] = $ip;
		//break;
	}
}

function check_delete($table, $info)
{
	global $table_ip_list;
	global $backup_iplist;

	if( isset($table_ip_list[$table]) )
	{	
		foreach( $table_ip_list[$table] as $dns_info )
		{
			if( $info['name'] == $dns_info['name'] &&
				$info['ttl'] == $dns_info['ttl'] &&
				$info['rdtype'] == $dns_info['rdtype'] &&
				$info['rdata'] == $dns_info['rdata'] ) {
					return false;
				}
		}
	}

	if( array_key_exists($table, $backup_iplist) &&
		array_key_exists($info['name'], $backup_iplist[$table]) &&
		array_key_exists($info['rdata'], $backup_iplist[$table][$info['name']]) ) {
		return false;
	}

	return true;
}

function get_nettype($name)
{
    $nettype = explode('_', $name);
	return $nettype[0];
}

?>

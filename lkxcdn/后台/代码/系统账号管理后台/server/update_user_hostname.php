<?php

/* 
	万恶的mysql主从，记得不要用数据库前缀 !!! 
*/
require_once('usercheck.php');

$node_dns_master = array();
//print_r($_POST);
/*
$userid = check_user();
if( ! $userid ) 
{
	print("check user fail!\n");
	exit;
}*/

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

//print_r($node_dns_master);

$userlist = array();

$query = "select * from `cdn_web`.`user` where `status` = 'true';";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error()."\n");
	exit;
}
if( ! mysql_num_rows($result) )
{
	exit;
}
while( ($row = mysql_fetch_array($result)) ) 
{
	$username = $row['user'];
	$userlist[$username] = array();	
}
mysql_free_result($result);	

//old setting 
$query = "select * from `cdn_web`.`user_hostname` where `status` = 'true';";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error()."\n");
	exit;
}
if( ! mysql_num_rows($result) )
{
	exit;
}

$tablelist = array();
$user_tablelist = array();
$table_domainname = array();

$hostname_tablelist = array();
$nowhostname_list = array();

$nodedns_hostnamelist = array();

while( ($row = mysql_fetch_array($result)) ) 
{
	$username = $row['owner'];
	$hostname = $row['hostname'];
	$domainname = $row['domainname'];
	$tablename = $row['tablename'];
	
	$user_tablelist[$tablename] = $username;
	$tablelist[$tablename] = $domainname;
	$hostname_tablelist[$hostname] = $tablename;
	$nowhostname_list[$hostname] = $username;
}
mysql_free_result($result);	

//print_r($userlist);
//print_r($tablelist);
//exit;

$dbinfo = $node_dns_master['squid_dns_ct'];

$idbobj = new DBObj;
if( ! $idbobj->conn2($dbinfo['ip'], $dbinfo['user'], $dbinfo['pass']) ) 
{
	print($idbobj->error()."\n");
	return false;
}
$idbobj->query("set names utf8;");
$idbobj->select_db('squid_dns_ct');// import !!!

foreach( $tablelist as $tablename => $domainname )
{
	$query = "select * from `$tablename` where `rdtype` = 'A' and `name` not like 'ns.%';";
	if( ! ($result = $idbobj->query($query)) ) 
	{
		print($idbobj->error()."\n");
		exit;
	}
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$hostname = $row['name'];
		$hostname_tablelist[$hostname] = $tablename;
		$table_domainname[$hostname] = $domainname;
				
		$nodedns_hostnamelist[$hostname] = $hostname;
	}
	mysql_free_result($result);	
}	

//print_r($nodedns_hostnamelist);
//print_r($nowhostname_list);
$add_hostname_list = array_diff_key($nodedns_hostnamelist, $nowhostname_list);
$del_hostname_list = array_diff_key($nowhostname_list, $nodedns_hostnamelist);
//print_r($add_hostname_list);
//print_r($del_hostname_list);
//exit;

foreach( $del_hostname_list as $hostname => $owner )
{
	$query = "delete from cdn_web.user_hostname where `hostname` = '$hostname';";
	//$ret = $dbobj->query($query);	
	print("$owner => $query \n");
}

foreach( $add_hostname_list as $hostname )
{
	$tablename = $hostname_tablelist[$hostname];
	$username = $user_tablelist[$tablename];
	$domainname = $table_domainname[$hostname];
	
	$query = "insert into cdn_web.user_hostname(`hostname`, `domainname`, `tablename`, `owner`, `status`)
					values('$hostname', '$domainname', '$tablename', '$username', 'true');";
	$ret = $dbobj->query($query);	
	print("$query $ret\n");
}

function init()
{
	global $dbobj, $node_dns_master;

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
	
	return true;
}


?>

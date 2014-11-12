<?php
//中文UTF-8
require_once('db.php');

global $global_databaseip, $global_databaseuser, $global_databasepwd;
global $global_cdn_domain;

$dbobj = new DBObj;
if( ! $dbobj->conn2('cdninfo.efly.cc', 'root', 'rjkj@rjkj') ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db('cdn_rightgo_net_ex');
$query = "select * from zone_table;";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error());
	exit;
}

$tables = array();
while( ($row = mysql_fetch_array($result)) ) {
	$table = $row['tablename'];
	$tables[$table] = $table;
}
mysql_free_result($result);
//print_r($tables); exit;

$servers = array();
foreach( $tables as $table ) {
	$query = "select * from $table where rdtype = 'A' and `name` not like 'ns.%'";
	$result = $dbobj->query($query);
	while( ($row = mysql_fetch_array($result)) ) {
		$ip = $row['rdata'];
		$servers[$table][$ip] = $ip;
	}
	mysql_free_result($result);
}
//print_r($servers); exit;

//localhost
////////////////////////////////////////////////////////////////
$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db('cdn_rightgo_net_ex');

$myservers = array();
$ips = array();

foreach( $tables as $table ) {
	$query = "select * from $table where rdtype = 'A' and `name` not like 'ns.%'";
	$result = $dbobj->query($query);
	$tableips =  array();
	while( ($row = mysql_fetch_array($result)) ) {
		$ip = $row['rdata'];
		$myservers[$table][$ip] = $row['status'];
		$ips[$ip] = $ip;
		$tableips[$ip] = $ip;
	}
	mysql_free_result($result);
	
	
	foreach( $tableips as $ip) {
		if( ! isset($servers[$table][$ip]) ) {
			$query = "delete from `$table` where `rdata` = '$ip';";
			print(@date('Y-m-d H:i:s '));
			print("$query\n");
			$dbobj->query($query);
		}
	}
	
	foreach( $servers[$table] as $ip ) {
		if( ! isset($tableips[$ip]) ) {
			$ips[$ip] = $ip;
			$query = "insert into `$table`(`name`, `ttl`, `rdtype`, `rdata`, `status`) values('file.cdn.rightgo.net', '300', 'A', '$ip', 'true');";
			print(@date('Y-m-d H:i:s '));
			print("$query\n");
			$dbobj->query($query);
		}
	}
}

//print_r($myservers);
$ipstatus = $ips;
foreach( $ips as $ip ) {
	$status = check_dw_valid($ip, 80) ? 'true' : 'false';
	$ipstatus[$ip] = $status;
}
//print_r($ipstatus);

foreach( $myservers as $table => $info ) {
	foreach( $info as $ip => $status ) {
		if( $ipstatus[$ip] != $status ) {
			$setstatus = $ipstatus[$ip];
			$query = "update `$table` set `status` = '$setstatus' where `rdata` = '$ip';";
			print(@date('Y-m-d H:i:s '));
			print("$query\n");
			$dbobj->query($query);
		}
	}
}


//function 
////////////////////////////////////////////////////////////////////////////////////////
function check_dw_valid($ip, $port)
{
	$fp = fsockopen($ip, $port, $errno, $errstr, 5);
	if( ! $fp ) {
		return false;
	}
	$out = "GET / HTTP/1.1\r\n";
	$out .= "Host: $ip\r\n";
	$out .= "Connection: Close\r\n\r\n";
	$ret = fwrite($fp, $out);
	if( ! $ret ) { 
		return false; 
	}
	
	while( ! feof($fp) ) 
	{
		$http_ret = fgets($fp, 1024);
		if( ! $http_ret ) { 
			fclose($fp); 
			return false; 
		}
		break;
	}
	fclose($fp);
	//print_r($out.$http_ret);
	$http_ret = explode(' ', $http_ret);
	if( count($http_ret) < 2 ) {
		return false;
	}
	//print_r($http_ret);
	$httpcode = $http_ret[1];
	if( $httpcode == '403' ) {
		return true;
	}
	return true;
}

?>

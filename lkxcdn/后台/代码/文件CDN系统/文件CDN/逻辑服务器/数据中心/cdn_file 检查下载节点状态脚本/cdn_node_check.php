<?php
//中文UTF-8
require_once('cdn_db.php');

global $global_databasename;

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

$query = "select * from user_nginx;";
//print($query);
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

$nodelist = array();
while( ($row = mysql_fetch_array($result)) ) 
{
	$ip = $row['ip'];
	$port = $row['port'];
	$status = $row['status'];
	
	$nodelist[$ip][$port] = $status;
}
mysql_free_result($result);

//print_r($nodelist);exit;

foreach( $nodelist as $ip => $portlist )
{
	foreach( $portlist as $port => $status )
	{
		//$ret = check_node_port($ip, $port);
		$ret = check_dw_valid($ip, $port);
		$ret = ($ret == true) ? 'true' : 'false'; 
		
		//ob_flush(); flush();
		//print("$ip:$port $status $ret\n");
		if( $ret == $status ) {
			continue;
		}
		$query = "update user_nginx set `status` = '$ret' where ip = '$ip' and port = '$port'";
		$ret = $dbobj->query($query);
		print(date('Y-m-d H:i:s'));
		print(" $query $ret \n");
	}
}

function check_node_port($ip, $port)
{
	$i = 0;
	while( $i++ < 3 )
	{
		$fp = @fsockopen($ip, $port, $errno, $errstr, 5);
		if( ! $fp ) {
			continue;
		}
		fclose($fp);
		return 'true';
	}
	return 'false';
}

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
	
	/*
	$httpcode = $http_ret[1];
	//print_r($httpcode);
	if( $httpcode == '200' ) 
	{
		//print("<a href=$download_url>$download_url</a><br>");
		header("Location: $download_url");
		exit; // exit now !!!!!!
	}	
	*/
}
										
?>

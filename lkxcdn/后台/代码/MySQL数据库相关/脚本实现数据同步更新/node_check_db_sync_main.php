<?php
//中文UTF-8
require_once('db.php');

global $global_databaseip, $global_databaseuser, $global_databasepwd;
global $global_cdn_domain;

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");

//get server_list
////////////////////////////////////////////////////////////////
$dbobj->select_db('cdn_file');
$query = "select * from server_list where `type` = 'node'";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error());
	exit;
}

$servers = array();
while( ($row = mysql_fetch_array($result)) ) {
	$ip = $row['ip'];
	$nettype = $row['nettype'];
	$match = $row['match2'];
	$zone = $row['zone'];
	$status = check_dw_valid($ip, 7654);
	$status = $status ? 'true' : 'false';
	$servers[$ip] = array('ip' => $ip, 'nettype' => $nettype, 'match' => $match, 'zone' => $zone, 'status' => $status);
}
mysql_free_result($result);

asort($servers);
//print_r($servers);

//get zone_table
////////////////////////////////////////////////////////////////
$dbobj->select_db('cdn_rightgo_net_ex');

$query = "select * from zone_table";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error()); 
	exit;
}

$tables = array();
while( ($row = mysql_fetch_array($result)) ) {
	$id = $row['id'];
	$nettype = $row['nettype'];
	$tablename = $row['tablename'];
	$zone = $row['zone'];
	$local = $row['local'];
	
	if( strstr($tablename, 'mobile_') ) { continue; }
	
	$tables[$tablename] = array('tablename' => $tablename, 'nettype' => $nettype, 'zone' => $zone, 'local' => $local);
}
mysql_free_result($result);

asort($tables);
//print_r($tables);

/*
    [125.78.241.101] => Array
        (
            [ip] => 125.78.241.101
            [nettype] => 电信
            [match] => 福建 江西
            [zone] => 华东
            [status] => 1
        )
        
    [ct_hn_guangdong] => Array
        (
            [tablename] => ct_hn_guangdong
            [nettype] => 电信
            [zone] => 华南
            [local] => 广东
        )
                
*/
foreach( $tables as $table => $tableinfo ) {
	
	//$query = "DELETE FROM `$table` where `rdtype` = 'A' and name != 'ns.cdn.rightgo.net';";
	//$dbobj->query($query);
	
	$query = "SELECT * FROM `$table` where `rdtype` = 'A' and name != 'ns.cdn.rightgo.net';";
	if( ! ($result = $dbobj->query($query)) ) {
		print($dbobj->error());
		exit;
	}
	
	$table_ips = array();
	while( ($row = mysql_fetch_array($result)) ) {
		$id = $row['id'];
		$ip = $row['rdata'];
		$status = $row['status'];
		$table_ips[$ip] = array('id' => $id, 'ip' => $ip, 'status' => $status);
	}
	//print("$table => ");
	//print_r($table_ips);
	
	if( mysql_num_rows($result) ) { 
		mysql_free_result($result); 
	}
	
	foreach( $servers as $ip => $ipinfo ) {
		
		if( $ipinfo['nettype'] != $tableinfo['nettype'] ) { continue; }
		if( $ipinfo['zone'] != $tableinfo['zone'] &&
				! strstr($ipinfo['match'], $tableinfo['local']) ) { continue; }
		
		if( array_key_exists($ip, $table_ips) ) {
			if( $table_ips[$ip]['status'] != $servers[$ip]['status'] ) {
				//update status
				$id = $table_ips[$ip]['id'];
				$status = $servers[$ip]['status'];
				$query = "update `$table` set `status` = '$status' where `id` = '$id';";
				print(date('Y-m-d H:i:s '));
				print("$query\n");
				$dbobj->query($query);				
			}			
		} 
		else {
			$query = "insert into `$table`(`name`, `ttl`, `rdtype`, `rdata`, `status`) values('file.cdn.rightgo.net', '300', 'A', '$ip', 'true');";
			print(date('Y-m-d H:i:s '));
			print("$query\n");
			$dbobj->query($query);
		}
		
		foreach( $table_ips as $ip => $ipinfo ) {
			if( ! array_key_exists($ip, $servers) ) {
				$id = $ipinfo['id'];
				$query = "delete from `$table` where `id` = '$id';";
				print(date('Y-m-d H:i:s '));
				print("$query\n");
				$dbobj->query($query);				
			}
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

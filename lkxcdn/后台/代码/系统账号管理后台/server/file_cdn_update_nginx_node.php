<?php
require_once('db.php');

/* 
	万恶的mysql主从，记得不要用数据库前缀 !!! 
*/

$dbobj = new DBObj;
if( ! $dbobj->conn() ) { print($dbobj->error()."\n"); exit; }

$dbobj->query("set names utf8;");
$dbobj->select_db('cdn_file');//import!!!

$file_node_list = array();
$user_list = array();
$user_node_list = array();

$query = "select * from server_list where `type` = 'node';";
if( ! ($result = $dbobj->query($query)) ) { print($dbobj->error()."\n"); return; }
while( ($row = mysql_fetch_array($result)) ) {
	$ip = $row['ip'];
	$file_node_list[$ip] = $row['ip'];
}
mysql_free_result($result);	
//print_r($file_node_list);

$query = "select * from `user` where `status` = 'true';";
if( ! ($result = $dbobj->query($query)) ) { print($dbobj->error()."\n"); return; }
while( ($row = mysql_fetch_array($result)) ) {
	$user = $row['user'];
	$port = $row['nginxport'];
	$user_list[$user] = $port;
}
mysql_free_result($result);	
//print_r($user_list);

$query = "select * from `user_nginx`;";
if( ! ($result = $dbobj->query($query)) ) { print($dbobj->error()."\n"); return; }
while( ($row = mysql_fetch_array($result)) ) {
	$user = $row['user'];
	$ip = $row['ip'];
	$port = $row['port'];
	$user_node_list[$user][$ip] = $port;
}
mysql_free_result($result);	
//print_r($user_node_list);

foreach( $user_node_list as $user => $nodeinfo )
{
	if( ! array_key_exists($user, $user_list) )
	{
		print("delete from `user_nginx` where `user` = '$user'; \n");	
		continue;
	}
	
	$userport = $user_list[$user];
	
	foreach( $nodeinfo as $ip => $port ) 
	{
		if( $port != $userport ) {
			print("delete from `user_nginx` where `user` = '$user' and `port` = '$port'; \n");	
		}
		if( ! array_key_exists($ip, $file_node_list) ) {
			print("delete from `user_nginx` where `user` = '$user' and `ip` = '$ip'; \n");	
		}
	}
	
	foreach( $file_node_list as $ip )
	{
		if( ! array_key_exists($ip, $nodeinfo) ) {
			print("INSERT INTO `user_nginx` (`user`, `ip`, `port`, `status`) VALUES('$user', '$ip', '$userport', 'false'); \n");
		}
	}
}


/*	
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
*/

?>

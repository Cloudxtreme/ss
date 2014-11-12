<?php
require_once('cdn_db.php');

if( ! isset($_GET['url']) ) { exit; }

$url = $_GET['url'];
if( $url[strlen($url) - 1] != '/' ) { $url .= '/'; }
//print("$url");

$temp = str_replace('http://', '', $url);
$temp = explode('/', $temp);
//print_r($temp);

if( count($temp) < 2 ) { exit; }
$hostname = $temp[0];
//print("$hostname");
$tablename = $hostname;

$hostnames = array();
$handle = @fopen('/opt/squid_tools/hostname_list.txt', "r");
if( $handle ) {
	while( ! feof($handle) ) {
		$name = fgets($handle, 100);
		$name = trim($name);
		if( $name == '' ) { continue; }
		$hostnames[$name] = $name;
	}
	fclose($handle);
}
//print_r($hostnames);

foreach( $hostnames as $name ) {
	if( strstr($hostname, $name) ) {
		$tablename = $name; break;
	}
}
//print($tablename);

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	print($dbobj->error()); exit;
}

$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

$query = "delete from `$tablename` where `url` like '$url%'";
//print($query);
$dbobj->query($query);


?>


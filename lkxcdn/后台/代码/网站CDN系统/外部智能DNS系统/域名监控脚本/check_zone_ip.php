<?php
//中文UTF-8
require_once('db.php');

global $global_cdn_domain;

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

$query = "select * from dns_list;";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( ! mysql_num_rows($result) ) {
	continue;
}

$dns_list = array();

while( ($row = mysql_fetch_array($result)) ) 
{
	$id = $row['id'];
	$name = $row['name'];

	$dns_list[$id] = $name;
}	
mysql_free_result($result);

$query = "select * from ip_list;";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( ! mysql_num_rows($result) ) {
	continue;
}

$ip_list = array();

while( ($row = mysql_fetch_array($result)) ) 
{
	$tablename = $row['tablename'];
	$dnsid = $row['dnsid'];
	$ip = $row['rdata'];

	$ip_list[$tablename][$dnsid][] = $ip;
}	
mysql_free_result($result);

print(count($ip_list)."\n");

$index = 0;
foreach( $ip_list as $tablename => $info ) 
{
	print("$index $tablename ".count($info)."\n");
	$index++;
	print("-----------------------------------------------------------\n");
	foreach( $info as $dnsid => $ips )
	{
		printf("[%30s]  [ %50s ] %02d ", $tablename, $dns_list[$dnsid], count($ips));
		asort($ips);
		foreach( $ips as $ip ) {
			print(" $ip ");
		}
		print("\n");
	}
	print("\n");	
}

//print_r($ip_list);

?>

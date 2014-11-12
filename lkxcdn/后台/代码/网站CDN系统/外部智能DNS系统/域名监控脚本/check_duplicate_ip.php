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

$query = "select * from ip_list;";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( ! mysql_num_rows($result) ) { exit; }

$ip_list = array();

while( ($row = mysql_fetch_array($result)) ) 
{
	$id = $row['id'];
	$dnsid = $row['dnsid'];
	$tablename = $row['tablename'];
	$ip = $row['rdata'];

	if( isset($ip_list[$tablename][$dnsid][$ip]) ) {
		$nowid = $ip_list[$tablename][$dnsid][$ip];
		//printf("%s %s %s %s %s \n", $nowid, $id, $dnsid, $tablename, $ip);
		printf("delete from ip_list where id = %s;\n", $id);
		continue;
	}
	$ip_list[$tablename][$dnsid][$ip] = $id;
}	
mysql_free_result($result);


//print_r($ip_list);

?>

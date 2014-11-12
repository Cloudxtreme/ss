<?php
//中文UTF-8
/*
检查ip_list是否存在重复的记录 dnsid rdata tablename
*/

require_once('db.php');

$dns_list_idkey = $dns_list_namekey = array();
$dup_name_rdata = array();

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

//get dns list
$query = "select * from dns_list where `status` != 'false';";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error());
	exit;
}
if( ! mysql_num_rows($result) ) { exit; }

while( ($row = mysql_fetch_array($result)) ) {
	$id = $row['id'];
	$name = $row['name'];
	$dns_list_idkey[$id] = $name;
	$dns_list_namekey[$name] = $id;
}
mysql_free_result($result);

//get ip list
$query = "select * from ip_list where `tablename` like 'mobile_%' and `status` = 'true';";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error());
	exit;
}
if( ! mysql_num_rows($result) ) { exit; }

while( ($row = mysql_fetch_array($result)) ) {
	$id = $row['id'];
	$dnsid = $row['dnsid'];
	$ip = $row['rdata'];
	$tablename = $row['tablename'];
	
	$name = $dns_list_idkey[$dnsid];
	
	$dup_name_rdata[$tablename][$dnsid][$ip][] = $id;
	
	if( count($dup_name_rdata[$tablename][$dnsid][$ip]) > 1 ) {
		print("delete $tablename $dnsid $name $ip $id \n");
		//print("delete from ip_list where `id` = '$id'; \n");
	}
}
mysql_free_result($result);


?>

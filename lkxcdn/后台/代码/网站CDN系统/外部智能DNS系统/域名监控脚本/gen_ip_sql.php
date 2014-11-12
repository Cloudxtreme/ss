<?php
//中文UTF-8
require_once('db.php');

global $global_cdn_domain;

$sampleip = $argv[1];
$genip = $argv[2];

//print("sampleip = $sampleip  genip = $genip \n");

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

$sampleip_list = array();

$query = "select * from ip_list where rdata = '$sampleip';";
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
	$dnsid = $row['dnsid'];
	$tablename = $row['tablename'];
	$sampleip_list[] = array( 'dnsid' => $dnsid, 'tablename' => $tablename );
}	
mysql_free_result($result);

foreach( $sampleip_list as $info )
{
	$dnsid = $info['dnsid'];
	$tablename = $info['tablename'];
	$query = "insert into ip_list(`dnsid`, `ttl`, `rdtype`, `rdata`, `tablename`, `status`, `lasttime`) values('$dnsid', '300', 'A', '$genip', '$tablename', 'true', now());";
	print("$query\n");
}


?>

<?php
//中文UTF-8
require_once('db.php');

$table_list = array();
$proc_list = array();

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

$query = "select * from zone_table where `nettype` = '电信' or `nettype` = '网通' ;";
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
	$tablename = $row['tablename'];
	$table_list[] = $tablename;
}
mysql_free_result($result);

//print_r($table_list);

//print(date('Y-m-d H:i:s '));
//print("run... \n");

$i = 0;	
foreach( $table_list as $tablename )
{
	$cmd = "/usr/bin/php /opt/dns_check_ex/table_dns_node_check.php $tablename";
	//print(date('Y-m-d H:i:s '));
	//print("$cmd\n");
	$handle = popen($cmd, 'r');	
	/*
	$contents = '';
	while( ! feof($handle) ) {
	  $contents .= fread($handle, 8192);
	}
	print_r($contents);
	pclose($handle);	
	*/
	$proc_list[$i++] = $handle;
	sleep(1);
}

//print_r($proc_list); //exit;

foreach( $proc_list as $i => $handle )
{
	$contents = '';
	while( ! feof($handle) ) {
	  $contents .= fread($handle, 8192);
	}
	print_r($contents);
	pclose($handle);	
}

?>

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

$table_list = array();
$query = "select * from zone_table;";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( ! mysql_num_rows($result) ) {
	continue;
}

$table_list = array();

while( ($row = mysql_fetch_array($result)) ) 
{
	$tablename = $row['tablename'];

	$table_list[$tablename] = $tablename;
}	
mysql_free_result($result);

//print_r($table_list);

foreach( $table_list as $table )
{
	//$query = "delete from $table where `name` = 'cdn.love-ems.cn.cache.rightgo.net';";
	$query = "select * from $table where `rdata` = '116.28.64.170';";
	print("$query\n");
}

?>

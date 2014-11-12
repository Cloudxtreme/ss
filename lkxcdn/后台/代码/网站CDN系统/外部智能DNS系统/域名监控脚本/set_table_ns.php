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

$query = "show tables;";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( ! mysql_num_rows($result) ) {
	continue;
}

$tables = array();

while( ($row = mysql_fetch_array($result)) ) 
{
	$table = $row[0];
	if( strstr($table, 'mobile_') ) {
		$tables[$table] = $table;
	}
}	
mysql_free_result($result);

foreach( $tables as $table ) {
	$query = "update `$table` set `rdata` = '183.238.95.103' where `rdata` = '183.234.49.173';";
	print("$query\n");
	$query = "update `$table` set `rdata` = '183.238.95.104' where `rdata` = '183.234.49.174';";
	print("$query\n");
}

?>

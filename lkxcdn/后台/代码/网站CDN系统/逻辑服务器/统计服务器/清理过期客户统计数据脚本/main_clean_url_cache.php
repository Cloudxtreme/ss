<?php
require_once('cdn_db.php');

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db('cdn_web_cache_url');

$query = "SHOW TABLES;";
//print($query);

if( ! ($result = $dbobj->query($query)) ) {
	return;
}

$table_list = array();
while( ($row = mysql_fetch_array($result)) ) 
{
	$table = $row['0'];
	$table_list[$table] = $table;
}
mysql_free_result($result);
print_r($table_list);

foreach( $table_list as $table )
{
	$query = "delete from `$table` where `timestamp` < '2013-01-01 00:00:00';";
	$result = $dbobj->query($query);
	print("$query\n");
}

?>


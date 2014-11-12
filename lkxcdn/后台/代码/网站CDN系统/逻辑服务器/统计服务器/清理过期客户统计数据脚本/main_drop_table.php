<?php
require_once('cdn_db.php');

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db('cdn_web_cache_url');

$query = "SHOW TABLE STATUS;";
//print($query);

if( ! ($result = $dbobj->query($query)) ) {
	return;
}

$table_list = array();

print("drop table");		
while( ($row = mysql_fetch_array($result)) ) 
{
	$table = $row['Name'];
	$update_time = $row['Update_time'];
	$table_list[$table]['update_time'] = $update_time;
	
	if( $update_time < "2013-01-15 00:00:00" ) {
		//print("$table $update_time \n");		
		print(" `$table`,");		
	}
	
}
mysql_free_result($result);

//print_r($table_list);

?>


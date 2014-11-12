<?php
require_once('cdn_db.php');

global $global_databaseip;

$dbobj = new DBObj;
if( ! $dbobj->conn() ) { exit; }
$dbobj->query("set names utf8;");
$dbobj->select_db("cache_rightgo_net_ex");

$query = "SHOW TABLES;";
//print("$query\n");

if( ! ($result = $dbobj->query($query)) ) { exit; }

$tables = array();

while( ($row = mysql_fetch_array($result)) ) {
	$table = $row[0];
	if( is_sync_table($table) ) {
		$tables[$table] = $table;
	}
}
mysql_free_result($result);
print_r($tables);

foreach( $tables as $table ) {

	//print("$table $now_update_time $old_update_time \n");
	print("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip cache_rightgo_net_ex $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj cache_rightgo_net_ex < /tmp/$table.sql\n");
	
	system("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip cache_rightgo_net_ex $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj cache_rightgo_net_ex < /tmp/$table.sql\n");
	

}

function is_sync_table($table) 
{
	$base_tables = array('dns_list' => 'dns_list', 'ip_list' => 'ip_list', 'zone_table' => 'zone_table');
	if( isset($base_tables[$table]) ) { return false; }
	if( strstr($table, 'mobile_') ) { return false; }
	return true;
}

?>


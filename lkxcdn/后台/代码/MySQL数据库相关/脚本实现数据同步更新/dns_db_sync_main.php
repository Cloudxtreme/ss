<?php
require_once('cdn_db.php');

global $global_databaseip;

$no_match_tables = array('dns_list', 'ip_list', 'zone_table');

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db("cache_rightgo_net_ex");

$query = "SHOW TABLE STATUS;";
//print("$query\n");

if( ! ($result = $dbobj->query($query)) ) {
	exit;
}

$now_table_list = array();

while( ($row = mysql_fetch_array($result)) ) 
{
	$table = $row['Name'];
	$update_time = $row['Update_time'];
	if( array_search($table, $no_match_tables) === FALSE ) { 
		$now_table_list[$table]['update_time'] = $update_time;
	}
}
mysql_free_result($result);
//print_r($now_table_list);

$now_data = serialize($now_table_list);

$handle = fopen("data", "a+b");
if( ! $handle ) { 
	print("fopen data file error! \n");
	exit; 
}

fseek($handle, 0);

$old_data = fread($handle, filesize("data"));
$old_table_list = unserialize($old_data);
//print_r($old_table_list);

foreach( $now_table_list as $table => $info ) {
	if( ! isset($old_table_list[$table]) ) { 
		$old_update_time = '';
	} else {
		$old_update_time = $old_table_list[$table]['update_time'];
	}
	$now_update_time = $info['update_time'];
	if( $now_update_time == $old_update_time ) { continue; }

	//print("$table $now_update_time $old_update_time \n");
	print("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip cache_rightgo_net_ex $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj cache_rightgo_net_ex < /tmp/$table.sql\n");
	
	//system("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip cache_rightgo_net_ex $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj cache_rightgo_net_ex < /tmp/$table.sql\n");
	
}

ftruncate($handle, 0);

if( fwrite($handle, $now_data) === FALSE) {
	print("fwrite data error! \n");
	exit;
}

fclose($handle);

//check if local db have this table
//******************************************************
$mydbobj = new DBObj;
if( ! $mydbobj->conn2('localhost', 'root', 'rjkj@rjkj') ) { exit; }
$mydbobj->query("set names utf8;");
$mydbobj->select_db("cache_rightgo_net_ex");
$query = "SHOW TABLES;";
//print("$query\n");
if( ! ($result = $mydbobj->query($query)) ) { exit; }
$tables = array();

while( ($row = mysql_fetch_array($result)) ) {
	$table = $row[0];
	$tables[$table] = $table;
}
mysql_free_result($result);
//print_r($tables);
foreach( $now_table_list as $table => $info ) {
	if( array_key_exists($table, $tables) ) { continue; }

	print("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip cache_rightgo_net_ex $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj cache_rightgo_net_ex < /tmp/$table.sql\n");
	
	system("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip cache_rightgo_net_ex $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj cache_rightgo_net_ex < /tmp/$table.sql\n");

}
//******************************************************

?>


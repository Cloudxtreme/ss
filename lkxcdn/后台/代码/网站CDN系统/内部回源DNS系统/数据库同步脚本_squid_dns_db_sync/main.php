<?php
require_once('cdn_db.php');

$dbobj = new DBObj;
if( ! $dbobj->conn() ) { exit; }
$dbobj->query("set names utf8;");
$dbobj->select_db("squid_dns_mobile");

$query = "SHOW TABLE STATUS;";
//print("$query\n");

if( ! ($result = $dbobj->query($query)) ) { exit; }

$now_table_list = array();

while( ($row = mysql_fetch_array($result)) ) {
	$table = $row['Name'];
	$update_time = $row['Update_time'];
	$now_table_list[$table] = $update_time;
}
mysql_free_result($result);
//print_r($now_table_list);


$now_data = serialize($now_table_list);

$handle = fopen("data", "a+b");
if( ! $handle ) { print("fopen data file error! \n"); exit; }

fseek($handle, 0);

$old_table_list = array();

$old_data = fread($handle, filesize("data"));
if( $old_data != FALSE ) {
	$old_table_list = unserialize($old_data);
}
//print_r($old_table_list);

foreach( $now_table_list as $table => $now_update_time ) {

	if( ! isset($old_table_list[$table]) ) { 

		print("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip squid_dns_mobile $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj squid_dns_mobile < /tmp/$table.sql\n");

		system("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip squid_dns_mobile $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj squid_dns_mobile < /tmp/$table.sql\n");

	} else {
		$old_update_time = $old_table_list[$table];
	}
	if( $now_update_time == $old_update_time ) { continue; }

	print("$table $now_update_time $old_update_time \n");
	
	print(@date('Y-m-d H:i:s'));

	print("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip squid_dns_mobile $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj squid_dns_mobile < /tmp/$table.sql\n");

	system("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip squid_dns_mobile $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj squid_dns_mobile < /tmp/$table.sql\n");

}

ftruncate($handle, 0);

if( fwrite($handle, $now_data) === FALSE) { print("fwrite data error! \n"); exit; }

fclose($handle);

//check if local db have this table
//******************************************************
$mydbobj = new DBObj;
if( ! $mydbobj->conn2('localhost', 'root', 'rjkj@rjkj') ) { exit; }
$mydbobj->query("set names utf8;");
$mydbobj->select_db("squid_dns_mobile");
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

	print(@date('Y-m-d H:i:s'));

	print("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip squid_dns_mobile $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj squid_dns_mobile < /tmp/$table.sql\n");

	system("/usr/bin/mysqldump -udnsadmin -pdnsadmin -h $global_databaseip squid_dns_mobile $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj squid_dns_mobile < /tmp/$table.sql\n");

}

foreach( $tables as $table ) {
	if( ! array_key_exists($table, $now_table_list) ) {
		print("mysql -uroot -prjkj@rjkj -e \"drop table `squid_dns_mobile`.`$table`;\" \n");
		
		system("mysql -uroot -prjkj@rjkj -e \"drop table squid_dns_mobile.$table;\" \n");
	}
}

//******************************************************


?>


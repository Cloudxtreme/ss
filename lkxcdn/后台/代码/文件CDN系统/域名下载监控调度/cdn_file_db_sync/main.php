<?php
require_once('cdn_db.php');

$match_tables = array('user', 'server_list', 'user_nginx', 'source_file', 'user_hostname');

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db("cdn_file");

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
	foreach( $match_tables as $tablename ) {
		if( $tablename == $table ) {
			$now_table_list[$table]['update_time'] = $update_time;
		}
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
	print("/usr/bin/mysqldump -uroot -prjkj@rjkj -h cdninfo.efly.cc cdn_file $table > /tmp/$table.sql\n");
	print("/usr/bin/mysql -uroot -prjkj@rjkj cdn_file < /tmp/$table.sql\n");
	
	system("/usr/bin/mysqldump -uroot -prjkj@rjkj -h cdninfo.efly.cc cdn_file $table > /tmp/$table.sql && /usr/bin/mysql -uroot -prjkj@rjkj cdn_file < /tmp/$table.sql");
}

ftruncate($handle, 0);

if( fwrite($handle, $now_data) === FALSE) {
	print("fwrite data error! \n");
	exit;
}

fclose($handle);

?>


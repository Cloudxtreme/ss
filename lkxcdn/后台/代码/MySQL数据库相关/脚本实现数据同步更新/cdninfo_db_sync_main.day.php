<?php
require_once('cdn_db.php');

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	exit;
}
$dbobj->query("set names utf8;");

//cdn_file
/******************************************************************************/
$dbobj->select_db("cdn_file");

$query = "SHOW TABLES;";

if( ! ($result = $dbobj->query($query)) ) {
	exit;
}

$tables = array();

while( ($row = mysql_fetch_array($result)) ) {
	$table = $row[0];
	if( strstr($table, 'node_task_') ) { continue; }
	$tables[$table] = $table;
}
mysql_free_result($result);
//print_r($tables);

foreach( $tables as $table ) {
	$timestamp = time();
	print("/usr/bin/mysqldump -uroot -prjkj@rjkj -h 116.28.65.246 cdn_file $table > /tmp/$table.$timestamp.sql && /usr/bin/mysql -uroot -prjkj@rjkj cdn_file_daybk < /tmp/$table.$timestamp.sql && rm -rf /tmp/$table.$timestamp.sql \n");
	system("/usr/bin/mysqldump -uroot -prjkj@rjkj -h 116.28.65.246 cdn_file $table > /tmp/$table.$timestamp.sql && /usr/bin/mysql -uroot -prjkj@rjkj cdn_file_daybk < /tmp/$table.$timestamp.sql && rm -rf /tmp/$table.$timestamp.sql \n");
}

//cdn_web
/******************************************************************************/
$dbobj->select_db("cdn_web");

$query = "SHOW TABLES;";

if( ! ($result = $dbobj->query($query)) ) {
	exit;
}

$tables = array();

while( ($row = mysql_fetch_array($result)) ) {
	$table = $row[0];
	if( strstr($table, 'node_task_') ) { continue; }
	$tables[$table] = $table;
}
mysql_free_result($result);
//print_r($tables);

foreach( $tables as $table ) {
	$timestamp = time();
	print("/usr/bin/mysqldump -uroot -prjkj@rjkj -h 116.28.65.246 cdn_web $table > /tmp/$table.$timestamp.sql && /usr/bin/mysql -uroot -prjkj@rjkj cdn_web_daybk < /tmp/$table.$timestamp.sql && rm -rf /tmp/$table.$timestamp.sql \n");
	system("/usr/bin/mysqldump -uroot -prjkj@rjkj -h 116.28.65.246 cdn_web $table > /tmp/$table.$timestamp.sql && /usr/bin/mysql -uroot -prjkj@rjkj cdn_web_daybk < /tmp/$table.$timestamp.sql && rm -rf /tmp/$table.$timestamp.sql \n");
}

?>


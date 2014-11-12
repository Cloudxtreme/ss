<?php
require_once('db.php');

//print_r($_SERVER);

$serverip = $_SERVER['REMOTE_ADDR'];

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error()."\n");
	exit;
}

$dbobj->query("set names utf8;");
$dbobj->select_db('cdn_file');

$query = "select * from `user` where `status` = 'true';";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error()."\n");
	return;
}

if( ! mysql_num_rows($result) ) 
{
	print($dbobj->error()."\n");
	return;
}

while( ($row = mysql_fetch_array($result)) )
{
	$stats = $row['stats'];
	if( strlen($stats) ) {
		echo "mysql -uroot -prjkj@rjkj -e \"CREATE DATABASE IF NOT EXISTS $stats default charset utf8 COLLATE utf8_general_ci;\"\n";
	}
}
mysql_free_result($result);	

?>

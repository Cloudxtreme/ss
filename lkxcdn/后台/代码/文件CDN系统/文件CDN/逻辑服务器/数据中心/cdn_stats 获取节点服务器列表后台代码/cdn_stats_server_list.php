<?php
//中文UTF-8

require_once('cdn_db.php');

$server_list = array();

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");

$query = "select * from cdn_file.server_list where `type` = 'node';";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error()); exit;
}

if( mysql_num_rows($result) )
{
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$ip = $row['ip'];
		$port = 7654;
		
		$server_list[$ip] = $port;
	}
}
mysql_free_result($result);

//print_r($server_list);
foreach( $server_list as $ip => $port ) {
	print("$ip:$port\n");
}

?>

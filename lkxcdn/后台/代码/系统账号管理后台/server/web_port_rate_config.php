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
$dbobj->select_db('cdn_server_admin');

$query = "select * from server_list where `ip` = '$serverip'";
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
	
$row = mysql_fetch_array($result);
if( ! $row )
{
	print("not found this serverip $serverip \n");
	return;
}

$nettype = $row['nettype'];

mysql_free_result($result);	

if( $nettype == '网通') {
	$filename = "web_port_rate_config_cnc";
}else {
	$filename = "web_port_rate_config_ct";
}

$handle = fopen($filename, "r");
$contents = fread($handle, filesize ($filename));
fclose($handle);

header("Content-type: text/plain");
//header("Content-Disposition: attachment;filename=run_client.sh");
echo $contents;	

?>

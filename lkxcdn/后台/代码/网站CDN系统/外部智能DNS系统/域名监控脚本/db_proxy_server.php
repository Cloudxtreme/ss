<?php
require_once('db.php');

//HTTPSQS_GET_END
define("HTTPSQS_GET_FMT", "http://127.0.0.1:1218?name=dns_db_query&opt=%s");

global $global_cdn_domain;
global $global_cdn_web_ip;
global $global_cdn_web_db;
global $global_databasename;

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit();
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

$url = sprintf(HTTPSQS_GET_FMT, 'get');
print("$url\n");
$opts = array( 'http'=>array('method'=>"GET",'timeout'=>3,));
$context = stream_context_create($opts);

while(true)
{
	$ret = file_get_contents("$url", false, $context);
	if( ! $ret ) { break; }
	if( $ret == 'HTTPSQS_GET_END' ) { break; }
	
	$query = $ret;
	$ret = $dbobj->query($query);	
	print(date('Y-m-d H:i:s'));
	print(" $query $ret \n");
}

?>

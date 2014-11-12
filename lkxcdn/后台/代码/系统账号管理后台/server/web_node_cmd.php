<?php
require_once('db.php');

//print_r($_SERVER);


if( ! isset($_GET['opcode']) ) {
	exit;
}

$opcode = $_GET['opcode'];

$serverip = $_SERVER['REMOTE_ADDR'];

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error()."\n");
	exit;
}

$dbobj->query("set names utf8;");

$query = "select * from cdn_server_admin.server_list where `ip` = '$serverip'";
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

$query = "SELECT squidname, squidport FROM cdn_web.domain_info where `status` = 'true'";
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

$squids = array();	
while( ($row = mysql_fetch_array($result)) )
{
	$squidname = $row['squidname'];
	$squidport = $row['squidport'];
	$squids[$squidname] = $squidport;
}
mysql_free_result($result);	

$squiddns = "116.28.65.245 116.28.64.162";

if( $nettype == '网通') 
{
	$squiddns = "112.90.178.231 112.90.177.66";
}

//header("Content-type: text/plain");
//header("Content-Disposition: attachment;filename=run_client.sh");

switch( $opcode )
{
	case 'get_cmd':
		get_cmd();
		break;
		
	case 'get_cmd_ret':
		get_cmd_ret();
		break;	
}

function get_cmd()
{
	global $dbobj;
	global $squiddns;
	global $serverip;

	$query = "SELECT * FROM cdn_server_admin.server_cmd where `ip` = '$serverip' and `type` = 'webcdn';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
		return;
	}

	if( ! mysql_num_rows($result) ) 
	{
		//print($dbobj->error()."\n");
		return;
	}

	$row = mysql_fetch_array($result);
	if( ! $row ) 
	{ 
		print("mysql_fetch_array error \n");
		exit; 
	}

	$id = $row['id'];
	$status = $row['status'];
	$cmd = $row['cmd'];
	$args = $row['args'];
	$cmdtime = strtotime($row['timestamp']);
	$nowtime = time();
	if( $status == 'ready' && $nowtime - $cmdtime < 300 ) { return; } //delay 5min
	
	if( $cmd == 'node_create_client' )
	{
		$args = explode(' ', $args);
		if( count($args) != 2 ) { exit; }
		$squidname = $args[0];
		$squidport = $args[1];
		print("cmdid=$id\n");
		print("cmd=node_create_client\n");
		print("dnsserver=$squiddns\n");
		print("squidname=$squidname\n");
		print("squidport=$squidport\n");
		
		$query = "update cdn_server_admin.server_cmd set `status` = 'running', `timestamp` = now() where id = '$id';";
		$dbobj->query($query);
	}
	else if( $cmd == 'node_delete_client' )
	{
		$squidname = $args;
		print("cmdid=$id\n");
		print("cmd=node_delete_client\n");
		print("squidname=$squidname\n");
		
		$query = "update cdn_server_admin.server_cmd set `status` = 'running', `timestamp` = now() where id = '$id';";
		$dbobj->query($query);		
	}
	else if( $cmd == 'node_update_haproxy' )
	{
		print("cmdid=$id\n");
		print("cmd=node_update_haproxy\n");
		
		$query = "update cdn_server_admin.server_cmd set `status` = 'running', `timestamp` = now() where id = '$id';";
		$dbobj->query($query);		
	}
	else {
	}
}

function get_cmd_ret()
{
	global $dbobj;
	global $serverip;

	if( ! isset($_GET['cmdid']) && ! isset($_GET['ret']) ) { exit; }
	
	$cmdid = $_GET['cmdid'];
	$ret = $_GET['ret'];
	
	if( $ret == 'ok' ) {
		$query = "delete from cdn_server_admin.server_cmd where id = '$cmdid' and `ip` = '$serverip';";
	} else {
		$query = "update cdn_server_admin.server_cmd set `status` = 'error', `ret` = '$ret', `timestamp` = now()
		where id = '$cmdid' and `ip` = '$serverip';";
	}
	//print_r($query);
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error()."\n");
	}
	else
	{
		print('ok');
	}
}

?>

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

header("Content-type: text/plain");
//header("Content-Disposition: attachment;filename=run_client.sh");

switch( $opcode )
{
	case 'squid_create':
		squid_create();
		break;
	
	case 'squid_init':
		squid_init();
		break;
		
	case 'squid_run':
		squid_run();
		break;	
}

function squid_create()
{
	global $squids, $squiddns;
	
	foreach( $squids as $squidname => $squidport ) {
		echo "/opt/cdn_client/create_cdn_client.sh $squidname $squidport \"$squiddns\" 1000\n";
	}
}

function squid_init()
{
	global $squids, $squiddns;
	
	foreach( $squids as $squidname => $squidport ) {
		echo "/opt/cdn_client/$squidname"."_squid/squid -f /opt/cdn_client/$squidname"."_squid/squid.conf -z\n";
	}	
}

function squid_run()
{
	global $squids, $squiddns;

	foreach( $squids as $squidname => $squidport ) {
		echo "/opt/cdn_client/$squidname"."_squid/squid -f /opt/cdn_client/$squidname"."_squid/squid.conf\n";
	}		
}

?>

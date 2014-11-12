<?php
//中文UTF-8
require_once('cdn_db.php');
require_once('iplocation.class.php');

define("URL_FMT", "http://%s:%d/%s");

global $global_databasename;

header("Content-type: text/html; charset=UTF-8");

if( ! valid_check() ) { exit; }

$userip = $_SERVER['REMOTE_ADDR'];
$user_fileid = $_GET['user_fileid'];

//check file
$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");


$fileinfo = get_source_file_info($user_fileid);
if( ! $fileinfo ) 
{
	print('该文件没有哦!');
	exit;
}

$client = $fileinfo['client'];

if( ! check_file_delivery_finish($user_fileid) ) 
{
	print('该文件没CDN或者没分发完哦!');
	exit;
}

//get user ip info
$ip_l = new ipLocation();
$address = $ip_l->getaddress($userip);
$address["area1"] = iconv('GB2312', 'utf-8', $address["area1"]);
$address["area2"] = iconv('GB2312', 'utf-8', $address["area2"]);
//print_r($address);
$user_nettype = $address["area2"];
$user_zone = $address["area1"];
//print("$user_nettype $user_zone <br><br>");exit;

if( strstr($user_nettype, '联通') || 
		strstr($user_nettype, '网通') ||
		strstr($user_nettype, '方正')	) {
	$user_nettype = '网通';
}

//get node
$query = "SELECT $global_databasename.server_list.ip, $global_databasename.user_nginx.port, nettype, match1, match2
		FROM $global_databasename.`server_list`, $global_databasename.`user_nginx`
		where
		$global_databasename.server_list.ip = user_nginx.ip
		and
		$global_databasename.user_nginx.`user` = '$client'
		and
		$global_databasename.user_nginx.`status` = 'true';";
		//print($query);

if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( ! mysql_num_rows($result) ) 
{
	print('no node server');
	exit;
}

$cdn_node = array();
while( ($row = mysql_fetch_array($result)) ) 
{
	$ip = $row['ip'];
	$port = $row['port'];
	$nettype = $row['nettype'];
	$match1 = $row['match1'];
	$match2 = $row['match2'];
	$cdn_node[$nettype][] = array( 'ip' => $ip, 'port' => $port, 'match1' => $match1, 'match2' => $match2 );
}
mysql_free_result($result);
//print_r($cdn_node);print("<br><br>");exit;

$cdn_node_info = array();

if( array_key_exists($user_nettype, $cdn_node) )
{
	foreach( $cdn_node[$user_nettype] as $info ) {
		$cdn_node_info[] = $info;
	}
}

if( ! count($cdn_node_info) )
{
	foreach( $cdn_node as $infos ) {
		foreach( $infos as $info ) {
			$cdn_node_info[] = $info;
		}
	}
}

$clientpath = $global_source_cdn_dir . '/' . $client;
$filepathname = $fileinfo['name'];

if( $clientpath != $fileinfo['filepath'] )
{
    $filepathname = str_replace($clientpath.'/', '', $fileinfo['filepath']);
    if( strlen($filepathname) ) {
        $filepathname = $filepathname . '/' . $fileinfo['name'];
    } else {
        $filepathname = $fileinfo['name'];
    }
}

$download_url = '';

//zone match
////////////////////////////////////////////////////////////////////////
shuffle($cdn_node_info);
foreach( $cdn_node_info as $info )
{
	$ip = $info['ip'];
	$port = $info['port'];
	$match1 = $info['match1'];
	$match2 = $info['match2'];
	
	$matchs = explode(' ', $match2); //print_r($matchs);
	
	foreach( $matchs as $match )
	{
		if( ! strstr($user_zone, $match) ) { continue; }
		
		$download_url = sprintf(URL_FMT, $ip, $port, $filepathname);
		//print("<a href=$download_url>$download_url</a><br>");
		header("Location: $download_url");
		exit; // exit now !!!!!!
	}	
}

//random
////////////////////////////////////////////////////////////////////////
shuffle($cdn_node_info);
foreach( $cdn_node_info as $info )
{
	$ip = $info['ip'];
	$port = $info['port'];
	$download_url = sprintf(URL_FMT, $ip, $port, $filepathname);
		
	//print("<a href=$download_url>$download_url</a><br>");
	header("Location: $download_url");
	exit; // exit now !!!!!!
}

//print("<a href=$download_url>$download_url</a><br>");
header("Location: $download_url");
exit;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function get_source_file_info($fileid)
{
	global $global_databasename;
	global $dbobj;
	
	$fileinfo = false;
	
	$query = "select * from $global_databasename.source_file where `fileid` = '$fileid';";
	
	if( ! ($result = $dbobj->query($query)) ) {
		return false;
	}
	
	if( ! mysql_num_rows($result) ) {
		return false;
	}
	
	$row = mysql_fetch_array($result);

	$client = $row['subpath'];
	$client = explode('/', $client);
	if( count($client) >= 2 ) {
		$client = $client[1];
	} else {
		$client = '';
	}
	$fileinfo['client'] = $client;
	$fileinfo['filepath'] = $row['filepath'];
	$fileinfo['subpath'] = $row['subpath'];
	$fileinfo['name'] = $row['filename'];
	mysql_free_result($result);
	
	return $fileinfo;
}

function check_file_delivery_finish($fileid)
{
	global $global_databasename;
	global $dbobj;
	global $client;
	
	$node_server_cnt = 0;
	/*
	//super node
	$query = "select count(*) from $global_databasename.server_list where `type` = 'super_node';";
	if( ! ($result = $dbobj->query($query)) ) {
		return false;
	}
	if( ! mysql_num_rows($result) ) {
		return false;
	}

	$row = mysql_fetch_array($result);
	$node_server_cnt = $row[0];
	mysql_free_result($result);
	*/
	//user node
	$query = "select count(*) from $global_databasename.user_nginx where `user` = '$client';";
	//print_r($query);
	if( ! ($result = $dbobj->query($query)) ) {
		return false;
	}
	if( ! mysql_num_rows($result) ) {
		return false;
	}

	$row = mysql_fetch_array($result);
	$node_server_cnt = $row[0];
	mysql_free_result($result);	

	$query = "select count(*) from $global_databasename.node_file where `filemd5id` = '$fileid';";
	//print_r($query);
	if( ! ($result = $dbobj->query($query)) ) {
		return false;
	}
	
	if( ! mysql_num_rows($result) ) {
		return false;
	}

	$row = mysql_fetch_array($result);
	$finish_cnt = $row[0];
	mysql_free_result($result);

	return $finish_cnt >= $node_server_cnt;
}

function valid_check()
{
	if( ! isset($_GET['user_fileid']) ) { return false; }
	$user_fileid = $_GET['user_fileid'];
	if( strlen($user_fileid) != 32 ) { return false; }
	if( ! ctype_alnum($user_fileid) ) { return false; }

	return true;
}
										
?>

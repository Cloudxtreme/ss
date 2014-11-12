<?php
//中文UTF-8
require_once('cdn_db.php');
require_once('iplocation.class.php');

define("URL_FMT", "http://%s:%d/%s");

header("Content-type: text/html; charset=UTF-8");

//if( ! valid_check() ) { exit; }

$userip = $_SERVER['REMOTE_ADDR'];
$hostname = $_SERVER['HTTP_HOST'];
$filename = $_SERVER['REDIRECT_URL'];

//print_r($_SERVER);exit;
//print("$hostname $filename");

//check file
$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	print($dbobj->error());exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

$query = "select * from `user`, `user_hostname`
						where 
						`user_hostname`.`hostname` = '$hostname'
						and
						`user_hostname`.`owner` = `user`.`user`
						and
						`user`.`status` = 'true';";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error());exit;
}
if( ! mysql_num_rows($result) ) {
	print('no hostname'); exit;
}
$row = mysql_fetch_array($result);
$owner = $row['owner'];
$nginxport = $row['nginxport'];	
mysql_free_result($result);

//get user ip info
$ip_l = new ipLocation();
$address = $ip_l->getaddress($userip);
$address["area1"] = iconv('GB2312', 'utf-8', $address["area1"]);
$address["area2"] = iconv('GB2312', 'utf-8', $address["area2"]);
//print_r($address);
$user_nettype = $address["area2"];
$user_zone = $address["area1"];
//print("$user_nettype $user_zone <br><br>");exit;
//header("Via: $userip");

if( strstr($user_nettype, '联通') || 
		strstr($user_nettype, '网通') ) {
	$user_nettype = '网通';
}

//get node
$query = "SELECT `server_list`.`ip`, `user_nginx`.`port`, nettype, match1, match2
				FROM `server_list`, `user_nginx`
				where
				`server_list`.`ip` = `user_nginx`.`ip`
				and
				`user_nginx`.`user` = '$owner' 
				and
				`user_nginx`.`status` = 'true';";
//print($query);
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error()); exit;
}
if( ! mysql_num_rows($result) ) {
	print('no node server'); exit;
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
		
		$download_url = sprintf(URL_FMT, $ip, $port, "$hostname$filename");
		//print("<a href=$download_url>$download_url</a><br>");
		header("Location: $download_url");
		exit; // exit now !!!!!!
	}
}

//second 2 random
////////////////////////////////////////////////////////////////////////
shuffle($cdn_node_info);
foreach( $cdn_node_info as $info )
{
	$ip = $info['ip'];
	$port = $info['port'];
	$download_url = sprintf(URL_FMT, $ip, $port, "$hostname$filename");
	
	//print("<a href=$download_url>$download_url</a><br>");
	header("Location: $download_url");
	exit; // exit now !!!!!!	
}

//print("<a href=$download_url>$download_url</a><br>");
header("Location: $download_url");

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


function valid_check()
{
	$invalids = array(';', ',', "'", '#');
	
	$hostname = $_SERVER['HTTP_HOST'];
	$filename = $_SERVER['REDIRECT_URL'];
	
	if( ! strstr($hostname, '.') ) {
		return false;
	}	
	$pieces = explode(".", $hostname);
	foreach($pieces as $piece) 
	{
		if( ! preg_match('/^[a-z\d][a-z\d-]{0,62}$/i', $piece) || preg_match('/-$/', $piece) ) {
			return false;
		}
	}
	
	foreach( $invalids as $check )
	{
		if( strstr($filename, $check) ) {
			return false;
		}
	}
	
	return true;
}

										
?>

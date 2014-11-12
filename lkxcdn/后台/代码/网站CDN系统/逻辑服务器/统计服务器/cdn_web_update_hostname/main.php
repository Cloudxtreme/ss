<?php
require_once('cdn_db.php');

$rate_db = "cdn_portrate_stats";
$info_db = "cdn_web";
$log_db = 'cdn_web_log_general';

$day = @date("Y-m-d", time());
//print($day);

/////////////////////////////////////////////////////////////////////

$infodb = new DBObj;
if( ! $infodb->conn2('cdninfo.efly.cc', 'root', 'rjkj@rjkj') ) { exit; }
$infodb->query("set names utf8;");
$infodb->select_db($info_db);
$query = "SELECT * FROM `user_hostname`;";
//print($query);
if( ! ($result = $infodb->query($query)) ) { exit; }
$user_hostnames = array();
while( ($row = mysql_fetch_array($result)) ) 
{
	$domainname = $row['domainname'];
	$owner = $row['owner'];
	$user_hostnames[$domainname] = $owner;
}
mysql_free_result($result);
//print_r($user_hostnames); exit;

/////////////////////////////////////////////////////////////////////

$webdb = new DBObj;
if( ! $webdb->conn2('webstats.cdn.efly.cc', 'root', 'rjkj@rjkj') ) { exit; }
$webdb->query("set names utf8;");
$webdb->select_db($rate_db);
$query = "SELECT distinct `hostname` FROM `$day`;";
//print($query);
if( ! ($result = $webdb->query($query)) ) { exit; }
$hostnames = array();
while( ($row = mysql_fetch_array($result)) ) 
{
	$hostname = $row['hostname'];
	if( strstr($hostname, 'cache.rightgo.net') ) { continue; }
	$hostnames[$hostname] = $hostname;
}
mysql_free_result($result);
//print_r($hostnames);

/////////////////////////////////////////////////////////////////////

$logdb = new DBObj;
if( ! $logdb->conn() ) { exit; }
$logdb->query("set names utf8;");
$logdb->select_db($log_db);
$query = "SELECT * FROM `web_domain`;";
//print($query);
if( ! ($result = $logdb->query($query)) ) { exit; }
$nows = array();
while( ($row = mysql_fetch_array($result)) ) 
{
	$hostname = $row['domain'];
	$owner = $row['owner'];
	$nows[$hostname] = $owner;
}
mysql_free_result($result);
//print_r($nows); 

/////////////////////////////////////////////////////////////////////

foreach( $hostnames as $hostname ) {
	if( isset($nows[$hostname]) ) { 
		$query = "update `web_domain` set `date` = now() where domain = '$hostname';";
		print("$query\n");
		$logdb->query($query);		
		continue; 
	}
	$h_owner = '';
	foreach( $user_hostnames as $domain => $owner ) {
		if( strstr($hostname, $domain) ) {
			$h_owner = $owner;
			break;
		}
	}
	$query = "insert into `web_domain`(`domain`, `owner`, `date`) values('$hostname', '$h_owner', now());";
	print("$query\n");
	$logdb->query($query);
}

?>


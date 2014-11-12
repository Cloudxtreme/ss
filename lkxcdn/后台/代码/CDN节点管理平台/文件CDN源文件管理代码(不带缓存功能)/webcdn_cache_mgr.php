<?php
require_once('usercheck.php');

global $global_admin_db, $global_webcdn_db;

$hostnameport = array('4001', '4002', '4003', '4004');

$userid = webcdn_check_user();
if( ! $userid ) {
	ret_result(1, "用户登录失败", "");
}

$user = $opcode = $url = '';

if( isset($_GET['user']) && 
		isset($_GET['opcode']) && 
		isset($_GET['url']) ) 
{
	$user = $_GET['user'];
	$opcode = $_GET['opcode'];
	$url = $_GET['url'];
}
else if( 	isset($_POST['user']) &&
					isset($_POST['opcode']) && 
					isset($_POST['url']) ) 
{
	$user = $_POST['user'];
	$opcode = $_POST['opcode'];
	$url = $_POST['url'];	
}
else
{
	ret_result(1, '', '');
}

if( ! checkurl($url) ) {
	ret_result(1, '', '');
}

$url = explode(',', $url);
if( count($url) > 10 ) {
	ret_result(1, '条数超过限制', '');
}

switch( $opcode )
{
	case 'cache_purge':
		cache_purge($user);
		break;
}

function cache_purge($client)
{
	global $url;
	global $hostnameport;
	
	$server_info = get_server_info($client);//print_r($server_info);
	if( ! $server_info ) {
		ret_result(1, '', '');
	}
	//print_r($server_info);
	//print_r($hostnameport);

	foreach( $server_info as $ip => $info )
	{
		$sshport = $info['sshport'];
		$user = $info['user'];
		$pass = $info['pass'];
				
		$cmd = make_purge_cmd($hostnameport, $url);
		
		$conn = ssh2_connect($ip, $sshport);
		if( ! $conn ) {
			continue;
		}
		if( ! ssh2_auth_password($conn, $user, $pass) ) {
			continue;
		}
		$stream = ssh2_exec($conn, $cmd);
		if( ! $stream ) {
			continue;
		}
		stream_set_blocking($stream, true);
		$ret = stream_get_contents($stream);
		//print("$ip => $ret \n");
	}
	ret_result(0, '', '');
}

function make_purge_cmd($hostnameport, $url)
{
	$cmd = '';
	foreach( $url as $oneurl ) 
	{
		foreach( $hostnameport as $squidport )
		{
			$cmd = $cmd . "/opt/squid/bin/squidclient -h 127.0.0.1 -p $squidport -m PURGE $oneurl;";
		}
	}
	//print($cmd);exit;
	return $cmd;
}

function get_server_info($client)
{
	global $global_admin_db, $global_webcdn_db;
	global $global_password_key;
	global $hostnameport;
	
	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		return false;
	}
	
	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_webcdn_db);

	$query = "select * from $global_webcdn_db.domain_info where `owner` = '$client';";//print_r($query);
	if( ! ($result = $dbobj->query($query)) ) {
		return false;
	}
	if( ! mysql_num_rows($result) ) {
		return false;
	}
	
	while( ($row = mysql_fetch_array($result)) )
	{
		$hostname = $row['hostname'];
		$squidport = $row['squidport'];
		//$hostnameport[$hostname] = $squidport;
	}
	
	$query = "select * from $global_admin_db.server_list where `type` = 'webnode';";//print($query);
						
	if( ! ($result = $dbobj->query($query)) ) {
		return false;
	}
	if( ! mysql_num_rows($result) ) {
		return false;
	}

	$server_info = array();
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$ip = $row['ip'];
		$server_info[$ip]['sshport'] = $row['port'];
		$server_info[$ip]['user'] = $row['user'];
		
		$pass = $row['pass'];
		$pass = mydecrypt($global_password_key, $pass);
		$server_info[$ip]['pass'] = $pass;
	}
	mysql_free_result($result);
	return $server_info;
}

function checkurl($url)
{
	if( strstr($url, ';') ) { return false; }
	if( strstr($url, '|') ) { return false; }
	if( strstr($url, '&') ) { return false; }
	if( strstr($url, '<') ) { return false; }
	if( strstr($url, '>') ) { return false; }
	if( strstr($url, '..') ) { return false; }
	
	return true;	
}

function ret_result($ret, $error, $data)
{
	/*
	echo '<?xml version="1.0" encoding="utf8"?>';
	echo "<result><ret>$ret</ret><error>$error</error>$data</result>";
	*/
	
	if( isset($_GET['callback']) ) {
		echo $_GET['callback'].'({'. "\"result\":\"$ret\",\"error\":\"$error\"" . '})';
	}
	else if( isset($_POST['callback']) ) {
		echo $_POST['callback'].'({'. "\"result\":\"$ret\",\"error\":\"$error\"" . '})';
	}
	else {
	}
	
	exit();
}

?>

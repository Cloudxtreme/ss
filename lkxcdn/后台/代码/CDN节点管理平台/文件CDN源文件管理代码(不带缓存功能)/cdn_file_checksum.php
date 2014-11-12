<?php
require_once('usercheck.php');

global $global_admin_db, $global_filecdn_db;
global $global_ftp_cdn_dir, $global_source_cdn_dir;

$userid = filecdn_check_user();
if( ! $userid ) {
	ret_result(1, "用户登录失败", "");
}

if( ! isset($_GET['filepath']) ||
		! isset($_GET['filename']) ||
		! isset($_GET['callback']) ) {
	ret_result(1, '', '');
}

$client = $_GET['user'];
$filepath = $_GET['filepath'];
$filename = $_GET['filename'];
$callback = $_GET['callback'];

$ftpfile = "$global_ftp_cdn_dir/$client$filepath/$filename";
$abspath = "$global_source_cdn_dir/$client$filepath";

$server_info = get_server_info();
//print_r($server_info);

$ip = $server_info['ip'];
$sshport = $server_info['sshport'];
$user = $server_info['user'];
$pass = $server_info['pass'];
$conn = ssh2_connect($ip, $sshport);

if( ! $conn ) {
	ret_result(1, 'connect server fail!', '');
}

if( ! ssh2_auth_password($conn, $user, $pass) ) {
	ret_result(1, 'server auth fail!', '');
}

if( ! checkurl($ftpfile) )
{
	ret_result(1, '', '');
}

clean_file_md5($abspath, $filename);

$cmd = "md5sum $ftpfile";
$stream = ssh2_exec($conn, $cmd);
if( ! $stream ) {
	ret_result(1, '', '');
}

stream_set_blocking($stream, true);
$ret = stream_get_contents($stream);
//print("$ip => $ret \n");
if( $ret ) 
{
	$ret = explode(' ', $ret);
	ret_result(0, '', $ret[0]);
} else {
	ret_result(1, '', '');
}

//clean_file_md5($id);

function clean_file_md5($filepath, $filename)
{
	$up_sql = "update source_file set status='ready' where filepath='$filepath' and filename='$filename';";

	global $global_filecdn_db;
	$dbobj = new DBObj;
	if(! $dbobj->conn())
		return false;
	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_filecdn_db);

	$dbobj->query($up_sql);
}

function get_server_info()
{
	global $global_admin_db, $global_filecdn_db;
	global $global_password_key;
	
	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		return false;
	}
	
	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_admin_db);
	
	$query = "select 
						$global_filecdn_db.server_list.ip,
						$global_admin_db.server_list.port as sshport,
						$global_admin_db.server_list.user,
						$global_admin_db.server_list.pass
						from
						$global_filecdn_db.server_list,
						$global_admin_db.server_list
						where
						$global_filecdn_db.server_list.`type` = 'source'
						and
						$global_filecdn_db.server_list.ip = $global_admin_db.server_list.ip;";
						//print($query);
						
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
		$server_info['ip'] = $ip;
		$server_info['sshport'] = $row['sshport'];
		$server_info['user'] = $row['user'];
		
		$pass = $row['pass'];
		$pass = mydecrypt($global_password_key, $pass);
		$server_info['pass'] = $pass;
	}
	mysql_free_result($result);
	return $server_info;
}

function checkurl($str)
{
	if( strstr($str, ';') ) { return false; }
	if( strstr($str, '|') ) { return false; }
	if( strstr($str, '&') ) { return false; }
	if( strstr($str, '<') ) { return false; }
	if( strstr($str, '>') ) { return false; }	
	if( strstr($str, '..') ) { return false; }
	
	return true;	
}

function ret_result($ret, $error, $data)
{
	if( isset($_GET['callback']) ) {
		echo $_GET['callback'].'({'. "\"result\":\"$ret\",\"error\":\"$error\",\"md5\":\"$data\"" . '})';
	} else {
		echo '?'.'({'. "\"result\":\"$ret\",\"error\":\"$error\",\"md5\":\"$data\"" . '})';
	}
	exit();
}

?>

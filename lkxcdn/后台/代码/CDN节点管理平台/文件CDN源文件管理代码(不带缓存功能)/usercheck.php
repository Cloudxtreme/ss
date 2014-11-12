<?php
require_once('db.php');

function filecdn_check_user()
{
	global $global_filecdn_db;
	
	if( ! isset($_GET['user']) || ! isset($_GET['pass']) ) {
		return false;
	}
	
	$user = $_GET['user'];
	$pass = $_GET['pass'];
	
	if( ! valid_check($user, $pass) ) { return false; }
		
	if( strlen($user) <= 0 || strlen($pass) <= 0 ) {
		return false;
	}
	
	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		return false;
	}
			
	$query = "select * from $global_filecdn_db.user where `user` = '$user' and `pass` = '$pass' and `status` = 'true';";
	if( ! ($result = $dbobj->query($query)) ) {
		return false;
	}
			
	$row = mysql_fetch_array($result);
	if( ! $row ) {
		return false;
	}
	
	$userid = $row[0];
	mysql_free_result($result);

	return $userid;
}

function webcdn_check_user()
{
	global $global_webcdn_db;
	
	$user = $pass = '';
	
	if( isset($_GET['user']) && isset($_GET['pass']) ) 
	{
		$user = $_GET['user'];
		$pass = $_GET['pass'];
	}
	else if ( isset($_POST['user']) && isset($_POST['pass']) ) 
	{
		$user = $_POST['user'];
		$pass = $_POST['pass'];
	}
	else
	{
		return false;
	}
	
	if( strlen($user) <= 0 || strlen($pass) <= 0 ) {
		return false;
	}
	
	if( ! valid_check($user, $pass) ) { return false; }
	
	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) { 
		return false;
	}
			
	$query = "select * from $global_webcdn_db.user where `user` = '$user' and `pass` = '$pass' and `status` = 'true';";
	if( ! ($result = $dbobj->query($query)) ) {
		return false;
	}
			
	$row = mysql_fetch_array($result);
	if( ! $row ) {
		return false;
	}
	
	$userid = $row[0];
	mysql_free_result($result);

	return $userid;
}

function valid_check($user, $pass)
{
	$invalids = array(';', ',', "'", '#');
	
	foreach( $invalids as $check )
	{
		if( strstr($user, $check) ) { return false; }
		if( strstr($pass, $check) ) { return false; }
	}
	
	return true;	
}

function myencrypt($key, $plain_text) 
{
	$plain_text = trim($plain_text);
	$iv = substr(md5($key), 0,mcrypt_get_iv_size (MCRYPT_CAST_256,MCRYPT_MODE_CFB));
	$c_t = mcrypt_cfb (MCRYPT_CAST_256, $key, $plain_text, MCRYPT_ENCRYPT, $iv);
	return trim(chop(base64_encode($c_t)));
}

function mydecrypt($key, $c_t) 
{
	$c_t =  trim(chop(base64_decode($c_t)));
	$iv = substr(md5($key), 0,mcrypt_get_iv_size (MCRYPT_CAST_256,MCRYPT_MODE_CFB));
	$p_t = mcrypt_cfb (MCRYPT_CAST_256, $key, $c_t, MCRYPT_DECRYPT, $iv);
	return trim(chop($p_t));
}



?>


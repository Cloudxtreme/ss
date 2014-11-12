<?php
require_once('usercheck.php');

if( ! isset($_POST['opcode']) ) {
	return;
}

$opcode = $_POST['opcode'];

switch( $opcode )
{
	case "login":	
		login();
		break;	
}

function ret_result($ret, $error, $data)
{
	echo '<?xml version="1.0" encoding="utf8"?>';
	echo "<result><ret>$ret</ret><error>$error</error>$data</result>";
	exit();
}

function login()
{
	$userid = check_user();
	
	if( $userid ) {
		ret_result(0, " ", "<userid>$userid</userid>");
	} else {
		ret_result(1, "用户登录失败", "");
	}
}

?>


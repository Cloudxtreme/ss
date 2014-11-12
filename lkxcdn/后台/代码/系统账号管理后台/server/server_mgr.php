<?php
require_once('usercheck.php');

$userid = check_user();
if( ! $userid ) {
	ret_result(1, "用户登录失败", "");
}

if( ! isset($_POST['opcode']) ) {
	ret_result(1, '', '');
}
	
$user = $_POST['user'];
$opcode = $_POST['opcode'];

switch( $opcode )
{
	case 'get_server_type_list':
		get_server_type_list($user);
		break;
		
	case 'add_server_item':
		add_server_item();
		break;
		
	case 'get_server_list':
		get_server_list();
		break;
		
	case 'del_server_item':
		del_server_item();
		break;
		
	case 'mod_server_item':
		mod_server_item();
		break;
		
	case 'server_run_cmd':
		server_run_cmd();
		break;		
}

function add_server_item()
{
	global $global_databasename;
	
	if( ! isset($_POST['user']) ||
			! isset($_POST['pass']) ||
			! isset($_POST['ip']) ||
			! isset($_POST['type']) ||
			! isset($_POST['nettype']) ||
			! isset($_POST['ifdesc']) ||
			! isset($_POST['port']) ||
			! isset($_POST['suser']) ||
			! isset($_POST['spass']) ||
			! isset($_POST['skey']) ||
			! isset($_POST['desc'])
		 ) {
		ret_result(1, '', '');
	}

	$user = $_POST['user'];
	$ip = $_POST['ip'];
	$type = $_POST['type'];
	$nettype = $_POST['nettype'];
	$ifdesc = $_POST['ifdesc'];
	$port = $_POST['port'];
	$suser = $_POST['suser'];
	$spass = $_POST['spass'];
	$skey = $_POST['skey'];
	$desc = $_POST['desc'];

	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		ret_result(1, '', '');
	}

	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_databasename);

	$spass = myencrypt($skey, $spass);

	$query = "insert into server_list(`ip`, `port`, `user`, `pass`, 
						`owner`, `type`, `nettype`, `ifdesc`, `status`, `desc`) values
						('$ip', '$port', '$suser', '$spass', '$user', '$type', '$nettype', '$ifdesc', 'true', '$desc');";
	
	if( ! ($result = $dbobj->query($query)) ) {
		ret_result(1, '', '');
	}
		
	ret_result(0, '', '');
}

function get_server_type_list($user)
{
	global $global_databasename;
	global $global_password_key;
	
	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		ret_result(1, '', '');
	}
	
	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_databasename);
	
	$query = "select distinct `type` from server_list where `owner` = '$user';";
						
	if( ! ($result = $dbobj->query($query)) ) {
		ret_result(1, '', '');
	}
	if( ! mysql_num_rows($result) ) {
		ret_result(0, '', '<typelist></typelist>');
	}

	$typelist = '<typelist>';
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$type = $row['type'];
		$typelist = $typelist . "<type>$type</type>";
	}
	mysql_free_result($result);
	$typelist = $typelist . '</typelist>';
	
	ret_result(0, '', $typelist);
}

function get_server_list()
{
	global $global_databasename;
	global $global_password_key;
	
	$user = $_POST['user'];
	$type = $_POST['type'];
	
	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		ret_result(1, '', '');
	}
	
	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_databasename);
	
	$query = "select * from server_list where `owner` = '$user' and `type` = '$type';";
						
	if( ! ($result = $dbobj->query($query)) ) {
		ret_result(1, '', '');
	}
	if( ! mysql_num_rows($result) ) {
		ret_result(0, '', '<serverlist></serverlist>');
	}

	$typelist = '<serverlist>';
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$id = $row['id'];
		$ip = $row['ip'];
		$port = $row['port'];
		$user = $row['user'];
		$pass = $row['pass'];
		$type = $row['type'];
		$nettype = $row['nettype'];
		$ifdesc = $row['ifdesc'];
		$status = $row['status'];
		$desc = $row['desc'];
		
		$typelist = $typelist . 
								"<server><id>$id</id><ip>$ip</ip><port>$port</port>
								<user>$user</user><pass>$pass</pass><type>$type</type>
								<nettype>$nettype</nettype><ifdesc>$ifdesc</ifdesc>
								<status>$status</status>
								<desc>$desc</desc></server>";
	}
	mysql_free_result($result);
	$typelist = $typelist . '</serverlist>';
	
	ret_result(0, '', $typelist);	
}

function del_server_item()
{
	global $global_databasename;
	global $global_password_key;
	
	if( ! isset($_POST['id']) ) {
		ret_result(1, '', '');
	}
	
	$user = $_POST['user'];
	$id = $_POST['id'];
		
	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		ret_result(1, '', '');
	}
	
	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_databasename);
	
	$query = "delete from server_list where `id` = '$id' and `owner` = '$user';";
						
	if( ! ($result = $dbobj->query($query)) ) {
		ret_result(1, '', '');
	}
	
	ret_result(0, '', $typelist);	
}

function mod_server_item()
{
	global $global_databasename;
	
	if( ! isset($_POST['user']) ||
			! isset($_POST['id']) ||
			! isset($_POST['pass']) ||
			! isset($_POST['ip']) ||
			! isset($_POST['type']) ||
			! isset($_POST['nettype']) ||
			! isset($_POST['ifdesc']) ||
			! isset($_POST['port']) ||
			! isset($_POST['suser']) ||
			! isset($_POST['spass']) ||
			! isset($_POST['skey']) ||
			! isset($_POST['desc'])
		 ) {
		ret_result(1, '', '');
	}

	$user = $_POST['user'];
	$id = $_POST['id'];
	$ip = $_POST['ip'];
	$type = $_POST['type'];
	$nettype = $_POST['nettype'];
	$ifdesc = $_POST['ifdesc'];
	$port = $_POST['port'];
	$suser = $_POST['suser'];
	$spass = $_POST['spass'];
	$skey = $_POST['skey'];
	$status = $_POST['status'];
	$desc = $_POST['desc'];

	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		ret_result(1, '', '');
	}

	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_databasename);

	if( $spass == '' ) 
	{
		$query = "update server_list set 
							`ip` = '$ip', `port` = '$port', `user` = '$suser',
							`type` = '$type' , `nettype` = '$nettype', `ifdesc` = '$ifdesc',
							`status` = '$status' , `desc` = '$desc'
							where `id` = '$id' and `owner` = '$user';";		
	} 
	else 
	{
		$spass = myencrypt($skey, $spass);
		$query = "update server_list set 
							`ip` = '$ip', `port` = '$port', `user` = '$suser', `pass` = '$spass', 
							`type` = '$type', `nettype` = '$nettype', `ifdesc` = '$ifdesc',
							`status` = '$status' , `desc` = '$desc'
							where `id` = '$id' and `owner` = '$user';";
	}
	//print($query);exit;

	if( ! ($result = $dbobj->query($query)) ) {
		ret_result(1, '', '');
	}
		
	ret_result(0, '', '');
}

function server_run_cmd()
{
	global $global_databasename;
	
	if( ! isset($_POST['user']) ||
			! isset($_POST['skey']) ||
			! isset($_POST['ids']) ||
			! isset($_POST['cmd']) 
		 ) {
		ret_result(1, '', '');
	}

	$user = $_POST['user'];
	$skey = $_POST['skey'];
	$ids = $_POST['ids'];
	$cmd = $_POST['cmd'];

	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		ret_result(1, '', '');
	}

	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_databasename);

	$server_info = get_server_info($user, $ids, $skey);
	if( ! $server_info ) {
		print('$server_info null');
	}
	//print_r($server_info);

	foreach( $server_info as $ip => $info )
	{
		$port = $info['port'];
		$user = $info['user'];
		$pass = $info['pass'];
			
		$conn = ssh2_connect($ip, $port);
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
		
		print("$ip \n");
		print("********************************************\n");
		print("$ret \n\n");
	}
}

function get_server_info($user, $ids, $skey)
{
	global $global_databasename;
	
	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		return false;
	}
	
	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_databasename);
	
	$query = "select * from server_list where `owner` = '$user' and `id` in(";
	
	$ids = explode(';', $ids);
	foreach( $ids as $id )
	{
		if( $id != '') {
			$query = $query . "'$id',";
		}
	}
	$query = substr($query, 0, -1);
	$query = $query . ");";
	
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
		$server_info[$ip]['port'] = $row['port'];
		$server_info[$ip]['user'] = $row['user'];
		
		$pass = $row['pass'];
		$pass = mydecrypt($skey, $pass);
		$server_info[$ip]['pass'] = $pass;
	}
	mysql_free_result($result);
	return $server_info;
}

function ret_result($ret, $error, $data)
{
	echo '<?xml version="1.0" encoding="utf8"?>';
	echo "<result><ret>$ret</ret><error>$error</error>$data</result>";
	exit();
}

?>

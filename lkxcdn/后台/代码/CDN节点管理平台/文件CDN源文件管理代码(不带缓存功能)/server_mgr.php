<?php
require_once('usercheck.php');

global $global_databasename;
global $global_memcached_host;
global $global_memcached_port;

/*
$userid = check_user();
if( ! $userid ) {
	ret_result(1, "用户登录失败", "");
}
*/

$user = $_POST['user'];

$memcache_obj = memcache_connect($global_memcached_host, $global_memcached_port);

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}

$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

//get server list
$query = "select * from server_list, user_node
				 where `type` = 'node' and server_list.ip = user_node.ip and user_node.`user` = '$user';";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

$source_list = '<source_list>';
$super_list = '<super_list>';
$node_list = '<node_list>';

if( mysql_num_rows($result) )
{
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$serverid =  $row['id'];
		$serverip = $row['ip'];
		$serverport = $row['port'];
		$type = $row['type'];
		$nettype = $row['nettype'];
		$match1 = $row['match1'];
		$match2 = $row['match2'];
		$zone = $row['zone'];
		$desc = $row['desc'];
		
		$cpu_usage = '';
		$net_usage = '';
		$conn_cnt = '';
		$serverstatus = get_server_status($type, $serverip);
		
		$cpu_usage = get_cpu_usage($serverip);
		$net_usage = get_net_usage($serverip);
		$conn_cnt = get_conn_cnt($serverip, $serverport);
		
		$serverinfo = "<server>
		<serverid>$serverid</serverid>
		<serverip>$serverip</serverip>
		<nettype>$nettype</nettype>
		<match1>$match1</match1>
		<match2>$match2</match2>
		<zone>$zone</zone>
		<desc>$desc</desc>
		<cpu>$cpu_usage</cpu>
		<net>$net_usage</net>
		<conn>$conn_cnt</conn>
		<status>$serverstatus</status>
		</server>";
		
		$node_list = $node_list . $serverinfo;
	}
}

mysql_free_result($result);

$memcache_obj->close();

$source_list = $source_list . '</source_list>';
$super_list = $super_list . '</super_list>';
$node_list = $node_list . '</node_list>';
$data = '<server_list>'.$source_list.$super_list.$node_list.'</server_list>';

ret_result(0, " ", $data);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function ret_result($ret, $error, $data)
{
	echo '<?xml version="1.0" encoding="utf8"?>';
	echo "<result><ret>$ret</ret><error>$error</error>$data</result>";
	exit();
}

function get_cpu_usage($server_ip)
{
	global $memcache_obj;
	
	if( ! $memcache_obj ) {
		return false;
	}
	
	$key = sprintf("%s:161_cpu", $server_ip);
	$value = memcache_get($memcache_obj, $key);
	if( $value ) 
	{
		$value = explode("_", $value);
		$value = $value[0];
	}
	return $value;	
}

function get_net_usage($server_ip)
{
	global $memcache_obj;
	
	if( ! $memcache_obj ) {
		return false;
	}	
	
	$net_usage = '';
	$mifdesc = get_monitor_if_desc($server_ip);
	if( ! $mifdesc ) {
		$mifdesc = 'eth0';
	}
	
	$key = sprintf("%s:161_%s_in", $server_ip, $mifdesc);
	$value = memcache_get($memcache_obj, $key);
	$net_in = $net_out = '';
	if( $value ) 
	{
		$value = explode('_', $value);
		$net_in = (int)($value[0]/$value[1]);	
	}
	
	$key = sprintf("%s:161_%s_out", $server_ip, $mifdesc);
	$value = memcache_get($memcache_obj, $key);
	if( $value ) 
	{
		$value = explode('_', $value);
		$net_out = (int)($value[0]/$value[1]);	
	}
	
	$temp = sprintf("[%s] 流入=%d 流出=%d\n", $mifdesc, $net_in, $net_out);			
	$net_usage = $net_usage . $temp;
	/*
	//all if desc
	for( $index = 0; $index < 10; $index++ )
	{
		$key = sprintf("%s:161_ifDescr.%d", $server_ip, $index);
		$ifname = memcache_get($memcache_obj, $key);
		if( $ifname )
		{
			//in
			$key = sprintf("%s:161_%s_in", $server_ip, $ifname);
			$value = memcache_get($memcache_obj, $key);
			$key = str_replace("$server_ip:161_", "", $key);
			$value = explode('_', $value);
			$net_in = (int)($value[0]/$value[1]);
			
			//out
			$key = sprintf("%s:161_%s_out", $server_ip, $ifname);
			$value = memcache_get($memcache_obj, $key);
			$key = str_replace("$server_ip:161_", "", $key);
			$value = explode('_', $value);
			$net_out = (int)($value[0]/$value[1]);
			
			$temp = sprintf("[%s] 流入=%d 流出=%d\n", $ifname, $net_in, $net_out);			
			$net_usage = $net_usage . $temp;
		}
	}
	*/
	return $net_usage;	
}

function get_monitor_if_desc($server_ip)
{
	global $global_databasename;
	
	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		return false;
	}
	
	$dbobj->query("set names utf8;");
	
	//get source info
	$query = "select * from $global_databasename.server_info where `serverip` = '$server_ip' and `key` = 'ifdesc';";
	if( ! ($result = $dbobj->query($query)) ) {
		return false;
	}
	
	if( mysql_num_rows($result) )
	{
		$row = mysql_fetch_array($result);
		$ifdesc = $row['value'];
		return $ifdesc;
	}
	
	mysql_free_result($result);
	
	return false;
}

function get_conn_cnt($server_ip, $server_port)
{
	global $memcache_obj;
	
	if( ! $memcache_obj ) {
		return false;
	}

	$key = sprintf("%s_conn_%d", $server_ip, $server_port);
	$value = memcache_get($memcache_obj, $key);		
	if( $value ) 
	{
		$value = explode("_", $value);
		$value = $value[0];
	}	
	return $value;	
}

function get_server_status($server_type, $server_ip)
{
	//$ret = false;
		
	switch( $server_type )
	{
		case 'source':
					$ret = get_source_server_status($server_ip);
					break;
						
		case 'super_node':
					$ret = get_super_node_server_status($server_ip);
					break;
						
		case 'node':
					$ret = get_node_server_status($server_ip);
					break;
	}	
	
	return $ret ? '正常' : '异常';	
}

function get_source_server_status($server_ip)
{
	global $memcache_obj;
	global $global_server_status_time;
	
	if( ! $memcache_obj ) {
		return false;
	}

	$key = sprintf("%s.rsync.run", $server_ip);
	$value = memcache_get($memcache_obj, $key);		
	if( $value ) 
	{
		$value = explode("_", $value);
		$pids = $value[0];
		$timestamp = $value[1];
		return $pids > 0 && abs(time() - $timestamp) < $global_server_status_time;
	}	
	return false;
}

function get_super_node_server_status($server_ip)
{
	global $memcache_obj;
	global $global_server_status_time;
	
	if( ! $memcache_obj ) {
		return false;
	}

	$key = sprintf("%s.rsync.run", $server_ip);
	$value = memcache_get($memcache_obj, $key);		
	if( $value ) 
	{
		$value = explode("_", $value);
		$pids = $value[0];
		$timestamp = $value[1];
		return $pids > 0 && abs(time() - $timestamp) < $global_server_status_time;
	}	
	return false;	
}

function get_node_server_status($server_ip)
{
	global $memcache_obj;
	global $global_server_status_time;
	
	if( ! $memcache_obj ) {
		return false;
	}

	$key = sprintf("%s.haproxy.run", $server_ip);
	$value = memcache_get($memcache_obj, $key);		
	if( $value ) 
	{
		$value = explode("_", $value);
		$pids = $value[0];
		$timestamp = $value[1];
		return $pids > 0 && abs(time() - $timestamp) < $global_server_status_time;
	}	
	return false;	
}

?>

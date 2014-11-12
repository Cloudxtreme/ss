<?php
require_once('usercheck.php');

$conf = array();

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error()."\n");
	exit;
}

$dbobj->query("set names utf8;");
$dbobj->select_db('cdn_server_admin');

$query = "select * from haproxy_conf where `status` = 'true';";
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

while( ($row = mysql_fetch_array($result)) ) 
{
	$session = $row['session'];
	$name = $row['name'];
	$key = $row['key'];
	$operator = $row['operator'];
	$value = $row['value'];
	
	if( $name == '' ) 
	{
		$conf[$session][$key]	= $value;
	}
	else if ( $session == 'use_backend' )  
	{
		$conf[$session][$name]['name'] = $name;
		//$conf[$session][$name]['key'] = $key;
		$conf[$session][$name]['value'][$value]['value'] = $value;
		$conf[$session][$name]['value'][$value]['operator'] = $operator;		
	}
	else
	{
		$conf[$session][$name]['name'] = $name;
		$conf[$session][$name]['key'] = $key;
		$conf[$session][$name]['value'] = $value;
		$conf[$session][$name]['operator'] = $operator;
	}
}
mysql_free_result($result);	

//print_r($conf);exit;

$handle = @fopen("haproxy.cfg", "w");
if( ! $handle ) {
	return;
}

$info = $conf['global'];
fwrite($handle, "global\n");
foreach( $info as $key => $value ) {
	fwrite($handle, "\t$key $value\n");
}
fwrite($handle, "\n");

$info = $conf['defaults'];
fwrite($handle, "defaults\n");
foreach( $info as $key => $value ) {
	fwrite($handle, "\t$key $value\n");
}
fwrite($handle, "\n");

$info = $conf['frontend'];
$key = key($info);
$httpin = $info[$key];
fwrite($handle, "frontend $httpin[name]\n");
$key = $httpin['key'];
$value = $httpin['value'];
fwrite($handle, "\t$key $value\n");
fwrite($handle, "\n");

$infos = $conf['acl'];
foreach( $infos as $name => $info )
{
	$name = $info['name'];
	$key = $info['key'];
	$operator = $info['operator'];
	$value = $info['value'];
	fwrite($handle, "acl $name $operator $value\n");
}
fwrite($handle, "\n");

$infos = $conf['use_backend'];
foreach( $infos as $name => $info )
{
	//$name = $info['name'];
	//$key = $info['key'];
	//$operator = $info['operator'];
	foreach( $info['value'] as $subinfo ) {
		fwrite($handle, "use_backend $name $subinfo[operator] $subinfo[value]\n");
	}
}
fwrite($handle, "\n");

$infos = $conf['backend'];
foreach( $infos as $name => $info )
{
	$key = $info['key'];
	$value = $info['value'];
	fwrite($handle, "backend $name\n");
	fwrite($handle, "\tbalance roundrobin\n");
	fwrite($handle, "\tcookie SERVERID insert nocache indirect\n");
	fwrite($handle, "\toption httpchk HEAD /check.txt HTTP/1.0\n");
	fwrite($handle, "\toption httpclose\n");
	fwrite($handle, "\toption forwardfor\n");
	fwrite($handle, "\tserver squid1 127.0.0.1:$value cookie squid1\n");
	fwrite($handle, "\n");
}

fclose($handle);

?>

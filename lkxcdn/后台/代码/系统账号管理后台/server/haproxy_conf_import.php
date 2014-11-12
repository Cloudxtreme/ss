<?php
require_once('usercheck.php');

$acls = $use_backends = $backends = array();

$handle = @fopen("haproxy.cfg", "r");
if( ! $handle ) {
	return;
}

while( ! feof($handle) ) 
{
	$line = fgets($handle, 4096);
	$line = ltrim($line);
	$line = rtrim($line);

	if( strlen($line) == 0 ) { continue; }
	if( substr($line, 0, 1) == '#' ) { continue; }
	
	if( substr($line, 0, strlen('acl')) == 'acl' ) { 
		acl($line);
	}
	if( substr($line, 0, strlen('use_backend')) == 'use_backend' ) { 
		use_backend($line);
	}	
	if( substr($line, 0, strlen('backend')) == 'backend' ) 
	{
		//backend mediacdn
		$info = explode(' ', $line);
		$name = $info[1];
		
		while( 1 )
		{
			$line = fgets($handle, 4096);
			$line = ltrim($line);
			$line = rtrim($line);
			if( substr($line, 0, strlen('server')) == 'server' ) { 
				break;
			}
		}
		//server squid1 127.0.0.1:3124 cookie squid1
		$info = explode(' ', $line);
		$port = $info[2];
		$port = explode(':', $port);
		$port = $port[1];		
		$backends[$name] = $port;
	}
}
fclose($handle);

print_r($acls);
print_r($use_backends);
print_r($backends);

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error()."\n");
	exit;
}

$dbobj->query("set names utf8;");
$dbobj->select_db('cdn_server_admin');

foreach( $acls as $name => $info )
{
	$operator = $info['operator'];
	$value = $info['value'];
	$query = "insert into haproxy_conf(`session`, `name`, `key`, `operator`, `value`) 
					values('acl', '$name', '', '$operator', '$value')";
	$dbobj->query($query);
}

foreach( $use_backends as $name => $info )
{
	$backname = $info['name'];
	$operator = $info['operator'];
	$value = $info['value'];
	$query = "insert into haproxy_conf(`session`, `name`, `key`, `operator`, `value`) 
					values('use_backend', '$backname', '', '$operator', '$value')";
	$dbobj->query($query);
}

foreach( $backends as $name => $port )
{
	$query = "insert into haproxy_conf(`session`, `name`, `key`, `operator`, `value`) 
					values('backend', '$name', 'port', '', '$port')";
	$dbobj->query($query);	
}

function acl($line)
{
	global $acls;
	//acl is.ikusoo.net.cn hdr_end(host) -i ikusoo.net.cn
	
	$info = explode(' ', $line);
	$name = $info[1];
	$operator = $info[2];
	$value = $info[3] . ' ' . $info[4];
	
	$acls[$name]['operator'] = $operator;
	$acls[$name]['value'] = $value;
}

function use_backend($line)
{
	global $use_backends;
	//use_backend ikusoo.net.cn if is.ikusoo.net.cn
	
	$info = explode(' ', $line);
	$name = $info[1];
	$operator = $info[2];
	$value = $info[3];
	
	$use_backends[$value]['name'] = $name;
	$use_backends[$value]['operator'] = $operator;
	$use_backends[$value]['value'] = $value;	
}

?>

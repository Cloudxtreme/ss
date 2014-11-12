<?php
/////////use async check
require_once('db.php');


//$hosts = array("192.168.1.50", "192.168.1.60", "192.168.1.70");
//$timeout = 15;

global $cdninfo_ip;
global $cdninfo_user;
global $cdninfo_pass;
global $cdninfo_web_database;

$dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_web_database);
//while(1)
//{
	$hosts = nodes_get($dbobj);
	$status = nodes_check($hosts, 15);
	nodes_update($dbobj, $hosts, $status);
	//sleep(1);
//}

function nodes_get($dbobj)
{
	$hosts = array();
	$query = "select ip from server_list_tmp where type = 'node' and nettype != '移动';";
	if( ! ($result = db_query($dbobj, $query)) ) 
	{
   		 print($dbobj->error()); 
   		 return false;
	}

	while( ($row = mysql_fetch_array($result)) )
	{
      	 	$ip = $row['ip'];
      	 	$hosts[] = $ip;
	}
	mysql_free_result($result);
	return $hosts;
}

function nodes_append($nodes, $host)
{
	if(strlen($nodes) == 0)
	{
		$nodes = $nodes . "'$host'";
	}
	else
	{
		$nodes = $nodes . "," . "'$host'";
	}
	return $nodes;
}

function nodes_update($dbobj, $hosts, $status)
{
	$sql_node_ok = "update server_list_tmp set status = 'true' where ip in(";
	$sql_node_bad = "update server_list_tmp set status = 'false' where ip in(";
	
	$ok_nodes = "";
	$bad_nodes = "";
	foreach ($hosts as $id => $host) 
	{ 
		//printf("node : %s, ret : %s\n", $host, $status[$id]);
		$http_ret = $ret = $status[$id];
		$http_ret = explode(' ', $http_ret);
		if( count($http_ret) < 2 )
		{
			printf("unknow error!\n");
			continue;
		}
		$httpcode = $http_ret[1];
		if( $httpcode >= '200' && $httpcode < '500' ) 
		{ 
			//printf("true\n"); 
			$ok_nodes = nodes_append($ok_nodes, $host);
			continue;
		}
		if( strstr($ret, 'X-Cache') || strstr($ret, 'squid') ) 
		{ 
			//printf("true\n");
			$ok_nodes = nodes_append($ok_nodes, $host);
			continue;
		}
		$bad_nodes = nodes_append($bad_nodes, $host);
		printf("bad node : %s -- %s\n", $host, $status[$id]);
	}

	if(strlen($ok_nodes))
	{
		$sql_node_ok = $sql_node_ok . $ok_nodes . ");";
		db_sql_exec($dbobj, $sql_node_ok, 1);
		//printf("ok : %s\n", $sql_node_ok);
	}

	if(strlen($bad_nodes))
	{
		$sql_node_bad = $sql_node_bad . $bad_nodes . ");";
		db_sql_exec($dbobj, $sql_node_bad, 1);
		//printf("bad : %s\n", $sql_node_bad);
	}
}

function nodes_check($hosts, $timeout)
{
	$status = array();
	$sockets = array();
	/* Initiate connections to all the hosts simultaneously */
	foreach ($hosts as $id => $host) 
	{
		$s = stream_socket_client("$host:80", $errno, $errstr, $timeout,  STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_CONNECT);
 		if($s) 
		{  
			$sockets[$id] = $s;  
			$status[$id] = "in progress";
		} 
		else
		{  
			$status[$id] = "failed, $errno $errstr"; 
		}
	}

	$write = $sockets; 

	/* Now, wait for the results to come back in */
	$out = "HEAD / HTTP/1.1\r\n";
	$out .= "Host: www.efly.cc\r\n";
	$out .= "Connection: Close\r\n\r\n";

	/* writeable sockets can accept an HTTP request */  
	foreach ($write as $w) 
	{   
		$id = array_search($w, $sockets);   
		if ($status[$id] == "in progress")
		{
			fwrite($w, $out);
			$status[$id] = "waiting for response";  
		}
	} 	

	while (count($sockets)) 
	{
		/* This is the magic function - explained below */ 
	
		$read = $sockets;
		$n = stream_select($read, $write = null, $e = null, $timeout); 
		if ($n > 0) 
		{  
			/* readable sockets either have data for us, or are failed   * connection attempts */  
			foreach ($read as $r)
			{
				$id = array_search($r, $sockets);      
				$data = fread($r, 8192);
				if (strlen($data) == 0) 
				{
					fclose($r);   
					unset($sockets[$id]);     
				} 
				else 
				{   
					$status[$id] .= $data;      
				}  
			}  
		} 
		else 
		{
			/* timed out waiting; assume that all hosts associated   * with $sockets are faulty */  
			foreach ($sockets as $id => $s) 
			{   
				$status[$id] = "timed out " . $status[$id];  
			}  
			break; 
		}
	}
	return $status;
}
?>

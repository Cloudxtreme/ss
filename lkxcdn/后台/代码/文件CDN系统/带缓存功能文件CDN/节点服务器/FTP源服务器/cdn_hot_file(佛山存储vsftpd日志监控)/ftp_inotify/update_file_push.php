<?php
	require_once('db.php');

	function db_gethandle($db_ip, $db_user, $db_pass, $databasename)
	{
		$dbobj = new DBObj;
		while( ! $dbobj->conn2($db_ip, $db_user, $db_pass) ) 
		{
			printf("conn error!\n");
			print($dbobj->error());
			sleep(3);
		}
		$dbobj->query("set names utf8;");
		$dbobj->select_db($databasename);
		return $dbobj;
	}

	function db_query($dbobj, $query)
	{
		return $dbobj->query($query);	
	}

	function nodes_get($dbobj)
	{
		$hosts = array();
		$query = "select distinct ip from node_list;";
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

	function update_file_push($dbobj, $file, $nodes)
	{
		$sql = "";
		foreach ($nodes as $node)
		{
			if(strlen($sql) == 0)
				$sql = "insert into file_push(filename, serverip) values ('$file','$node')";
			else
				$sql .= ",('$file','$node')";
		}
		$sql .= ";";
		//printf("\nfile:%s\n", $file);
		db_query($dbobj, $sql);
	}

	global $cdninfo_ip;
	global $cdninfo_user;
	global $cdninfo_pass;
	global $cdninfo_database;

	global $cdnfile_ip;
	global $cdnfile_user;
	global $cdnfile_pass;
	global $cdnfile_database;
	
	if(!strstr($argv[1], ".zip.unzip"))
	{
		$cdninfo_dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_database);
		$cdnfile_dbobj = db_gethandle($cdnfile_ip, $cdnfile_user, $cdnfile_pass, $cdnfile_database);
	
		$nodes = nodes_get($cdninfo_dbobj);
		update_file_push($cdnfile_dbobj, $argv[1], $nodes);
	}
?>

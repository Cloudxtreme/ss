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

	function get_file_id($dbobj)
	{
		$sql = "select max(id) from file_list";
		$file_id = 0;
		
		if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                        return $file_id;
                }

                $row = mysql_fetch_array($result);
		$file_id = $row['max(id)'];
                mysql_free_result($result);
                return $file_id;
	}

	function update_file_push($dbobj, $file, $filesize, $owner, $lastmodify, $nodes)
	{
		$sql = "";
		$file_id = 0;
		$file_id = get_file_id($dbobj);

		foreach ($nodes as $node)
		{
			if(strlen($sql) == 0)
				$sql = "insert into file_push(fileid,filename,filesize,owner,lastmodify,serverip,status) 
					values ($file_id,'$file',$filesize,'$owner','$lastmodify','$node','ready')";
			else
				$sql .= ",($file_id,'$file',$filesize,'$owner','$lastmodify','$node','ready')";
		}
		$sql .= " on duplicate key update filesize=$filesize,lastmodify='$lastmodify',status='ready';";
		//$sql .= ";";
		//printf("\nsql:%s\n", $sql);
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
	
	$file = $argv[1];
	$filesize = $argv[2];
	$owner = $argv[3];
	$lastmodify = "$argv[4] $argv[5]";

	if(!stristr($file, ".zip.unzip"))
	{
		$cdninfo_dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_database);
		$cdnfile_dbobj = db_gethandle($cdnfile_ip, $cdnfile_user, $cdnfile_pass, $cdnfile_database);
	
		$nodes = nodes_get($cdninfo_dbobj);
		update_file_push($cdnfile_dbobj, $file, $filesize, $owner, $lastmodify, $nodes);
	}
?>

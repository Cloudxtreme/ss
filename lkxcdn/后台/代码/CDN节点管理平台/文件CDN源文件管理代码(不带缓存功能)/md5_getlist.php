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

	function get_sourcefilename($dbobj, $fileid)
	{
		$sql = "select filepath, filename from source_file where fileid='$fileid';";
		if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                         return "";
                }

                if( !($row = mysql_fetch_array($result)) )
			return "";
		$filename = $row['filepath'];
		$filename .= "/";
		$filename .= $row['filename'];
		mysql_free_result($result);
		return $filename;
	}

	function md5_file_list($dbobj, $id, $fileid)
	{
		$filename = get_sourcefilename($dbobj, $fileid);
		if(strlen($filename))
		{
			$md5_url = "file $filename\n";
			$md5_url .= "callback http://cdnmgr.efly.cc/cdn_server_admin/md5_callback.php?id=$id\n";
			return $md5_url;
		}
	}

	function md5_getlist($dbobj, $nodeip)
	{
		$query = "select id,fileid from md5_file where status='ready' and serverip = '$nodeip';";
		$update_status = "";
		
		if( ! ($result = db_query($dbobj, $query)) ) 
		{
   		 	print($dbobj->error()); 
   			 return false;
		}

		while( ($row = mysql_fetch_array($result)) )
		{
			$id = $row['id'];
			$fileid = $row['fileid'];

			if(!strlen($update_status))
				$update_status = "update md5_file set status='md5ing' where id in($id";
			else
				$update_status .= ",$id";
			echo md5_file_list($dbobj, $id, $fileid);
		}

		$update_status .= ");";
		db_query($dbobj, $update_status);
		mysql_free_result($result);
		//return $node_file_list;
	}

	global $cdninfo_ip;
	global $cdninfo_user;
	global $cdninfo_pass;
	global $cdninfo_database;

	$cdninfo_dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_database);
	
	$nodeip = $_SERVER['REMOTE_ADDR'];

	md5_getlist($cdninfo_dbobj, $nodeip);

?>

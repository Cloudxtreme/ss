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

	function update_file_list($dbobj, $del_list)
	{
		foreach($del_list as $filename)
		{
			$sql = "update file_list set status='delete_finish',lastmodify=now() where filename='$filename' and status='delete' 
				and 0=(select count(*) from file_push where filename='$filename' and status='delete');";
			db_query($dbobj, $sql);
		}
		$up_sql = "delete fl,fp from file_list fl,file_push fp where fl.filename=fp.filename and fl.status='delete_finish' and To_Days(now())-To_Days(fl.lastmodify) >= 1;";
		db_query($dbobj, $up_sql);
	}

	global $cdninfo_ip;
	global $cdninfo_user;
	global $cdninfo_pass;
	global $cdninfo_file_database;

	$cdnfile_dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdnfile_database);
	$del_file_list = get_del_list($cdnfile_dbobj);
	update_file_list($cdnfile_dbobj, $del_file_list);
	
?>

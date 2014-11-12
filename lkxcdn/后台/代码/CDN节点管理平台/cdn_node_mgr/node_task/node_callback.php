<?php

	require_once('db.php');

	function node_web_callback($dbobj)
	{
		$id = $_GET['id'];

		$sql = "update node_task set task_finish_time=now(),status='finish' where id=$id;";

		db_query($dbobj, $sql);
	}

	function node_file_callback($dbobj)
	{
		$task_result = "";
		if( isset($_GET['ret']) )
			$task_result = $_GET['ret'];

		$id = $_GET['id'];

                $sql = "update node_task set task_finish_time=now(),task_result='$task_result',status='finish' where id=$id;";

                db_query($dbobj, $sql);
	}


        global $cdnmgr_ip;
        global $cdnmgr_user;
        global $cdnmgr_pass;
        global $cdnmgr_web_database;
	global $cdnmgr_file_database;

	$cdn_type = $_GET['cdn_type'];
	
	$dbobj = "";
	if($cdn_type == "web")
	{
		$dbobj = db_gethandle($cdnmgr_ip, $cdnmgr_user, $cdnmgr_pass, $cdnmgr_web_database);
		node_web_callback($dbobj);
	}
	else
	{
		$dbobj = db_gethandle($cdnmgr_ip, $cdnmgr_user, $cdnmgr_pass, $cdnmgr_file_database);
		node_file_callback($dbobj);
	}

?>


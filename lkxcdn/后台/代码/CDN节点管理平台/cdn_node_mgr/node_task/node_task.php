<?php
	require_once('db.php');

	function node_type($nodeip)
	{
		global $cdninfo_ip;
		global $cdninfo_user;
		global $cdninfo_pass;
		global $cdninfo_web_database;
		global $cdninfo_file_database;

		$dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_web_database);
		$sql = "select id from server_list where ip='$nodeip';";
		if( ($result = db_query($dbobj, $sql)) )
		{
                	if(mysql_num_rows($result))
			{
				mysql_free_result($result);
				return "web";
			}
		}

		$dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_file_database);
		$sql = "select id from server_list where ip='$nodeip';";
		if( ($result = db_query($dbobj, $sql)) )
		{
                	if(mysql_num_rows($result))
			{
				mysql_free_result($result);
				return "file";
			}
		}

		return "error";
	}

	function web_task()
	{
		$web_task_content = file_get_contents("/opt/cdn_node_mgr/node_task/tasks/web_task.sh");
		echo $web_task_content;
	}

	function file_task()
	{
		$file_task_content = file_get_contents("/opt/cdn_node_mgr/node_task/tasks/file_task.sh");
		echo $file_task_content;
	}

	$nodeip = "";
	if($_GET)
		$nodeip = $_GET['ip'];

	if(!strlen($nodeip))
		$nodeip = $_SERVER['REMOTE_ADDR'];
	
	$node_t = node_type($nodeip);
	
	switch($node_t)
	{
		case "web":
			//echo "web node\n";
			web_task();
			break;

		case "file":
			//echo "file node\n";
			file_task();
			break;

		default:
			//echo "error node";
	}
	
?>

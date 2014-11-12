<?php

	require_once('db.php');

	function check_file_md5_status($dbobj, $file_id)
        {
                $ret = "";
                $sql = "select id,node_type from node_task where task_from_id=$file_id and task_type='file_md5' and status!='finish';";

                if( ! ($result = db_query($dbobj, $sql)) )
                        return "fail";
		$not_finish = mysql_num_rows($result);
                if($not_finish > 0)
		{
			mysql_free_result($result);
			return "doing";
		}
                mysql_free_result($result);

		$sql = "select ip from node_task where task_from_id=${file_id} and task_type='file_md5' and task_arg!='' and task_result!='' and task_arg!=task_result;";

                if( ! ($result = db_query($dbobj, $sql)) )
                        return "fail";
                if(mysql_num_rows($result) > 0)
                {
                        $ret = "fail";
                }
                else
			$ret = "finish";

                mysql_free_result($result);
                return $ret;
        }
	
	function check_file_cache_status($dbobj, $file_id)
	{
		$proxy = 0;
		$proxy_done = 0;
		$done = 0;
		$all = 0;
		$check_node = "";
		$bad_node = "";
		$redo_node = "";
		$sql_done = "select id from node_task where task_from_id=$file_id and task_type='file_cache' and status='finish';";
		$sql_all = "select id,ip,node_type,task_arg,task_result,unix_timestamp(now())-unix_timestamp(task_start_time) as task_use_time,task_limit_time,status from node_task where task_from_id=$file_id and task_type='file_cache';";
		if( ($result = db_query($dbobj, $sql_all)) )
		{
			$all = mysql_num_rows($result);
			while(($row = mysql_fetch_array($result)))
			{
				$id = $row['id'];
				$ip = $row['ip'];
				$node_type = $row['node_type'];
				$task_arg = $row['task_arg'];
				$task_result = $row['task_result'];
				$task_use_time = $row['task_use_time'];
				$task_limit_time = $row['task_limit_time'];
				$status = $row['status'];

				if($node_type == "node_proxy")
					$proxy++;
				if($status == "finish")
				{
					if($task_arg != $task_result)
					{
						if(!strlen($redo_node))
							$redo_node = "'$ip'";
						else
							$redo_node .= ",'$ip'";
					}
					else
					{
						if($node_type == "node_proxy")
							$proxy_done++;
						$done++;
					}
				}
				else
				{
					if(($task_use_time > $task_limit_time) && ($node_type == "node"))
					{
						if(strlen($check_node))
							$check_node .= ",'$ip'";
						else
							$check_node = "'$ip'";
					}
				}
			}
			mysql_free_result($result);
		}
		if(strlen($check_node))
		{
			$node_status = array();
			$sql_nginx = "select ip,status from user_nginx where ip in ($check_node) order by ip,status;";
			if(($result = db_query($dbobj, $sql_nginx)))
			{
				while(($row = mysql_fetch_array($result)))
				{
					$node_status["$ip"] = $status;
				}
				mysql_free_result($result);
			}
			foreach($node_status as $ip=>$status)
			{
				if($status == "false")
				{
					if(strlen($bad_node))
						$bad_node .= ",'$ip'";
					else
						$bad_node = "'$ip'";
				}
			}
			if(strlen($bad_node))
			{
				$sql_del = "delete from node_task where ip in ($bad_node) and status!='finish';";
				db_query($dbobj, $sql_del);
			}
		}
		if(strlen($redo_node))
		{
			$redo_sql = "update node_task set status='ready' where task_from_id=$file_id and task_type='file_cache' and ip in ($redo_node);";
			db_query($dbobj, $redo_sql);
		}
		if(($proxy > 0) && ($proxy == $proxy_done))
		{
			$sql = "update node_task set status='ready' where task_from_id=$file_id and node_type='node' and task_type='file_cache' and status='wait';";
			db_query($dbobj, $sql);
		}
		if($all == 0) $all = 1;
		$ret = $done / $all * 100;
		return $ret;
	}

	function check_zipfile_cache_status($dbobj, $filename)
	{
		$done = 0;
                $all = 0;
                $sql_done = "select id from file_list where extract='$filename' and status='finish';";
                $sql_all = "select id from file_list where extract='$filename';";
                if( ($result = db_query($dbobj, $sql_done)) )
                {
                        $done = mysql_num_rows($result);
                        mysql_free_result($result);
                }
                if( ($result = db_query($dbobj, $sql_all)) )
                {
                        $all = mysql_num_rows($result);
                        mysql_free_result($result);
                }
		if($all == 0) $all = 1;
                $ret = $done / $all * 100;
		return $ret;
	}

	function node_add_task($dbobj, $task_from_id, $task_type, $task_arg, $task_limit_time, $task_failreset, $node_type, $task_status)
	{
		$real_limit_time = $task_limit_time + 60;
		$sql = "insert into node_task(ip,node_type,task_from_id,task_type,task_arg,task_limit_time,task_failreset,status) 
			select distinct match1,'$node_type',$task_from_id,'$task_type','$task_arg',$real_limit_time,$task_failreset,'$task_status' from server_list 
			where type='$node_type' 
			on duplicate key update task_type='$task_type',task_arg='$task_arg',task_start_time=NULL,task_finish_time=NULL,task_limit_time=$real_limit_time,
						task_failreset=$task_failreset,task_retry=0,task_result='',status='$task_status';";

		return db_query($dbobj, $sql);
	}

	function node_check_web_task($dbobj, $task_from_id, $task_type)
	{
		$ret = "doing";
		switch($task_type)
		{
			case "web_cache":
				$sql = "select id from node_task where task_from_id=$task_from_id and status!='finish';";
				if( ($result = db_query($dbobj, $sql)) )
				{
                			if(!mysql_num_rows($result))
						$ret = "finish";
					mysql_free_result($result);
				}
				break;

			default:;
		}
		return $ret;
	}

	function node_check_file_task($dbobj, $task_from_id, $task_type)	
	{
		$ret = "doing";
                switch($task_type)
                {
                        case "file_md5":
                                $ret = check_file_md5_status($dbobj, $task_from_id);
                                break;

			case "file_cache":
				$ret = check_file_cache_status($dbobj, $task_from_id);
				break;

                        default:;
                }
                return $ret;
	}

	function node_check_task($dbobj, $cdn_type, $task_from_id, $task_type)
	{
		if($cdn_type == "web")
                        return node_check_web_task($dbobj, $task_from_id, $task_type);
                else
                        return node_check_file_task($dbobj, $task_from_id, $task_type);
	}

	function node_web_cache_check($dbobj)
	{
		global $web_cache_task_timeout;
		global $web_cache_task_failreset;

		$sql = "select id,url,url_type,status from web_cache_mgr where status!='finish';";
	
		if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                        return false;
                }
                while( ($row = mysql_fetch_array($result)) )
		{
			$id = $row['id'];
			$url = $row['url'];
			$url_type = $row['url_type'];
			$status = $row['status'];

			switch($status)
			{
				case "ready":
					if(node_add_task($dbobj, $id, "web_cache", "", $web_cache_task_timeout, $web_cache_task_failreset, "node", "ready"))
					{
						$sql = "update web_cache_mgr set start_time=now(),status='doing' where id=$id";
						db_query($dbobj, $sql);
					}
					break;
			
				case "doing":
				case "fail":
					$ret = node_check_task($dbobj, "web", $id, "web_cache");
					if($ret != $status)
					{
						$sql = "update web_cache_mgr set finish_time=now(),status='$ret' where id=$id;";
						db_query($dbobj, $sql);
						if(($ret == "finish") && ($url_type == "path"))
							file_get_contents("http://weblogdw.cdn.efly.cc/cdn_web_cache_mgr/clean_cache_url_list.php?url=$url");
					}
					break;
				
				default:;
			}
		}
		mysql_free_result($result);
	}

	function node_file_md5_check($dbobj)
	{
		global $file_md5_task_timeout;
		global $file_md5_task_failreset;

		$sql = "select id,`md5`,`md5_source`,type from file_list where (`md5`='ready' or `md5`='doing') and type='push' and status='finish';";

                if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                        return false;
                }
                while( ($row = mysql_fetch_array($result)) )
		{
                        $id = $row['id'];
			$md5_stat = $row['md5'];
			$md5_source = $row['md5_source'];
			$type = $row['type'];
                        switch($md5_stat)
                        {
                                case "ready":
					if($type == "delete")
						break;
                                        if(node_add_task($dbobj, $id, "file_md5", $md5_source, $file_md5_task_timeout, $file_md5_task_failreset, "node", "ready"))
					{
                                        	$sql = "update file_list set `md5`='doing' where id=$id";
                                        	db_query($dbobj, $sql);
					}
                                        break;

                                case "doing":
                                case "fail":
                                        $ret = node_check_task($dbobj, "file", $id, "file_md5");
					if($ret == "finish")
					{
						$sql = "update file_list set `md5`=`md5_source` where id=$id;";
						db_query($dbobj, $sql);
					}
                                        else if($ret != $status)
                                        {
                                                $sql = "update file_list set `md5`='$ret' where id=$id;";
                                                db_query($dbobj, $sql);
                                        }
                                        break;

                                default:;
                        }
                }
                mysql_free_result($result);
	}

	function node_file_cache_check($dbobj)
	{
		global $file_cache_minsize;
		global $file_cache_tasklimit_eachowner;
		global $file_cache_task_timeout;
		global $file_cache_task_failreset;

		$owner_task_doing = array();
		$owner_task_add = array();

		$task_limit_time = 0;


		$sql = "select owner,count(*) as owner_tasks from file_list where status='doing' group by owner;";
		if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                        return false;
                }

                while( ($row = mysql_fetch_array($result)) )
		{
			$owner = $row['owner'];
			$owner_tasks = $row['owner_tasks'];

			$owner_task_doing["$owner"] = $owner_tasks;
		}
		mysql_free_result($result);


		$sql = "select id,status,filename,filesize,md5_source,owner,opera_time,type from file_list where status='ready' or status='doing' or status='fail';";
                if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                        return false;
                }

                while( ($row = mysql_fetch_array($result)) )
                {
                        $id = $row['id'];
                        $status = $row['status'];
			$filename = $row['filename'];
			$filesize = $row['filesize'];
			$filemd5 = $row['md5_source'];
			$owner = $row['owner'];
			$opera_time = $row['opera_time'];
			$type = $row['type'];
			$task_limit_time = floor($filesize / 1000000) + $file_cache_task_timeout;

			if(!isset($owner_task_doing["$owner"]))
				$owner_task_doing["$owner"] = 0;
			if(!isset($owner_task_add["$owner"]))
				$owner_task_add["$owner"] = 0;

                        switch($status)
                        {
                                case "ready":
					if(($owner_task_doing["$owner"] + $owner_task_add["$owner"]) >= $file_cache_tasklimit_eachowner)
					{
						break;
					}
					if(($filesize < $file_cache_minsize) && ($type == "push") && ($opera_time == 0))
					{
						$sql = "update file_list set percent=100,status='finish' where id=$id;";
						db_query($dbobj, $sql);
						break;
					}
					if(strcasecmp(substr($filename, -6), ".unzip"))
					{
						if($type == "delete")
							$filemd5 = "";
                                        	if(node_add_task($dbobj, $id, "file_cache", $filemd5, $task_limit_time, $file_cache_task_failreset, "node", "wait")
							&& node_add_task($dbobj, $id, "file_cache", $filemd5, $task_limit_time, $file_cache_task_failreset, "node_proxy", "ready"))
						{
							$sql = "update file_list set status='doing' where id=$id";
                                        		db_query($dbobj, $sql);
						}
					}
					else
					{
                                        	$sql = "update file_list set status='doing' where id=$id";
                                        	db_query($dbobj, $sql);
					}
					$owner_task_add["$owner"]++;
                                        break;

                                case "doing":
                                case "fail":
					if(!strcasecmp(substr($filename, -6), ".unzip"))
						$ret = check_zipfile_cache_status($dbobj, $filename);
					else
                                        	$ret = node_check_task($dbobj, "file", $id, "file_cache");
                                        if(($ret != "finish") and ($ret != "fail"))
                                        {
						if($ret < 100)
                                                	$sql = "update file_list set percent=$ret where id=$id;";
						else
							$sql = "update file_list set percent=$ret,status='finish' where id=$id;";
                                                db_query($dbobj, $sql);
                                        }
					else if($ret != $status)
					{
						$sql = "update file_list set opera_time=now(),status='$ret' where id=$id;";
					}
                                        break;

                                default:;
                        }
                }
                mysql_free_result($result);
	}

	function node_task_timeout_check($dbobj, $task_type, $task_timeout, $task_retry)
	{
		$sql_fail = "update node_task set status='fail' 
				where task_type='$task_type' and status='doing' and task_retry=$task_retry and unix_timestamp(now())-unix_timestamp(task_start_time)>task_limit_time;";

		$sql_retry = "update node_task set task_retry=task_retry+1,status='ready' 
				where task_type='$task_type' and status='doing' and task_retry<$task_retry and unix_timestamp(now())-unix_timestamp(task_start_time)>task_limit_time;";

		$sql_reset = "update node_task set task_retry=0,status='ready' 
				where task_type='$task_type' and status='fail' and unix_timestamp(now())-unix_timestamp(task_start_time)>task_failreset;";

		//db_query($dbobj, $sql_fail);
		db_query($dbobj, $sql_retry);
		//db_query($dbobj, $sql_reset);
	}

	function node_web_check($dbobj)
	{
		global $web_cache_task_timeout;
		global $task_retry;

		node_web_cache_check($dbobj);
		node_task_timeout_check($dbobj, "web_cache", $web_cache_task_timeout, $task_retry);
	}

	function node_file_check($dbobj)
	{
		global $file_cache_task_timeout;
		global $file_md5_task_timeout;
		global $task_retry;

		node_file_md5_check($dbobj);
		node_file_cache_check($dbobj);

		//node_task_timeout_check($dbobj, "file_md5", $file_md5_task_timeout, $task_retry);
		//node_task_timeout_check($dbobj, "file_cache", $file_cache_task_timeout, $task_retry);
	}

        global $cdnmgr_ip;
        global $cdnmgr_user;
        global $cdnmgr_pass;
        global $cdnmgr_web_database;
	global $cdnmgr_file_database;

	$dbobj = db_gethandle($cdnmgr_ip, $cdnmgr_user, $cdnmgr_pass, $cdnmgr_web_database);
	node_web_check($dbobj);

	$dbobj = db_gethandle($cdnmgr_ip, $cdnmgr_user, $cdnmgr_pass, $cdnmgr_file_database);
	node_file_check($dbobj);

?>


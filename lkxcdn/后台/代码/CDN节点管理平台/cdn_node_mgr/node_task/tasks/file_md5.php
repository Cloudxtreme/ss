<?php
	require_once('../db.php');
	require_once('task.php');

	function get_user_port($dbobj)
        {
                $user_port = array();
                $query = "select user,nginxport from user;";
                if( ! ($result = db_query($dbobj, $query)) )
                {
                        print($dbobj->error());
                         return false;
                }

                while( ($row = mysql_fetch_array($result)) )
                {
                        $user = $row['user'];
                        $port = $row['nginxport'];
                        $user_port[$user] = $port;
                }
                mysql_free_result($result);
                return $user_port;
        }

	function md5_task($nodeip, $node_type, $id, $file, $owner, $port)
        {
		$push_url = "";
		if($node_type == "cache_source")
		{
			$push_url .= "md5_sum=`md5sum $file | cut -d \" \" -f 1`\n";
		}
		else
		{
			$file = substr($file, strlen("/var/ftp/pub/")+strlen($owner));
			$file_url = "$nodeip:$port$file";
			$md5_key = md5($file_url);
			$push_url .= "md5_sum=`python /opt/node_task/md5_check.py ${md5_key}`\n";
                	//$md5_keyfile = "/opt/node_task/file/" . md5("$file");
                	//$push_url .= build_loopcmd("/usr/bin/wget -t 3 http://$nodeip:$port$file -O ${md5_keyfile}");
			//$push_url .= "md5_sum=`md5sum ${md5_keyfile} | cut -d \" \" -f 1`\n";
			//$push_url .= "rm -f ${md5_keyfile}\n";
		}
                $push_url .= build_loopcmd("/usr/bin/wget -t 3 -T 30 \"http://cdnmgr.efly.cc/cdn_node_mgr/node_task/node_callback.php?cdn_type=file&id=$id&ret=\$md5_sum\" -O /dev/null");
                echo $push_url;
		return true;
        }

	function md5_file_task($filename, $owner, $port, $nodeip, $node_type, $task_id)
	{
		$ret = false;
		echo "##task_id=${task_id}\n";
		$ret = md5_task($nodeip, $node_type, $task_id, $filename, $owner, $port);
		return $ret;
	}

	function file_md5_task($dbobj, $nodeip, $task_list)
        {
                $md5_id_list = "";
                $sql = "";

                foreach($task_list as $task_from_id => $id)
                {       
                        if(!strlen($md5_id_list))
                                $md5_id_list = "$task_from_id";
                        else
                                $md5_id_list .= ",$task_from_id";
                }

                if(!strlen($md5_id_list)) 
                {
                        echo "";
                        return false;
                }

		$user_port = get_user_port($dbobj);
                        
                $sql = "select id,filename,owner from file_list where id in($md5_id_list);";
                if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                        return false;
                }               
                while( ($row = mysql_fetch_array($result)) )
		{
			$md5_id = $row['id'];
			$filename = $row['filename'];
			$owner = $row['owner'];

			$node_type = get_task_node_type($dbobj, $task_list[$md5_id]);
			$ret = md5_file_task($filename, $owner, $user_port[$owner], $nodeip, $node_type, $task_list[$md5_id]);
                        if($ret == false)
                        {
                                $sql = "update file_list set status='fail' where id=$md5_id;";
                                db_query($dbobj, $sql);
                        }
		}
        }

	global $cdninfo_ip;
	global $cdninfo_user;
	global $cdninfo_pass;
	global $cdninfo_file_database;

	$dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_file_database);
	
	$nodeip = $_SERVER['REMOTE_ADDR'];

	$task_list = get_task($dbobj, $nodeip, "file_md5");
	file_md5_task($dbobj, $nodeip, $task_list);
?>

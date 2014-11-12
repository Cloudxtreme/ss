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

	function push_file($dbobj, $nodeip, $id, $file, $filesize, $owner, $port)
        {
		global $file_cache_ftp_host;
		global $file_cache_task_timeout;

		$key = array();
		$node_type = get_task_node_type($dbobj, $id);
		$limit_time = floor($filesize / 1000000) + $file_cache_task_timeout;
		if($node_type == 'node')
		{
                	$file = substr($file, strlen("/var/ftp/pub/")+strlen($owner));
			$file_url = "$nodeip:$port$file";
			$key[] = md5("$nodeip:$port$file");
			$key[] = md5("$file");
		}
		else if($node_type == 'node_proxy')
		{
			$file = substr($file, strlen("/var/ftp/pub"));
			$port = 9000;
			$file_url = "$file";
			$key[] = md5("$file");
		}
		
		$md5_key = md5($file_url);

		$push_url = "";
                $push_url .= "/usr/bin/wget -t 3 -T 300 \"http://$nodeip:$port/rjkjcdn-purge$file\" -O /dev/null\n";
                $push_url .= build_loopcmd("/usr/bin/wget -t 3 \"http://$nodeip:$port$file\" -O /dev/null");
		//$push_url .= "md5_sum=`python /opt/node_task/md5_check.py ${md5_key}`\n";
		$push_url .= "md5_key=(";
		foreach($key as $val)
		{
			$push_url .= "\"$val\"\n";
		}
		$push_url .= ")\n";
		$push_url .= "md5_sum=\"\"\n";
		$push_url .= "for key in \${md5_key[*]};do\n";
		$push_url .= "\tif [ \"\$md5_sum\" = \"\" ]; then\n";
		$push_url .= "\t\tmd5_sum=`python /opt/node_task/md5_check.py \$key`\n";
		$push_url .= "\tfi\n";
		$push_url .= "done\n";
		//$push_url .= "/usr/bin/wget -t 3 -T 300 http://$file_cache_ftp_host:$port/rjkjcdn-purge$file -O /dev/null\n";
                //$push_url .= build_loopcmd("/usr/bin/wget -t 3 http://$file_cache_ftp_host:$port$file -O /dev/null");
                $push_url .= build_loopcmd("/usr/bin/wget -t 3 -T 30 \"http://cdnmgr.efly.cc/cdn_node_mgr/node_task/node_callback.php?cdn_type=file&id=$id&ret=\$md5_sum\" -O /dev/null");
                echo $push_url;
		return true;
        }

        function delete_file($dbobj, $nodeip, $id, $file, $filesize, $owner, $port)
        {
		global $file_cache_ftp_host;
                $file = substr($file, strlen("/var/ftp/pub/")+strlen($owner));

		$push_url = "";
                $push_url .= "/usr/bin/wget -t 3 -T 300 \"http://$nodeip:$port/rjkjcdn-purge$file\" -O /dev/null\n";
		//$push_url .= "/usr/bin/wget -t 3 -T 300 http://$file_cache_ftp_host:$port/rjkjcdn-purge$file -O /dev/null\n";
                $push_url .= build_loopcmd("/usr/bin/wget -t 3 -T 30 \"http://cdnmgr.efly.cc/cdn_node_mgr/node_task/node_callback.php?cdn_type=file&id=$id\" -O /dev/null");
                echo $push_url;
		return true;
        }

	function cache_file($dbobj, $filename, $filesize, $owner, $port, $type, $nodeip, $task_id)
	{
		$ret = false;
		echo "##task_id=${task_id}\n";
		switch($type)
		{
			case "push":
				$ret = push_file($dbobj, $nodeip, $task_id, $filename, $filesize, $owner, $port);
				break;

			case "delete":
				$ret = delete_file($dbobj, $nodeip, $task_id, $filename, $filesize, $owner, $port); 
				break;

			default:;
		}
		return $ret;
	}

	function file_cache_task($dbobj, $nodeip, $task_list)
        {
                $cache_id_list = "";
                $sql = "";

                foreach($task_list as $task_from_id => $id)
                {       
                        if(!strlen($cache_id_list))
                                $cache_id_list = "$task_from_id";
                        else
                                $cache_id_list .= ",$task_from_id";
                }

                if(!strlen($cache_id_list)) 
                {
                        echo "";
                        return false;
                }

		$user_port = get_user_port($dbobj);
                        
                $sql = "select id,filename,filesize,owner,type from file_list where id in($cache_id_list);";
                if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                        return false;
                }               
                while( ($row = mysql_fetch_array($result)) )
		{
			$cache_id = $row['id'];
			$filename = $row['filename'];
			$filesize = $row['filesize'];
			$owner = $row['owner'];
			$type = $row['type'];

			$ret = cache_file($dbobj, $filename, $filesize, $owner, $user_port[$owner], $type, $nodeip, $task_list[$cache_id]);
                        if($ret == false)
                        {
                                $sql = "update file_list set status='fail' where id=$cache_id;";
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

	$task_list = get_task($dbobj, $nodeip, "file_cache");
	file_cache_task($dbobj, $nodeip, $task_list);
?>

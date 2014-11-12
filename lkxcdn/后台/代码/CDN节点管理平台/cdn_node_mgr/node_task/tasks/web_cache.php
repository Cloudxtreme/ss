<?php

	require_once('../db.php');
	require_once('task.php');

	function get_user_domain($owner)
	{
		$user_domain = "";
		global $cdninfo_ip;
                global $cdninfo_user;
                global $cdninfo_pass;
                global $cdninfo_web_database;

                $dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_web_database);
		$sql = "select domainname from user_hostname where status='true' and owner='$owner';";
		if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                        return false;
                }
                while( ($row = mysql_fetch_array($result)) )
		{
			$domainname = $row['domainname'];
			if(!strlen($user_domain))
				$user_domain = $domainname;
			else
				$user_domain .= ",$domainname";
		}
		mysql_free_result($result);
		return $user_domain;
	}
	
	function check_url($url, $owner, $nodeip)
	{
		$user_domain = get_user_domain($owner);
		
		preg_match("/^(http:\/\/)?([^\/]+)/i", "$url", $matches);
        	$host = $matches[2];
        	preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
        	$url_domain = $matches[0];

		if(!strstr($user_domain, $url_domain))
			return false;

		if( strstr($url, ';') ) { return false; }
        	if( strstr($url, '|') ) { return false; }
       		if( strstr($url, '&') ) { return false; }
        	if( strstr($url, '<') ) { return false; }
        	if( strstr($url, '>') ) { return false; }
        	if( strstr($url, '..') ) { return false; }

		return true;
	}

	function cache_single_url($url, $op_type, $owner, $nodeip, $node_task_id)
	{
		if(check_url($url, $owner, $nodeip) == false)
			return false;
		
		$url = str_replace('http://', '', $url);
        	$host = substr($url, 0, stripos($url, '/'));
        	$uri = str_replace($host, '', $url);

		if(!strlen($uri))
			return true;
		if(substr($uri, 0, 1) != "/")
			return true;

		switch($op_type)
		{
			case "clean":
				echo "/usr/bin/curl -s -m 60 --retry 1 -X PURGE -H \"Host: $host\" \"http://$nodeip$uri\" -o /dev/null\n";
				break;

			case "push":
				echo build_loopcmd("/usr/bin/curl -s -m 600 --retry 1 -H \"Host: $host\" \"http://$nodeip$uri\" -o /dev/null\n");
				break;
	
			case "update":
				echo "/usr/bin/curl -s -m 60 --retry 1 -X PURGE -H \"Host: $host\" \"http://$nodeip$uri\" -o /dev/null\n";
				echo build_loopcmd("/usr/bin/curl -s -m 600 --retry 1 -H \"Host: $host\" \"http://$nodeip$uri\" -o /dev/null\n");
				break;

			default:;
		}
		return true;
	}

	function cache_url_from_file($url, $op_type, $owner, $nodeip)
	{
		$url_list = "";

		$ret = file_get_contents("$url");
		if( ! $ret )
			return false;
		$urls = explode("\n", $ret);
		foreach( $urls as $tempurl )
		{
        		$c_url = trim($tempurl);
        		if( ! strlen($c_url) ) { continue; }
			cache_single_url($c_url, $op_type, $owner, $nodeip, $node_task_id);
		}
		
		return true;
	}

	function cache_url_path($url, $op_type, $owner, $nodeip, $node_task_id)
	{
		$url_list = "";

                $ret = file_get_contents("http://weblogdw.cdn.efly.cc/cdn_web_cache_mgr/get_cache_url_list.php?url=$url");
                if( ! $ret )
                        return false;
                $urls = explode("\n", $ret);
                foreach( $urls as $tempurl )
                {
                        $c_url = trim($tempurl);
                        if( ! strlen($c_url) ) { continue; }
                        cache_single_url($c_url, $op_type, $owner, $nodeip, $node_task_id);
                }
		//$ret = file_get_contents("http://weblogdw.cdn.efly.cc/cdn_web_cache_mgr/clean_cache_url_list.php?url=$url");

                return true;
	}

	function cache_url($url, $url_type, $op_type, $owner, $nodeip, $node_task_id)
	{
		$ret = false;
		echo "##task_id=${node_task_id}\n";
		switch($url_type)
		{
			case "single":
				$ret = cache_single_url($url, $op_type, $owner, $nodeip, $node_task_id);
				break;

			case "multi":
				$ret = cache_url_from_file($url, $op_type, $owner, $nodeip, $node_task_id);
				break;

			case "path":
				$ret = cache_url_path($url, $op_type, $owner, $nodeip, $node_task_id);
				break;
	
			default:;
		}
		//if($ret == true)
			echo build_loopcmd("/usr/bin/curl -s -m 60 --retry 1 \"http://cdnmgr.efly.cc/cdn_node_mgr/node_task/node_callback.php?cdn_type=web&id=$node_task_id\" -o /dev/null\n");
		return $ret;
	}
	
	function web_cache_task($dbobj, $nodeip, $task_list)
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

                $sql = "select id,url,url_type,owner,type from web_cache_mgr where id in($cache_id_list);";
                if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                        return false;
                }
                while( ($row = mysql_fetch_array($result)) )
                {
                        $cache_id = $row['id'];
                        $url = $row['url'];
                        $url_type = $row['url_type'];
                        $owner = $row['owner'];
                        $op_type = $row['type'];

                        //echo "$cache_id,$url,$url_type,$url_local,$owner,$op_type\n";

                        $ret = cache_url($url, $url_type, $op_type, $owner, $nodeip, $task_list[$cache_id]);
			if($ret == false)
			{
				//$sql = "update web_cache_mgr set finish_time=now(),status='fail' where id=$cache_id;";
				//db_query($dbobj, $sql);
			}
                }
		mysql_free_result($result);
	}

	global $cdninfo_ip;
	global $cdninfo_user;
	global $cdninfo_pass;
	global $cdninfo_web_database;

	$nodeip = "";
        if($_GET)
                $nodeip = $_GET['ip'];

        if(!strlen($nodeip))
                $nodeip = $_SERVER['REMOTE_ADDR'];

	$dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_web_database);

	$task_list = get_task($dbobj, $nodeip, "web_cache");
	web_cache_task($dbobj, $nodeip, $task_list);

?>


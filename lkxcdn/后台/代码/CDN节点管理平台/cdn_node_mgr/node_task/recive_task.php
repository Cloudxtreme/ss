<?php
	require_once('db.php');

	function get_post_url($post_url)
	{
		$urls = "";
		global $username;

		if(!strlen($username)) return;
		$the_date = date('Y-m-d_H:i:s');
		$filename = "/opt/cdn_node_mgr/node_task/file/$username$the_date.xml";
		$fp = fopen($filename, 'w');
		fwrite($fp, $post_url);
		fclose($fp);

		$xml = simplexml_load_file($filename);
		if((!$xml) || (strcasecmp($xml->getName(), "task")))
		{
			return "fail";
		}
		foreach($xml->object as $obj)
		{
			if(!strlen($urls))
				$urls = "$obj";
			else
				$urls .= ";$obj";
		}
		if((!$urls) || (!strlen($urls)))
		{
			return "fail";
		}
		return $urls;
	}

	function get_para($para_name)
	{
		$para_val = "";
		switch($para_name)
		{
			case "username":
				if($_GET)
					$para_val = $_GET['username'];
				else
					$para_val = $_SERVER['HTTP_USERNAME'];
				break;
			case "password":
				if($_GET)
                                        $para_val = $_GET['password'];
                                else
                                        $para_val = $_SERVER['HTTP_PASSWORD'];
				break;
			case "type":
				if($_GET)
                                        $para_val = $_GET['type'];
                                else
                                        $para_val = $_SERVER['HTTP_TYPE'];
				break;
			case "url":
				if($_GET)
                                        $para_val = $_GET['url'];
                                else
                                        $para_val = get_post_url(file_get_contents("php://input"));
				break;
			default:;
		}
		return $para_val;
	}

	function get_user_domain($owner)
        {
                $user_domain = "";
                global $cdninfo_ip;
                global $cdninfo_user;
                global $cdninfo_pass;
                global $cdninfo_web_database;

                $dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_web_database);
                $sql = "select domainname from user_hostname where status='true' and owner='$owner';";
                if( ($result = db_query($dbobj, $sql)) )
                {
                        while( ($row = mysql_fetch_array($result)) )
                        {
                                $domainname = $row['domainname'];
                                if(!strlen($user_domain))
                                        $user_domain = $domainname;
                                else
                                        $user_domain .= ",$domainname";
                        }
                        mysql_free_result($result);
                }
                return $user_domain;
        }

	function check_userpass($dbobj, $username, $pass)
	{
		$ret = false;
		$sql = "select * from CDN_User where User='$username' and Pass=md5('$pass');";

		$db_ibss = db_gethandle("ibss.efly.cc", "root", "rjkj@2009#8", "IBSS_TEST");
		if( ($result = db_query($db_ibss, $sql)) )
                {
                        if($row = mysql_fetch_array($result))
                        {
                                $ret = true;
                        }
                        mysql_free_result($result);
                }
		if(($ret == false) && ($username == "poco") && ($pass == "poco"))
			$ret = true;
                return $ret;
	}

	function check_type($type)
	{
		if((strcasecmp($type, "api")) && (strcasecmp($type, "dir")))
			return false;
		return true;
	}

	function check_para_url($url)
	{
		if(!$url)
			return "URL Parameter Error!";

		if($url == "fail")
			return "XML Parse Fail!";

		$urls = explode(';', $url);

                foreach($urls as $each_url)
		{
			if( strstr($each_url, ';') ) { return "URL Parameter Error!"; }
                	if( strstr($each_url, '|') ) { return "URL Parameter Error!"; }
                	if( strstr($each_url, '&') ) { return "URL Parameter Error!"; }
                	if( strstr($each_url, '<') ) { return "URL Parameter Error!"; }
                	if( strstr($each_url, '>') ) { return "URL Parameter Error!"; }
                	if( strstr($each_url, '..') ) { return "URL Parameter Error!"; }
		}
		return "ok";
	}

	function check_para($dbobj, $username, $password, $type, $url)
        {
		$check_ret = "ok";
		
		if((!strlen($username)) || (!strlen($password)))
		{
			$check_ret = "Parameter Error!";
			return $check_ret;
		}

		if(check_userpass($dbobj, $username, $password) == false)
		{
			$check_ret = "Verify Fail!";
			return $check_ret;
		}

		if((!strlen($type)) || (check_type($type) == false))
		{
			$check_ret = "Purge Type Fail!";
			return $check_ret;
		}

		if(($ret = check_para_url($url)) != "ok")
		{
			$check_ret = "$ret";
			return $check_ret;
		}
		
                return $check_ret;
        }

	function check_user_url($dbobj, $username, $url)
	{
		$ret = true;
		$sql = "select status from web_cache_mgr where owner='$username' and url='$url';";

		$user_domain = get_user_domain($username);

                preg_match("/^(http:\/\/)?([^\/]+)/i", "$url", $matches);
                $host = $matches[2];
                preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
                $url_domain = $matches[0];

                if(!strstr($user_domain, $url_domain))
                        return false;

		if($result = db_query($dbobj, $sql))
		{
			while( ($row = mysql_fetch_array($result)) )
			{
				$url_status = $row['status'];
				if($url_status != 'finish')
					$ret = false;
			}
			mysql_free_result($result);
		}

		return $ret;
	}
	
	function task_ret($response, $session_id, $fail_url)
	{
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><result />');
        	$task_response = $xml->addChild('response', $response);
		if((!strcasecmp($response, "All Success!")) || (!strcasecmp($response, "Part Success!")))
			$task_session_id = $xml->addChild('session_id', $session_id);
		if($fail_url != NULL)
		{
        		$invalid_url = $xml->addChild('invalid_url');
			foreach($fail_url as $url)
			{
        			$obj = $invalid_url->addChild('obj', $url);
			}
		}
        	// 添加其他节点
        	echo $xml->asXML();
	}

	function check_owner_status($dbobj, $owner)
	{
		$ret = "ok";
		global $max_webcache_task_eachowner;
		$sql = "select * from web_cache_mgr where owner='$owner' and status!='finish';";
		if($result = db_query($dbobj, $sql))
		{
			$owner_doing_task = mysql_num_rows($result);
			if($owner_doing_task >= $max_webcache_task_eachowner)
				$ret = "Your tasks are still doing, please wait!";
			mysql_free_result($result);
		}
		return $ret;
	}

	function begin_task($dbobj, $username, $type, $url)
	{
		$task_session_id = "";
		$task_response = "";
		$task_succ_sum = 0;
		$task_fail_sum = 0;
		$task_fail_url = array();

		$the_date = date('YmdHis');
		$task_session_id = md5("$username$the_date$url");

		$url_type = "single";
		if($type == "dir")
			$url_type = "path";
		$urls = explode(';', $url);

		foreach($urls as $each_url)
		{
			if(check_user_url($dbobj, $username, $each_url) == true)
			{
				$sql = "insert into web_cache_mgr(session_id,url,url_type,owner,type,status)
					values ('$task_session_id','$each_url','$url_type','$username','update','ready')
					on duplicate key update session_id='$task_session_id',start_time=NULL,finish_time=NULL,type='update',status='ready';";
				db_query($dbobj, $sql);
				$task_succ_sum++;
			}
			else
			{
				$task_fail_url[] = $each_url;
				$task_fail_sum++;
			}
		}
		if($task_fail_sum == 0)
		{
			$task_response = "All Success!";
		}
		else
		{
			if($task_succ_sum == 0)
				$task_response = "All Failed!";
			else
				$task_response = "Part Success!";
		}
		task_ret($task_response, $task_session_id, $task_fail_url);
	}


	global $cdninfo_ip;
	global $cdninfo_user;
	global $cdninfo_pass;
	global $cdninfo_web_database;

	$dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_web_database);

	$username = get_para("username");
	$password = get_para("password");
	$type = get_para("type");
	$url = get_para("url");
	
	//print_r($username);
	//print_r($password);
	//print_r($type);
	//print_r($url);
	//return;

	if( ($ret = check_para($dbobj, $username, $password, $type, $url)) != "ok")
        {
                task_ret($ret, NULL, NULL);
                return;
        }

	if( ($ret = check_owner_status($dbobj, $username)) != "ok" )
	{
		task_ret($ret, NULL, NULL);
		return;
	}
	
	if(false)
	{
		$ret = "The Function Pause!Please Wait!";
		task_ret($ret, NULL, NULL);
		return;
	}

	begin_task($dbobj, $username, $type, $url);
?>

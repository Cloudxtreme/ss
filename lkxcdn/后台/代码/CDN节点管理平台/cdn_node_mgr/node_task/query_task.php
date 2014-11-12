<?php
	require_once('db.php');

	function get_post_session_id($post_session_id)
	{
		$session_ids = "";
		global $username;

		if(!strlen($username)) return;
		$the_date = date('Y-m-d_H:i:s');
		$filename = "/opt/cdn_node_mgr/node_task/file/$username$the_date.xml";
		$fp = fopen($filename, 'w');
		fwrite($fp, $post_session_id);
		fclose($fp);

		$xml = simplexml_load_file($filename);
		if((!$xml) || (strcasecmp($xml->getName(), "query")))
		{
			return "fail";
		}
		foreach($xml->session_id as $session_id)
		{
			if(!strlen($session_ids))
				$session_ids = "$session_id";
			else
				$session_ids .= ";$session_id";
		}
		if((!$session_ids) || (!strlen($session_ids)))
		{
			return "fail";
		}
		return $session_ids;
	}

	function get_session_status($dbobj, $username, $session_id)
	{
		$session_status = array();
		$sql = "select url,status from web_cache_mgr where owner='$username' and session_id='$session_id';";

		if( ($result = db_query($dbobj, $sql)) )
                {
                        while($row = mysql_fetch_array($result))
                        {
				$url = $row['url'];
				$status = $row['status'];
                                $session_status[$url] = $status;
                        }
                        mysql_free_result($result);
                }
		return $session_status;
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
			case "session_id":
				if($_GET)
                                        $para_val = $_GET['session_id'];
                                else
                                        $para_val = get_post_session_id(file_get_contents("php://input"));
				break;
			default:;
		}
		return $para_val;
	}

	function check_userpass($dbobj, $username, $pass)
	{
		$ret = false;
		$sql = "select * from user where user='$username' and pass=md5('$pass');";

		if( ($result = db_query($dbobj, $sql)) )
                {
                        if($row = mysql_fetch_array($result))
                        {
                                $ret = true;
                        }
                        mysql_free_result($result);
                }
                return $ret;
	}

	function check_session_id($session_id)
	{
		return "ok";
	}

	function check_para($dbobj, $username, $password, $session_id)
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

		if(($ret = check_session_id($session_id)) != "ok")
		{
			$check_ret = "$ret";
			return $check_ret;
		}
		
                return $check_ret;
        }

	function query_ret($query_response, $session_result)
	{
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><result />');
		$response = $xml->addChild('response', $query_response);
		
		if(!strcasecmp($query_response, "ok"))
		foreach($session_result as $session_id=>$session_status)
		{
			$session = $xml->addChild('session');
			$session->addAttribute('id', $session_id);

			foreach($session_status as $url=>$status)
			{
				$x_url = $session->addChild('url', $url);
				$x_url->addAttribute('status', $status);
			}
		}
        	// 添加其他节点
        	echo $xml->asXML();
	}

	function begin_query($dbobj, $username, $session_id)
	{
		$session_result = array();

		$session_ids = explode(';', $session_id);

		foreach($session_ids as $each_id)
		{
			$session_result[$each_id] = get_session_status($dbobj, $username, $each_id);
		}

		/*foreach($session_result as $session_id=>$session_status)
		{
			print_r("$session_id");
			foreach($session_status as $url=>$status)
			{
				print_r("$url $status");
			}
		}*/
		query_ret("ok", $session_result);
	}


	global $cdninfo_ip;
	global $cdninfo_user;
	global $cdninfo_pass;
	global $cdninfo_web_database;

	$dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_web_database);

	$username = get_para("username");
	$password = get_para("password");
	$session_id = get_para("session_id");
	
	//print_r($username);
	//print_r($password);
	//print_r($session_id);
	//return;

	if( ($ret = check_para($dbobj, $username, $password, $session_id)) != "ok")
        {
                query_ret($ret, NULL);
                return;
        }

	begin_query($dbobj, $username, $session_id);
?>

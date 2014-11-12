<?php
	require_once('db.php');
	require_once('data/iplocation.class.php');

	ini_set('memory_limit', '-1');

	function parse_each_record($dbobj, $args)
	{
		$sql_data = array();

		$parse_date = $args[0];
		$parse_hour = $args[1];
		$parse_group = $args[2];

		$parse_user = $args[3];
		$parse_file = $args[4];

		$ipl = $args[5];
		$node_list = $args[6];

		$handle = fopen("$parse_file", "r");
		if(!$handle)
		{
			echo "Get file content error!\n";
			return false;
		}

		while(!feof($handle))
		{
			$record = fgets($handle);
			$file_status = explode("|", $record);

			if(count($file_status) < 3) continue;

			$http_status = $file_status[3];
			if(($http_status >= 400) && ($http_status < 500))
				continue;

			$client_ip = $file_status[1];
			if(isset($node_list["$client_ip"]))
				continue;

			$address = $ipl->getaddress($client_ip);
        		$ip_nettype = iconv('GB2312', 'utf-8', $address["area2"]);

			if(strstr($ip_nettype, "电信"))
				$ip_nettype = "电信";
			else if((strstr($ip_nettype, "联通")) || (strstr($ip_nettype, "网通")) || (strstr($ip_nettype, "铁通")))
				$ip_nettype = "网通";
			else if((strstr($ip_nettype, "大学")) || (strstr($ip_nettype, "教育网")))
				$ip_nettype = "教育网";
			else if(strstr($ip_nettype, "移动"))
				$ip_nettype = "移动";
			else if(strstr($ip_nettype, "长城宽带"))
				$ip_nettype = "长宽";
			else
				$ip_nettype = "其他";

			$request = explode(" ", $file_status[2]);
			if(count($request) < 2)
				continue;
			$filename = $request[1];
			if(strstr($filename, "http://"))
                        {
                                $filename = substr($filename, strlen("http://"));
                                $filename = substr($filename, strpos($filename, "/"));
                        }
			$host = $file_status[8];
                        $host = trim($host);
                        if((strlen($host)) && (!filter_var($host, FILTER_VALIDATE_IP)))
                                $filename = "/$host$filename";
			$namearr = explode("/", $filename);
			if(count($namearr) < 3)
				$hostname = "";
			else
				$hostname = $namearr[1];

			$http_r_time = $file_status[4];
			$http_send = $file_status[5];
			if($http_send == 0)
				continue;
	
			if($http_r_time < 1)
				$http_r_time = 1;	
			$bandwidth = $http_send / $http_r_time;
			$flow = $http_send;

			if(!isset($sql_data["$hostname"]["$ip_nettype"]))
			{
				$sql_data["$hostname"]["$ip_nettype"]["bandwidth"] = 0;
				$sql_data["$hostname"]["$ip_nettype"]["flow"] = 0;
			}
			$sql_data["$hostname"]["$ip_nettype"]["bandwidth"] += $bandwidth;
			$sql_data["$hostname"]["$ip_nettype"]["flow"] += $flow;

		}
		fclose($handle);

		$parse_minute = $parse_group * 5;
		if($parse_minute < 10)
			$parse_minute = "0" . $parse_minute;
		$time = "${parse_hour}:${parse_minute}:00";
		
		$sql_head = "insert into `${parse_date}`(user,hostname,ip,bandwidth,flow,time) values ";
		$val = "";
		foreach($sql_data as $hostname=>$data)
		{
			foreach($data as $nettype=>$fb)
			{
				$bandwidth = round($fb["bandwidth"]);
				$flow = $fb["flow"];

				if(!strlen($val))
					$val = "('$parse_user','$hostname','$nettype',$bandwidth,$flow,'$time')"; 
				else
					$val .= "\n,('$parse_user','$hostname','$nettype',$bandwidth,$flow,'$time')";
			}
		}
		if(strlen($val))
		{
			$sql = $sql_head . $val;
			db_query($dbobj, $sql);
			//echo $sql . "\n\n";
		}
	}

	function create_table($dbobj, $date)
	{
		$sql = "CREATE TABLE IF NOT EXISTS `$date` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`user` char(20) DEFAULT NULL,
			`hostname` char(100) DEFAULT NULL,
			`ip` char(20) DEFAULT NULL,
			`bandwidth` bigint(20) DEFAULT NULL,
			`flow` bigint(20) DEFAULT NULL,
			`time` time DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `user_2` (`user`,`hostname`,`ip`,`time`),
			KEY `hostname` (`hostname`),
			KEY `user` (`user`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		db_query($dbobj, $sql);
	}

	function get_hostname_list($host_file)
	{
		$hostname_list = array();

		$handle = fopen("$host_file", "r");
                if(!$handle)
                {
                        echo "Get file content error!\n";
                        return false;
                }

                while(!feof($handle))
                {
                        $hostname = fgets($handle);
			$hostname = substr($hostname, 0, -1);
			if(!strlen($hostname))
				continue;
			$hostname_list["$hostname"] = 1;
		}
		fclose($handle);

		return $hostname_list;
	}

	function get_node_list($dbobj)
	{
		$node_list = array();

		$sql = "select ip from server_list where type='node';";
		if( ($result = db_query($dbobj, $sql)) )
                {
                	while( ($row = mysql_fetch_array($result)) )
			{
				$ip = $row['ip'];
				$node_list["$ip"] = 1;
			}
			mysql_free_result($result);
		}
		return $node_list;
	}
	
	global $cdnfilelog_ip;
	global $cdnfilelog_user;
	global $cdnfilelog_pass;
	global $cdnfilelog_ps_new_database;

	global $cdninfo_ip;
	global $cdninfo_user;
	global $cdninfo_pass;
	global $cdninfo_file_database;

	$dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_file_database);
	$node_list = get_node_list($dbobj);

	$dbobj = db_gethandle($cdnfilelog_ip, $cdnfilelog_user, $cdnfilelog_pass, $cdnfilelog_ps_new_database);

	$parse_date = $argv[1];
	$parse_hour = $argv[2];
	$parse_group = $argv[3];
	$parse_user = $argv[4];
	$parse_file = $argv[5];

	$ipl = new ipLocation('/opt/cdnfilelog/data/qqwry.dat');
	create_table($dbobj, $parse_date);

	$args = array($parse_date, $parse_hour, $parse_group, $parse_user, $parse_file, $ipl, $node_list);

	parse_each_record($dbobj, $args);
?>

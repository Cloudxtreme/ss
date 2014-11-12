<?php
	require_once('db.php');
	require_once('config.php');

	ini_set("memory_limit", "-1");
	ini_set('date.timezone','Asia/Shanghai');

	function get_all_path($log_home, $day)
	{
		$all_path = array();

		$day_log_home = $log_home . "/" . $day;
		$d = opendir($day_log_home);
		while($folder = readdir($d))
		{
			if(($folder == ".") || ($folder == ".."))
				continue;
			$user = $folder;
			$user_path = $day_log_home . "/" . $user;
			$d_user = opendir($user_path);
			while($u_folder = readdir($d_user))
			{
				if(($u_folder == ".") || ($u_folder == ".."))
					continue;
				$user_host = $u_folder;
				$all_path["$user"]["$user_host"] = $user_path . "/" . $user_host;
			}
		}
		
		return $all_path;
	}

	function handle_area($all_path, $day, $hour)
	{
		global $gen_data;

		$file_count = 2;

                foreach($all_path as $user=>$host_path)
                {
			foreach($host_path as $host=>$path)
			{
				$do_file_num = 0;
                        	do
                        	{
					$do_file_num++;
                                	$area_file = $path . "/area_" . $hour . "_" . $do_file_num . ".txt";
                                	if(!file_exists($area_file))
                                        	continue;
                                	$handle = fopen($area_file, "r");
					echo $area_file . "\n";

                                	while(!feof($handle))
                                	{
                                        	$record = fgets($handle);
                                        	if(!strlen($record))
                                                	continue;
                                        	$arr = explode("\t", $record);
                                        	if(count($arr) < 4)
                                                	continue;

                                        	$area = $arr[0];
                                        	$cnt = $arr[1];
                                        	$send = $arr[2];
                                        	$time = substr($arr[3], 0, -1);

                                        	if(!isset($gen_data["$user"]["$host"]["$area"]))
                                        	{
                                                	$gen_data["$user"]["$host"]["$area"]["cnt"] = 0;
                                                	$gen_data["$user"]["$host"]["$area"]["send"] = 0;
							$gen_data["$user"]["$host"]["$area"]["time"] = 0;
                                                	$gen_data["$user"]["$host"]["$area"]["ip"] = 0;
                                        	}
                                        	$gen_data["$user"]["$host"]["$area"]["cnt"] += $cnt;
                                        	$gen_data["$user"]["$host"]["$area"]["send"] += $send;
						$gen_data["$user"]["$host"]["$area"]["time"] += $time;
						
                                	}
                                	fclose($handle);
                        	}while($do_file_num < $file_count);
			}
                }
	}

	function handle_ip($all_path, $day, $hour)
	{
		global $gen_data;
		global $ip_data;

		$top_max = 100;
		$file_count = 1;

		if($hour >= 0)
			$file_count++;

                foreach($all_path as $user=>$host_path)
                {
			foreach($host_path as $host=>$path)
			{
				$do_file_num = 0;

				$top_cnt = array();
				$top_cnt_num = 0;
				$min_cnt = -1;

				$ip_info = array();

				do
                        	{
					$do_file_num++;
					if($hour < 0)
                                		$ip_file = $path . "/ipaddr.txt";
					else
						$ip_file = $path . "/ipaddr_" . $hour . "_" . $do_file_num . ".txt";
                                	if(!file_exists($ip_file))
                                        	continue;
                                	$handle = fopen($ip_file, "r");
					echo $ip_file . "\n";

                                	while(!feof($handle))
                                	{
                                        	$record = fgets($handle);
						$arr = explode("\t", $record);
						if(count($arr) < 5)
							continue;

						$ip = $arr[0];
						$area = $arr[1];
						$cnt = $arr[2];
						$send = $arr[3];
						$time = substr($arr[4], 0, -1);

						if(!isset($gen_data["$user"]["$host"]["$area"]))
                                        	{
                                                	$gen_data["$user"]["$host"]["$area"]["cnt"] = 0;
                                                	$gen_data["$user"]["$host"]["$area"]["send"] = 0;
							$gen_data["$user"]["$host"]["$area"]["time"] = 0;
                                                	$gen_data["$user"]["$host"]["$area"]["ip"] = 0;
                                        	}
						$gen_data["$user"]["$host"]["$area"]["ip"]++;

						if(!isset($ip_info["$ip"]))
						{
							$ip_info["$ip"]["cnt"] = 0;
							$ip_info["$ip"]["send"] = 0;
							$ip_info["$ip"]["time"] = 0;
						}
						$ip_info["$ip"]["cnt"] += $cnt;
						$ip_info["$ip"]["send"] += $send;
						$ip_info["$ip"]["time"] += $time;
					
						if(true)//$hour < 0)
						{
							$cur_cnt = $ip_info["$ip"]["cnt"];
							$cur_send = $ip_info["$ip"]["send"];
							$cur_time = $ip_info["$ip"]["time"];

                                                	if(isset($top_cnt["$ip"]))
                                                	{
                                                        	$ip_data["$user"]["$host"]["topcnt"]["$ip"] = "${cur_cnt}-${cur_send}-${cur_time}";
                                                        	$top_cnt["$ip"] = $cur_cnt;
                                                        	$min_cnt = min($top_cnt);
                                                	}
                                                	else
                                                	{
                                                        	if($top_cnt_num < $top_max)
                                                        	{
                                                                	$ip_data["$user"]["$host"]["topcnt"]["$ip"] = "${cur_cnt}-${cur_send}-${cur_time}";
                                                                	$top_cnt["$ip"] = $cur_cnt;
                                                                	$top_cnt_num++;
                                                                	$min_cnt = min($top_cnt);
                                                        	}
                                                        	else if($cur_cnt > $min_cnt)
                                                        	{
                                                                	$ip_data["$user"]["$host"]["topcnt"]["$ip"] = "${cur_cnt}-${cur_send}-${cur_time}";
                                                                	$top_cnt["$ip"] = $cur_cnt;
                                                                	$min_cnt_ip = array_search($min_cnt, $top_cnt);
                                                                	unset($ip_data["$host"]["topcnt"]["$min_cnt_ip"]);
                                                                	unset($top_cnt["$min_cnt_ip"]);
                                                                	$min_cnt = min($top_cnt);
                                                        	}
                                                	}
						}
					}
					fclose($handle);
				}while($do_file_num < $file_count);
			}
		}
	}

	function handle_url($all_path, $day, $hour)
	{
		global $gen_data;
                global $url_data;

		$top_max = 100;
		$file_count = 1;

		if($hour >= 0)
			$file_count++;

                foreach($all_path as $user=>$host_path)
                {
			foreach($host_path as $host=>$path)
			{
                        	$top_cnt = array();
				$top_send = array();

                        	$top_cnt_num = 0;
				$top_send_num = 0;
                        	$min_cnt = -1;
				$min_send = -1;

				$do_file_num = 0;

				$url_info = array();

				do
                        	{
					$do_file_num++;
					if($hour < 0)
                             			$url_file = $path . "/url.txt";
					else
						$url_file = $path . "/url_" . $hour . "_" . $do_file_num . ".txt";
                                	if(!file_exists($url_file))
                                        	continue;
                                	$handle = fopen($url_file, "r");
					echo $url_file . "\n";

                                	while(!feof($handle))
                                	{
                                        	$record = fgets($handle);
                                        	$arr = explode("\t", $record);
                                        	if(count($arr) < 5)
                                                	continue;

						$url = $arr[0];
						$area = $arr[1];
						$cnt = $arr[2];
						$send = $arr[3];
						$time = substr($arr[4], 0, -1);

						if(!isset($url_info["$url"]))
						{
							$url_info["$url"]["cnt"] = 0;
							$url_info["$url"]["send"] = 0;
							$url_info["$url"]["time"] = 0;
						}
						$url_info["$url"]["cnt"] += $cnt;
						$url_info["$url"]["send"] += $send;
						$url_info["$url"]["time"] += $time;
					
						if(true)//$hour < 0)
                                        	{
							$cur_cnt = $url_info["$url"]["cnt"];
							$cur_send = $url_info["$url"]["send"];
							$cur_time = $url_info["$url"]["time"];

							if(isset($top_cnt["$url"]))
							{
								$url_data["$user"]["$host"]["topcnt"]["$url"] = "${cur_cnt}-${cur_send}-${cur_time}";
								$top_cnt["$url"] = $cur_cnt;
								$min_cnt = min($top_cnt);
							}
							else
							{
								if($top_cnt_num < $top_max)
								{
									$url_data["$user"]["$host"]["topcnt"]["$url"] = "${cur_cnt}-${cur_send}-${cur_time}";
									$top_cnt["$url"] = $cur_cnt;
									$top_cnt_num++;
									$min_cnt = min($top_cnt);
								}
								else if($cur_cnt > $min_cnt)
								{
									$url_data["$user"]["$host"]["topcnt"]["$url"] = "${cur_cnt}-${cur_send}-${cur_time}";
                                                                	$top_cnt["$url"] = $cur_cnt;
									$min_cnt_url = array_search($min_cnt, $top_cnt);
									unset($url_data["$host"]["topcnt"]["$min_cnt_url"]);
									unset($top_cnt["$min_cnt_url"]);
									$min_cnt = min($top_cnt);
								}
							}

                                                	if(isset($top_send["$url"]))
                                                	{
                                                        	$url_data["$user"]["$host"]["topsend"]["$url"] = "${cur_cnt}-${cur_send}-${cur_time}";
                                                        	$top_send["$url"] = $cur_send;
                                                        	$min_send = min($top_send);
                                                	}
                                                	else
                                                	{
                                                        	if($top_send_num < $top_max)
                                                        	{
                                                                	$url_data["$user"]["$host"]["topsend"]["$url"] = "${cur_cnt}-${cur_send}-${cur_time}";
                                                                	$top_send["$url"] = $cur_send;
                                                                	$top_send_num++;
                                                                	$min_send = min($top_send);
                                                        	}
                                                        	else if($cur_send > $min_send)
                                                        	{
                                                                	$url_data["$user"]["$host"]["topsend"]["$url"] = "${cur_cnt}-${cur_send}-${cur_time}";
                                                                	$top_send["$url"] = $cur_send;
                                                                	$min_send_url = array_search($min_send, $top_send);
                                                                	unset($url_data["$host"]["topsend"]["$min_send_url"]);
                                                                	unset($top_send["$min_send_url"]);
                                                                	$min_send = min($top_send);
                                                        	}
                                                	}
                                        	}
					}
					fclose($handle);
				}while($do_file_num < $file_count);
			}
		}
	}

	function data_to_db($dbobj, $day, $hour)
	{
		global $gen_data;
		global $ip_data;
		global $url_data;
		global $ref_data;

                $month = substr($day, 0, -3);
		//$ip_sql = "insert into `${month}_ip`(`user`,hostname,top_type,ip,cnt,send,r_time,`date`) values ";
		//$url_sql = "insert into `${month}_url`(`user`,hostname,top_type,url,cnt,send,r_time,`date`) values ";
		$gen_sql = "insert into `${day}_gen`(`user`,hostname,nettype,province,city,cnt,send,r_time,ip,`time`) values ";

		if($hour < 0)
		{
			$ip_sql = "insert into `${month}_ip`(`user`,hostname,top_type,ip,cnt,send,r_time,`date`) values ";
                	$url_sql = "insert into `${month}_url`(`user`,hostname,top_type,url,cnt,send,r_time,`date`) values ";
		}
		else
		{
			$ip_sql = "insert into `${day}_ip`(`user`,hostname,top_type,ip,cnt,send,r_time,`time`) values ";
                        $url_sql = "insert into `${day}_url`(`user`,hostname,top_type,url,cnt,send,r_time,`time`) values ";
		}

		if($hour < 0)
		{
			/*
			foreach($ip_data as $user=>$user_data)
			{
				foreach($user_data as $host=>$host_top)
				{
					$val_num = 0;
					$ip_val = "";
					foreach($host_top as $top_type=>$top_data)
					{
						foreach($top_data as $ip=>$val)
						{
							$tmp = explode("-", $val);
							$cnt = $tmp[0];
							$send = $tmp[1];
							$r_time = $tmp[2];
							if(!$val_num)
								$ip_val = "('$user','$host', '$top_type', '$ip', $cnt, $send, $r_time, '$day')";
							else
								$ip_val .= "\n,('$user','$host', '$top_type', '$ip', $cnt, $send, $r_time, '$day')";
							$val_num++;
						}
					}
					$sql = $ip_sql . $ip_val;
					db_query($dbobj, $sql);
				}
			}
			*/

			/*
			foreach($url_data as $user=>$user_data)
			{
				foreach($user_data as $host=>$host_top)
				{
					foreach($host_top as $top_type=>$top_data)
					{
						foreach($top_data as $url=>$val)
						{
							$tmp = explode("-", $val);
							$cnt = $tmp[0];
							$send = $tmp[1];
							$r_time = $tmp[2];
							$url_val = "('$user','$host', '$top_type', '$url', $cnt, $send, $r_time, '$day')";
							$sql = $url_sql . $url_val;
                        				db_query($dbobj, $sql);
						}
					}
				}
			}
			*/

			foreach($gen_data as $user=>$user_data)
			{
				foreach($user_data as $host=>$host_data)
				{
					foreach($host_data as $area=>$data)
					{
						$ip = $data["ip"];
				
						$area_arr = explode("_", $area);
						if(count($area_arr) < 3)
						{
							$nettype = $area_arr[1];
							$province = $area_arr[0];
							$city = "";
						}
						else
						{
							$nettype = $area_arr[2];
							$province = $area_arr[0];
							$city = $area_arr[1];
						}

						$sql = "update `${month}_gen` set ip=$ip where `user`='$user' and hostname='$host' and nettype='$nettype' and province='$province' and city='$city' and `date`='$day';";
						db_query($dbobj, $sql);
					}
				}
			}
		}
		else
		{
			foreach($gen_data as $user=>$user_data)
			{
				foreach($user_data as $host=>$host_data)
				{
					$val = "";
					$val_num = 0;
					foreach($host_data as $area=>$data)
					{
						$area_arr = explode("_", $area);
                                        	if(count($area_arr) < 3)
                                        	{
                                                	$nettype = $area_arr[1];
                                                	$province = $area_arr[0];
                                                	$city = "";
                                        	}
                                        	else
                                        	{
                                                	$nettype = $area_arr[2];
                                                	$province = $area_arr[0];
                                                	$city = $area_arr[1];
                                        	}
					
						$cnt = $data["cnt"];
						$send = $data["send"];
						$r_time = $data["time"];
						$ip = $data["ip"];

						if(!$val_num)
							$val = "('$user','$host','$nettype','$province','$city',$cnt,$send,$r_time,$ip,'${hour}:00:00')";
						else
							$val .= "\n,('$user','$host','$nettype','$province','$city',$cnt,$send,$r_time,$ip,'${hour}:00:00')";
						$val_num++;

						$sql = "insert into `${month}_gen`(`user`,hostname,nettype,province,city,cnt,send,r_time,`date`)
								values('$user','$host','$nettype','$province','$city',$cnt,$send,$r_time,'$day')
								on duplicate key update
								cnt=cnt+$cnt,send=send+$send,r_time=r_time+$r_time;";
						db_query($dbobj, $sql);
					}
					$sql = $gen_sql . $val;
					db_query($dbobj, $sql);
				}
			}
		}

		if($hour < 0)
			$time_str = $day;
		else
			$time_str = "${hour}:00:00";

		foreach($ip_data as $user=>$user_data)
                        {
                                foreach($user_data as $host=>$host_top)
                                {
                                        $val_num = 0;
                                        $ip_val = "";
                                        foreach($host_top as $top_type=>$top_data)
                                        {
                                                foreach($top_data as $ip=>$val)
                                                {
                                                        $tmp = explode("-", $val);
                                                        $cnt = $tmp[0];
                                                        $send = $tmp[1];
                                                        $r_time = $tmp[2];
                                                        if(!$val_num)
                                                                $ip_val = "('$user','$host', '$top_type', '$ip', $cnt, $send, $r_time, '${time_str}')";
                                                        else
                                                                $ip_val .= "\n,('$user','$host', '$top_type', '$ip', $cnt, $send, $r_time, '${time_str}')";
                                                        $val_num++;
                                                }
                                        }
                                        $sql = $ip_sql . $ip_val;
                                        db_query($dbobj, $sql);
                                }
                        }

		foreach($url_data as $user=>$user_data)
                        {
                                foreach($user_data as $host=>$host_top)
                                {
                                        foreach($host_top as $top_type=>$top_data)
                                        {
                                                foreach($top_data as $url=>$val)
                                                {
                                                        $tmp = explode("-", $val);
                                                        $cnt = $tmp[0];
                                                        $send = $tmp[1];
                                                        $r_time = $tmp[2];
                                                        $url_val = "('$user','$host', '$top_type', '$url', $cnt, $send, $r_time, '${time_str}')";
                                                        $sql = $url_sql . $url_val;
                                                        db_query($dbobj, $sql);
                                                }
                                        }
                                }
                        }
	}

	function create_table($dbobj, $month, $day)
	{
		$gen_d_sql = "CREATE TABLE IF NOT EXISTS `${day}_gen` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`user` char(20) DEFAULT NULL,
			`hostname` char(100) DEFAULT NULL,
			`nettype` char(30) DEFAULT NULL,
			`province` char(30) DEFAULT NULL,
			`city` char(30) DEFAULT NULL,
			`cnt` int(11) DEFAULT NULL,
			`send` bigint(20) DEFAULT NULL,
			`r_time` float DEFAULT NULL,
			`ip` int(11) DEFAULT NULL,
			`time` time DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `user` (`user`,`hostname`,`nettype`,`province`,`city`,`time`),
			KEY `user_2` (`user`),
			KEY `hostname_2` (`hostname`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$gen_m_sql = "CREATE TABLE IF NOT EXISTS `${month}_gen` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`user` char(20) DEFAULT NULL,
			`hostname` char(100) DEFAULT NULL,
			`nettype` char(30) DEFAULT NULL,
			`province` char(30) DEFAULT NULL,
			`city` char(30) DEFAULT NULL,
			`cnt` int(11) DEFAULT NULL,
			`send` bigint(20) DEFAULT NULL,
			`r_time` float DEFAULT NULL,
			`ip` int(11) DEFAULT NULL,	
			`date` date DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `user` (`user`,`hostname`,`nettype`,`province`,`city`,`date`),
			KEY `user_2` (`user`),
			KEY `hostname_2` (`hostname`),
			KEY `date_2` (`date`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$ip_day_sql = "CREATE TABLE IF NOT EXISTS `${day}_ip` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user` char(20) DEFAULT NULL,
                        `hostname` char(100) DEFAULT NULL,
                        `top_type` char(20) DEFAULT NULL,
                        `ip` char(20) DEFAULT NULL,
                        `cnt` int DEFAULT NULL,
                        `send` bigint DEFAULT NULL,
                        `r_time` float DEFAULT NULL,
                        `time` time DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `user` (`user`,`hostname`,`ip`,`time`),
                        KEY `use_2` (`user`),
                        KEY `hostname_2` (`hostname`),
                        KEY `time_2` (`time`)
                        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$ip_sql = "CREATE TABLE IF NOT EXISTS `${month}_ip` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`user` char(20) DEFAULT NULL,
			`hostname` char(100) DEFAULT NULL,
			`top_type` char(20) DEFAULT NULL,
			`ip` char(20) DEFAULT NULL,
			`cnt` int DEFAULT NULL,
			`send` bigint DEFAULT NULL,
			`r_time` float DEFAULT NULL,
			`date` date DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `user` (`user`,`hostname`,`ip`,`date`),
			KEY `use_2` (`user`),
			KEY `hostname_2` (`hostname`),
			KEY `date_2` (`date`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$url_day_sql = "CREATE TABLE IF NOT EXISTS `${day}_url` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user` char(20) DEFAULT NULL,
                        `hostname` char(50) DEFAULT NULL,
                        `top_type` char(20) DEFAULT NULL,
                        `url` char(255) DEFAULT NULL,
                        `cnt` int DEFAULT NULL,
                        `send` bigint DEFAULT NULL,
                        `r_time` float DEFAULT NULL,
                        `time` time DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `user_2` (`user`),
                        KEY `hostname_2` (`hostname`),
                        KEY `time_2` (`time`)
                        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$url_sql = "CREATE TABLE IF NOT EXISTS `${month}_url` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`user` char(20) DEFAULT NULL,
			`hostname` char(50) DEFAULT NULL,
			`top_type` char(20) DEFAULT NULL,
			`url` char(255) DEFAULT NULL,
			`cnt` int DEFAULT NULL,
			`send` bigint DEFAULT NULL,
			`r_time` float DEFAULT NULL,
			`date` date DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `user_2` (`user`),
			KEY `hostname_2` (`hostname`),
			KEY `date_2` (`date`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		db_query($dbobj, $gen_d_sql);
		db_query($dbobj, $gen_m_sql);
		db_query($dbobj, $ip_day_sql);
		db_query($dbobj, $ip_sql);
		db_query($dbobj, $url_day_sql);
		db_query($dbobj, $url_sql);
	}

        $day = $argv[1];
        $hour = $argv[2];
        if($hour == "23")
                $day = date("Y-m-d", strtotime("-1 day"));
	$month = substr($day, 0, -3);

	$all_path = get_all_path("/opt/cdn_file_log_analyze", $day);
	$dbobj = db_gethandle("localhost", "root", "rjkj@rjkj", "cdn_file_log_general");

	create_table($dbobj, $month, $day);

	$gen_data = array();
	$ip_data = array();
	$url_data = array();
	handle_area($all_path, $day, $hour);
	handle_ip($all_path, $day, $hour);
	handle_url($all_path, $day, $hour);
	data_to_db($dbobj, $day, $hour);

	if($hour == "01")
	{
		$day = date("Y-m-d", strtotime("-1 day"));
		$all_path = get_all_path("/opt/cdn_file_log_analyze", $day);
		$gen_data = array();
		handle_ip($all_path, $day, -1);
		handle_url($all_path, $day, -1);
		data_to_db($dbobj, $day, -1);
	}
?>

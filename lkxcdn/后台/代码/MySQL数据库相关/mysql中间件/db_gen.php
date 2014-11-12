<?php
	require_once('db.php');

	function get_server($file)
	{
		$server = array();
		
		$xml = simplexml_load_file($file);
                if((!$xml) || (strcasecmp($xml->getName(), "db_gen")))
                {
                        return false;
                }
		foreach($xml->server_info as $info)     
                {                                       
                        $id = $info['id'];              
                        $server["$id"]['host'] = $info->hostname;
			$server["$id"]['ip'] = array();
			foreach($info->ip as $ip)
				$server["$id"]['ip'][] = $ip;
                        $server["$id"]['user'] = $info->user;           
                        $server["$id"]['pass'] = $info->pass;           
                }
		return $server;
	}
	
	function gen_config($file, $args)
	{
		$host = $args['host'];
		$database = $args['db'];
		$table = $args['tb'];

		$general = "";
		$xml = simplexml_load_file($file);
                if((!$xml) || (strcasecmp($xml->getName(), "db_gen")))
                {
                        return "fail";
                }
		$server_id = 0;
                foreach($xml->server_info as $info)
                {
			$id = $info['id'];
			foreach($info->ip as $ip)
                        {
                                if("$host" == "$ip")
                                {
                                        $server_id = $id;
                                        break;
                                }
                        }
			if("$host" == "$info->hostname")
			{
				$server_id = $id;
			}
			if($server_id != 0)
				break;
                }
		$gen_sync = "sync";
		foreach($xml->server_opera as $opera)
		{
			$id = $opera['id'];
			if("$id" == "$server_id")
			{
				foreach($opera->database as $db)
				{
					$db_name = $db['name'];
					if("$db_name" == "$database")
					{
						foreach($db->sync as $sync)
						{
							$srv_id = $sync['server_id'];
							$gen_sync .= ",$srv_id" . ":" . $sync['db'];
						}
						foreach($db->table as $tb)
						{
							$tb_name = $tb['name'];
							if("$tb_name" == "$table")
							{
								foreach($tb->sync as $sync)
								{
									$srv_id = $sync['server_id'];
									$gen_sync .= ",$srv_id" . ":" . $sync['db'];
								}
							}
						}
					}
				}
			}
		}

		$gen_sync .= ";";
		$general .= $gen_sync;
		return $general;
	}

	function get_job($dbobj)
	{
		$jobs = array();
		$yesday = date("Y-m-d",strtotime("-1 day"));
		$today = date("Y-m-d");
		$sql = "select id, gen_date, proxy_log_file, last_proxy_log_id, done_proxy_log_id from gen_log where done_proxy_log_id<last_proxy_log_id or (gen_date='$yesday' and status!='finish') order by gen_date;";

		if( ($result = db_query($dbobj, $sql)) )
		{
			while( ($row = mysql_fetch_array($result)) )
                        {
				$id = $row['id'];
				$gen_date = $row['gen_date'];
                                $proxy_log_file = $row['proxy_log_file'];
				$last_proxy_log_id = $row['last_proxy_log_id'];
                                $done_proxy_log_id = $row['done_proxy_log_id'];

				if($last_proxy_log_id == $done_proxy_log_id)
					continue;
				if($gen_date == $yesday)
					$jobs["$id"]['day'] = "yesterday";
				else
					$jobs["$id"]['day'] = "today";
				$jobs["$id"]['file'] = $proxy_log_file;
				$jobs["$id"]['begin'] = $done_proxy_log_id;
                        }
			mysql_free_result($result);
			foreach($jobs as $id=>$job)
			{
				echo "$id--";
				echo $job['day'] . "--";
				echo $job['file'] . "--" . $job['begin'] . "\n";
			}
			$sql = "update gen_log set done_proxy_log_id=last_proxy_log_id,status='finish' where gen_date='$yesday';";
                        db_query($dbobj, $sql);
		}
		return $jobs;
	}

	function get_table($sql)
	{
		$table = "";
		//$sql_arr = explode(" ", $sql);
		//$sql_type = strtolower($sql_arr[0]);
		$sql = preg_replace("/[\r\n]+/", ' ', $sql);
		$sql_type = strtolower(substr($sql, 0, 6));
		switch($sql_type)
		{
			case "insert":
				$sql_tmp = trim(substr(stristr($sql, "into"), 4));
				$table = trim(substr($sql_tmp, 0, strpos($sql_tmp, ' ')));//trim($sql_arr[2]);
				break;
			case "update":
				$sql_tmp = trim(substr($sql, 6));
				$table = trim(substr($sql_tmp, 0, strpos($sql_tmp, ' ')));//trim($sql_arr[1]);
				break;
			case "delete":
				$sql_tmp = trim(substr(stristr($sql, "from"), 4));
				$table = trim(substr($sql_tmp, 0, strpos($sql_tmp, ' ')));//trim($sql_arr[2]);
				break;
			default:;
		}
		if(strpos($table, "."))
                        $table = substr($table, strpos($table, ".")+1, strlen($table)-strpos($table, ".")-1);
		return $table;
	}
	
	function do_job($dbobj, $jobs)
	{
		$db_job = array();
		$db_ret = array();
		$args = array();
		$db_gen = array();
		$server_sqls = array();

		foreach($jobs as $id=>$job)
		{
			$file = $job['file'];
			$begin = $job['begin'];

			$handle = fopen($file, "r");
			if(!$handle)
				continue;

			for($log_id = 0; (($log_id < $begin) && (!feof($handle)));)
			{
				$line_tmp = fgets($handle);
                                $line = "";
                                while((substr($line_tmp, 0, strlen("efly-db-proxy-done")) != "efly-db-proxy-done") && (!feof($handle)))
                                {
                                        $line .= $line_tmp;
                                        $line_tmp = fgets($handle);
                                }
				//$line = fgets($handle);
				$tmp = explode(" ", $line);
				$log_id = $tmp[0];
			}
			echo "$file:\n";
			while(!feof($handle))
			{
				$line_tmp = fgets($handle);
				$line = "";
				while((substr($line_tmp, 0, strlen("efly-db-proxy-done")) != "efly-db-proxy-done") && (!feof($handle)))
				{
					$line .= $line_tmp;
					$line_tmp = fgets($handle);
				}
				//$line = fgets($handle);
				$info = explode(" ", $line);

				if(count($info) < 3)
					continue;
				
				$log_id = $info[0];
				$host = $info[1];
				if(strpos($host, ":"))
                        		$host = substr($host, 0, strpos($host, ":"));
				$db = $info[2];
				$sql = "";
				for($info_num = 3; $info_num < count($info); $info_num++)
				{
					if($info_num == count($info) - 1)
						$sql .= $info[$info_num];
					else
						$sql .= $info[$info_num] . " ";
				}
				$tb = get_table($sql);
				echo "table:$tb\n";

				if((!$host) || (!strlen($host))
					|| (!$db) || (!strlen($db))
					|| (!$tb) || (!strlen($tb)))
					continue;

				if(!isset($db_gen["$host$db$tb"]))
				{
					$args['host'] = $host;
					$args['db'] = $db;
					$args['tb'] = $tb;
					$db_gen["$host$db$tb"] = gen_config("/opt/db_mgr/config.xml", $args);
				}
				echo $db_gen["$host$db$tb"];

				$gens = explode(";", $db_gen["$host$db$tb"]);
				foreach($gens as $gen)
				{
					$infos = explode(",", $gen);
					if($infos[0] != "sync")
						continue;
					foreach($infos as $info)
					{
						if($info == "sync")
							continue;
						$server = $info;
						$sql_num = 0;
						if(!isset($server_sqls["$server"]))
							$server_sqls["$server"] = 0;
						else
							$sql_num = $server_sqls["$server"] + 1;
						$server_sqls["$server"] = $sql_num;
						$db_job["$server"]["$sql_num"] = $sql;
					}
				}
				

				//echo $host . "--" . $db . "--" . $tb . "--" . $sql . "\n";
			}

			fclose($handle);
			echo "\n";
			$sql = "update gen_log set done_proxy_log_id=$log_id where id=$id;";
			$db_ret[] = $sql;
			//db_query($dbobj, $sql);
		}

		$db_server = get_server("/opt/db_mgr/config.xml");
		foreach($db_job as $server=>$sqls)
		{
			$srv_id = substr($server, 0, strpos($server, ":"));
			$host = $db_server["$srv_id"]['host'];
			$user = $db_server["$srv_id"]['user'];
			$pass = $db_server["$srv_id"]['pass'];
			$db = substr($server, strpos($server, ":")+1, strlen($server)-strpos($server, ":")-1);

			echo $host . "--" . $user . "--" . $pass . "--" . $db . "\n";
			$sync_dbobj = false;
			if(strlen($host))
				$sync_dbobj = db_gethandle($host, $user, $pass, $db);
			if($sync_dbobj == false)
			{
				foreach($db_server["$srv_id"]['ip'] as $ip)
				{
					echo $ip . "--" . $user . "--" . $pass . "--" . $db . "\n";
					$sync_dbobj = db_gethandle($ip, $user, $pass, $db);
					if($sync_dbobj != false)
						break;
				}
			}
			if($sync_dbobj)
			{
				foreach($sqls as $sql_num=>$sql)
				{
					$db_sql_ret = db_query($sync_dbobj, $sql);
					echo "$db_sql_ret:$sql\n";
					//echo $sql . "\n";
				}
			}
		}

		foreach($db_ret as $ret)
		{
			db_query($dbobj, $ret);
		}
	}

	function db_gen()
	{
		$dbobj = db_gethandle("localhost", "root", "rjkj@2009#8", "proxy");
		if($dbobj == false)
		{
			echo "conn db error!\n";
			exit();
		}
		$jobs = get_job($dbobj);
		do_job($dbobj, $jobs);
	}
	
	db_gen();
	return;
?>

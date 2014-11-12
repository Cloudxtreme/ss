<?php
	require_once('db.php');

	ini_set('memory_limit', '-1');

	function parse_each_record($dbobj, $parse_date, $parse_user, $parse_file)
	{
		$send_total = 0;
		$file_download_succ_count = array();
		$file_download_fail_count = array();

		$succ_req = array();
		$fail_req = array();

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

			if(count($file_status) < 3) 
			{
				//echo "$record\n";
				continue;
			}

			$client_ip = $file_status[1];
			$request = explode(" ", $file_status[2]);
			if(count($request) < 2)
			{
				//echo "$record\n";
				continue;
			}
			$filename = $request[1];
			if(strstr($filename, "http://"))
			{
				$filename = substr($filename, strlen("http://"));
        			$filename = substr($filename, strpos($filename, "/"));
			}

			//$http_info = explode("|", $file_status[2]);
			$http_status = $file_status[3];
			$http_send = $file_status[5];
			
			$http_range = $file_status[6];
			$http_range_begin = substr($http_range, strpos($http_range, "=")+1, strpos($http_range, "-")-strpos($http_range, "=")-1);
			$http_range_end = substr($http_range, strpos($http_range, "-")+1, strlen($http_range)-strpos($http_range, "-")-1);

			$host = $file_status[8];
			$host = trim($host);
			if((strlen($host)) && (!filter_var($host, FILTER_VALIDATE_IP)))
				$filename = "/$host$filename";

			//echo "$client_ip $filename $http_status $http_send $http_range_begin $http_range_end\n";continue;

			switch($http_status)
			{
				case 200:
					$send_total += $http_send;
					if(isset($file_download_succ_count["$filename"]))
                                                        $file_download_succ_count["$filename"] += 1;
                                                else
                                                        $file_download_succ_count["$filename"] = 1;
					if(!isset($succ_req["$filename$client_ip"]))
					{
						$succ_req["$filename$client_ip"] = 1;
						//if(isset($file_download_succ_count["$filename"]))
						//	$file_download_succ_count["$filename"] += 1;
						//else
						//	$file_download_succ_count["$filename"] = 1;
					}
					break;
				case 206:
					$send_total += $http_send;
					if($http_send == $http_range_end - $http_range_begin + 1)
					{
						if(isset($file_download_succ_count["$filename"]))
                                                                $file_download_succ_count["$filename"] += 1;
                                                        else
                                                                $file_download_succ_count["$filename"] = 1;
						if(!isset($succ_req["$filename$client_ip"]))
						{
							$succ_req["$filename$client_ip"] = 1;
							//if(isset($file_download_succ_count["$filename"]))
                                                        //	$file_download_succ_count["$filename"] += 1;
                                        		//else
                                                	//	$file_download_succ_count["$filename"] = 1;
						}
					}
					else
					{
						if(isset($file_download_fail_count["$filename"]))
                                                                $file_download_fail_count["$filename"] += 1;
                                                        else
                                                                $file_download_fail_count["$filename"] = 1;
						if(!isset($fail_req["$filename$client_ip"]))
						{
							$fail_req["$filename$client_ip"] = 1;
							//if(isset($file_download_fail_count["$filename"]))
                                                        //	$file_download_fail_count["$filename"] += 1;
                                        		//else
                                        	       	//	$file_download_fail_count["$filename"] = 1;
						}
					}
					break;
				default:;
			}
		}
		fclose($handle);


		//$send_total = $send_total / 1024 / 1024;
		//echo "\nDate:$parse_date\tuser: $parse_user\tsend_total: $send_total mb\n";

		//echo "\nDownload Succ:\n";
		foreach($file_download_succ_count as $filename=>$succ_count)
		{
			//echo "$filename $succ_count\n";
			$sql = "insert into file_download_stats(download_date,user,download_file,download_succ_count) 
				values ('$parse_date','$parse_user','$filename',$succ_count) 
				on duplicate key update download_succ_count=$succ_count;";
			//echo "$filename : $succ_count\n";
			db_query($dbobj, $sql);
		}

		echo "\n\n";
		//echo "\nDownload Fail:\n";
		foreach($file_download_fail_count as $filename=>$fail_count)
                {
                        //echo "$filename $fail_count\n";
			$sql = "insert into file_download_stats(download_date,user,download_file,download_fail_count) 
                                values ('$parse_date','$parse_user','$filename',$fail_count) 
                                on duplicate key update download_fail_count=$fail_count;";
			//echo "$filename : $fail_count\n";
			db_query($dbobj, $sql);
                }
	}
	
	global $cdnfilelog_ip;
	global $cdnfilelog_user;
	global $cdnfilelog_pass;
	global $cdnfilelog_dw_database;

	$dbobj = db_gethandle($cdnfilelog_ip, $cdnfilelog_user, $cdnfilelog_pass, $cdnfilelog_dw_database);

	$parse_date = $argv[1];
	$parse_user = $argv[2];
	$parse_file = $argv[3];
	parse_each_record($dbobj, $parse_date, $parse_user, $parse_file);
?>

<?php
	require_once('db.php');
	require_once('config.php');

	ini_set("memory_limit", "-1");
	ini_set('date.timezone','Asia/Shanghai');

	function get_allhost_path($log_home, $day)
	{
		$all_host_path = array();

		$day_log_home = $log_home . "/" . $day;
		$d = opendir($day_log_home);
		while($host = readdir($d))
		{
			if(($host == ".") || ($host == ".."))
				continue;
			$all_host_path["$host"] = $day_log_home . "/" . $host;
		}
		
		return $all_host_path;
	}

	function handle_area($all_host_path, $day, $hour)
	{
		global $gen_data;

		$file_count = 2;

                foreach($all_host_path as $host=>$host_path)
                {
			$do_file_num = 0;
                        do
                        {
				$do_file_num++;
                                $area_file = $host_path . "/area_" . $hour . "_" . $do_file_num . ".txt";
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
                                        if(count($arr) < 5)
                                                continue;

                                        $area = $arr[0];
                                        $cnt = $arr[1];
                                        $send = $arr[2];
                                        $errcnt = $arr[3];
                                        $errsend = substr($arr[4], 0, -1);

                                        if(!isset($gen_data["$host$area"]))
                                        {
                                                $gen_data["$host$area"] = 1;
                                                $gen_data["$host"]["$area"]["cnt"] = 0;
                                                $gen_data["$host"]["$area"]["send"] = 0;
                                                $gen_data["$host"]["$area"]["errcnt"] = 0;
                                                $gen_data["$host"]["$area"]["errsend"] = 0;
                                                $gen_data["$host"]["$area"]["ip"] = 0;
                                                $gen_data["$host"]["$area"]["pv"] = 0;
                                        }
                                        $gen_data["$host"]["$area"]["cnt"] += $cnt;
                                        $gen_data["$host"]["$area"]["send"] += $send;
                                        $gen_data["$host"]["$area"]["errcnt"] += $errcnt;
                                        $gen_data["$host"]["$area"]["errsend"] += $errsend;
                                }
                                fclose($handle);
                        }while($do_file_num < $file_count);
                }
	}

	function handle_ip($all_host_path, $day, $hour)
	{
		global $gen_data;
		global $ip_data;

		$top_max = 100;
		$file_count = 1;

		if($hour >= 0)
			$file_count++;

                foreach($all_host_path as $host=>$host_path)
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
                                	$ip_file = $host_path . "/ipaddr.txt";
				else
					$ip_file = $host_path . "/ipaddr_" . $hour . "_" . $do_file_num . ".txt";
                                if(!file_exists($ip_file))
                                        continue;
                                $handle = fopen($ip_file, "r");
				echo $ip_file . "\n";

                                while(!feof($handle))
                                {
                                        $record = fgets($handle);
					$arr = explode("\t", $record);
					if(count($arr) < 6)
						continue;

					$ip = $arr[0];
					$area = $arr[1];
					$cnt = $arr[2];
					$send = $arr[3];
					$errcnt = $arr[4];
					$errsend = substr($arr[5], 0, -1);

					if(!isset($gen_data["$host$area"]))
                                        {
                                                $gen_data["$host$area"] = 1;
                                                $gen_data["$host"]["$area"]["cnt"] = 0;
                                                $gen_data["$host"]["$area"]["send"] = 0;
                                                $gen_data["$host"]["$area"]["errcnt"] = 0;
                                                $gen_data["$host"]["$area"]["errsend"] = 0;
                                                $gen_data["$host"]["$area"]["ip"] = 0;
                                                $gen_data["$host"]["$area"]["pv"] = 0;
                                        }
					$gen_data["$host"]["$area"]["ip"]++;

					if(!isset($ip_info["$host$ip"]))
					{
						$ip_info["$host$ip"] = 1;
						$ip_info["$ip"]["cnt"] = 0;
						$ip_info["$ip"]["send"] = 0;
						$ip_info["$ip"]["errcnt"] = 0;
						$ip_info["$ip"]["errsend"] = 0;
					}
					$ip_info["$ip"]["cnt"] += $cnt;
					$ip_info["$ip"]["send"] += $send;
					$ip_info["$ip"]["errcnt"] += $errcnt;
					$ip_info["$ip"]["errsend"] += $errsend;
					
					if(true)//$hour < 0)
					{
						$cur_cnt = $ip_info["$ip"]["cnt"];
						$cur_send = $ip_info["$ip"]["send"];
						$cur_errcnt = $ip_info["$ip"]["errcnt"];
						$cur_errsend = $ip_info["$ip"]["errsend"];

                                                if(isset($top_cnt["$ip"]))
                                                {
                                                        $ip_data["$host"]["topcnt"]["$ip"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
                                                        $top_cnt["$ip"] = $cur_cnt;
                                                        $min_cnt = min($top_cnt);
                                                }
                                                else
                                                {
                                                        if($top_cnt_num < $top_max)
                                                        {
                                                                $ip_data["$host"]["topcnt"]["$ip"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
                                                                $top_cnt["$ip"] = $cur_cnt;
                                                                $top_cnt_num++;
                                                                $min_cnt = min($top_cnt);
                                                        }
                                                        else if($cur_cnt > $min_cnt)
                                                        {
                                                                $ip_data["$host"]["topcnt"]["$ip"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
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

	function handle_url($all_host_path, $day, $hour)
	{
		global $gen_data;
                global $url_data;

		$top_max = 100;
		$file_count = 1;

		if($hour >= 0)
			$file_count++;

                foreach($all_host_path as $host=>$host_path)
                {
                        $top_cnt = array();
			$top_send = array();
			$top_errcnt = array();

                        $top_cnt_num = 0;
			$top_send_num = 0;
			$top_errcnt_num = 0;
                        $min_cnt = -1;
			$min_send = -1;
			$min_errcnt = -1;

			$do_file_num = 0;

			$url_info = array();

			do
                        {
				$do_file_num++;
				if($hour < 0)
                             		$url_file = $host_path . "/url.txt";
				else
					$url_file = $host_path . "/url_" . $hour . "_" . $do_file_num . ".txt";
                                if(!file_exists($url_file))
                                        continue;
                                $handle = fopen($url_file, "r");
				echo $url_file . "\n";

                                while(!feof($handle))
                                {
                                        $record = fgets($handle);
                                        $arr = explode("\t", $record);
                                        if(count($arr) < 6)
                                                continue;

					$url = $arr[0];
					$area = $arr[1];
					$cnt = $arr[2];
					$send = $arr[3];
					$errcnt = $arr[4];
					$errsend = substr($arr[5], 0, -1);

					if(!isset($gen_data["$host$area"]))
                                        {
                                                $gen_data["$host$area"] = 1;
                                                $gen_data["$host"]["$area"]["cnt"] = 0;
                                                $gen_data["$host"]["$area"]["send"] = 0;
                                                $gen_data["$host"]["$area"]["errcnt"] = 0;
                                                $gen_data["$host"]["$area"]["errsend"] = 0;
                                                $gen_data["$host"]["$area"]["ip"] = 0;
                                                $gen_data["$host"]["$area"]["pv"] = 0;
                                        }
					$type1 = substr($url, -5);
                                        $type2 = substr($url, -4);
                                        if(($type1 == ".html") || ($type2 == ".htm"))
                                                $gen_data["$host"]["$area"]["pv"] += $cnt;

					if(!isset($url_info["$host$url"]))
					{
						$url_info["$host$url"] = 1;
						$url_info["$url"]["cnt"] = 0;
						$url_info["$url"]["send"] = 0;
						$url_info["$url"]["errcnt"] = 0;
						$url_info["$url"]["errsend"] = 0;
					}
					$url_info["$url"]["cnt"] += $cnt;
					$url_info["$url"]["send"] += $send;
					$url_info["$url"]["errcnt"] += $errcnt;
					$url_info["$url"]["errsend"] += $errsend;
					
					if($hour < 0)
                                        {
						$cur_cnt = $url_info["$url"]["cnt"];
						$cur_send = $url_info["$url"]["send"];
						$cur_errcnt = $url_info["$url"]["errcnt"];
						$cur_errsend = $url_info["$url"]["errsend"];

						if(isset($top_cnt["$url"]))
						{
							$url_data["$host"]["topcnt"]["$url"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
							$top_cnt["$url"] = $cur_cnt;
							$min_cnt = min($top_cnt);
						}
						else
						{
							if($top_cnt_num < $top_max)
							{
								$url_data["$host"]["topcnt"]["$url"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
								$top_cnt["$url"] = $cur_cnt;
								$top_cnt_num++;
								$min_cnt = min($top_cnt);
							}
							else if($cur_cnt > $min_cnt)
							{
								$url_data["$host"]["topcnt"]["$url"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
                                                                $top_cnt["$url"] = $cur_cnt;
								$min_cnt_url = array_search($min_cnt, $top_cnt);
								unset($url_data["$host"]["topcnt"]["$min_cnt_url"]);
								unset($top_cnt["$min_cnt_url"]);
								$min_cnt = min($top_cnt);
							}
						}

                                                if(isset($top_send["$url"]))
                                                {
                                                        $url_data["$host"]["topsend"]["$url"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
                                                        $top_send["$url"] = $cur_send;
                                                        $min_send = min($top_send);
                                                }
                                                else
                                                {
                                                        if($top_send_num < $top_max)
                                                        {
                                                                $url_data["$host"]["topsend"]["$url"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
                                                                $top_send["$url"] = $cur_send;
                                                                $top_send_num++;
                                                                $min_send = min($top_send);
                                                        }
                                                        else if($cur_send > $min_send)
                                                        {
                                                                $url_data["$host"]["topsend"]["$url"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
                                                                $top_send["$url"] = $cur_send;
                                                                $min_send_url = array_search($min_send, $top_send);
                                                                unset($url_data["$host"]["topsend"]["$min_send_url"]);
                                                                unset($top_send["$min_send_url"]);
                                                                $min_send = min($top_send);
                                                        }
                                                }

                                                if(isset($top_errcnt["$url"]))
                                                {
                                                        $url_data["$host"]["toperrcnt"]["$url"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
                                                        $top_errcnt["$url"] = $cur_errcnt;
                                                        $min_errcnt = min($top_errcnt);
                                                }
                                                else
                                                {
                                                        if($top_errcnt_num < $top_max)
                                                        {
                                                                $url_data["$host"]["toperrcnt"]["$url"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
                                                                $top_errcnt["$url"] = $cur_errcnt;
                                                                $top_errcnt_num++;
                                                                $min_errcnt = min($top_errcnt);
                                                        }
                                                        else if($cur_errcnt > $min_errcnt)
                                                        {
                                                                $url_data["$host"]["toperrcnt"]["$url"] = "${cur_cnt}-${cur_send}-${cur_errcnt}-${cur_errsend}";
                                                                $top_errcnt["$url"] = $cur_errcnt;
                                                                $min_errcnt_url = array_search($min_errcnt, $top_errcnt);
                                                                unset($url_data["$host"]["toperrcnt"]["$min_errcnt_url"]);
                                                                unset($top_errcnt["$min_errcnt_url"]);
                                                                $min_errcnt = min($top_errcnt);
                                                        }
                                                }
                                        }
				}
				fclose($handle);
			}while($do_file_num < $file_count);
		}
	}

	function is_search($web, $ref)
        {
                $ret = false;
                global $search_list;

                foreach($search_list as $info)
                {
                        $name = $info[0];
                        $act = $info[1];
                        $charset = $info[2];

                        if(preg_match("/\b{$name}\b/", $web))
                        {
                                $q_len = strlen($act);
                                if(($act_loc = stripos($ref, $act)) == 0)
                                        continue;
                                $query = substr($ref, $act_loc + $q_len);
                                $q_end = stripos($query, '&');
                                if($q_end)
                                        $key = substr($query, 0, $q_end);
                                else
                                        $key = substr($query, 0);
                                if((!$key) || (!strlen($key)) || (ctype_space($key)))
                                        break;
                                switch($charset)
                                {
                                        case "gbk":
                                                //$key = iconv("GBK", "UTF-8", $key);
                                                break;
                                        default:;
                                }
                                if(substr($key, -1) == "\\")
                                        $key = substr($key, 0, -1);
                                $ret = array();
                                $ret["name"] = $name;
                                $ret["key"] = $key;
                        }
                }
                return $ret;
        }

        function handle_ref($all_host_path, $day, $hour)
        {
                global $ref_data;

                $top_max = 10;
                $top_ref_max = 100;
                $file_count = 1;

		if($hour >= 0)
                        $file_count++;

                foreach($all_host_path as $host=>$host_path)
                {
                        $top_ref = array();
                        $top_search = array();
                        $top_key = array();

                        $top_ref_num = 0;
                        $top_search_num = 0;
                        $top_key_num = 0;
                        $min_ref = -1;
                        $min_search = -1;
                        $min_key = -1;

                        $do_file_num = 0;

                        $ref_info = array();
                        $ref_url_cnt = array();
                        $search_info = array();
                        $key_info = array();

                        do
                        {
                                $do_file_num++;
				if($hour < 0)
                                        $ref_file = $host_path . "/reference.txt";
                                else
                                        $ref_file = $host_path . "/reference_" . $hour . "_" . $do_file_num . ".txt";
                                //$ref_file = $host_path . "/reference.txt";
                                if(!file_exists($ref_file))
                                        continue;
                                $handle = fopen($ref_file, "r");
                                //echo $ref_file . "\n";

                                while(!feof($handle))
                                {
                                        $record = fgets($handle);
                                        $arr = explode("\t", $record);
                                        if(count($arr) < 6)
                                                continue;

                                        $ref = $arr[0];
                                        $url = $arr[1];
                                        $cnt = $arr[2];
                                        $send = $arr[3];
                                        $errcnt = $arr[4];
                                        $errsend = substr($arr[5], 0, -1);

					if((!$ref) || (!strlen($ref)) || (ctype_space($ref)))
                                        	continue;

                                        $ref_arr = explode("/", $ref);
                                        if(substr($ref, 0, 4) == "http")
                                                $ref_web = $ref_arr[2];
                                        else
                                                $ref_web = $ref_arr[0];

					if((!$ref_web) || (!strlen($ref_web)) || (ctype_space($ref_web)))
                                        	continue;

                                        if(!isset($ref_info["$ref_web"]))
                                        {
                                                $ref_info["$ref_web"]["total"]["cnt"] = 0; $ref_info["$ref_web"]["total"]["send"] = 0;
                                                $ref_info["$ref_web"]["total"]["errcnt"] = 0; $ref_info["$ref_web"]["total"]["errsend"] = 0;
                                        }
                                        if(!isset($ref_info["$ref_web"]["$url"]))
                                        {
                                                $ref_info["$ref_web"]["$url"]["cnt"] = 0; $ref_info["$ref_web"]["$url"]["send"] = 0;
                                                $ref_info["$ref_web"]["$url"]["errcnt"] = 0; $ref_info["$ref_web"]["$url"]["errsend"] = 0;
                                        }
                                        if(!isset($ref_url_cnt["$ref_web"]["$url"]))
                                        {
                                                $ref_url_cnt["$ref_web"]["$url"] = 0;
                                        }
                                        $ref_info["$ref_web"]["total"]["cnt"] += $cnt; $ref_info["$ref_web"]["total"]["send"] += $send;
                                        $ref_info["$ref_web"]["total"]["errcnt"] += $errcnt; $ref_info["$ref_web"]["total"]["errsend"] += $errsend;

                                        $ref_url_cnt["$ref_web"]["$url"] += $cnt;
                                        $ref_info["$ref_web"]["$url"]["cnt"] += $cnt; $ref_info["$ref_web"]["$url"]["send"] += $send;
                                        $ref_info["$ref_web"]["$url"]["errcnt"] += $errcnt; $ref_info["$ref_web"]["$url"]["errsend"] += $errsend;

                                        if(($search = is_search($ref_web, $ref)) != false)
                                        {
                                                $name = $search["name"];
                                                $key = $search["key"];

                                                //echo "${name}--${key}--${cnt}--${send}--${errcnt}--${errsend}\n";
                                                if(!isset($search_info["$name"]))
                                                {
                                                        $search_info["$name"]["cnt"] = 0; $search_info["$name"]["send"] = 0;
                                                        $search_info["$name"]["errcnt"] = 0; $search_info["$name"]["errsend"] = 0;
                                                }
                                                if(!isset($key_info["$key"]))
                                                {
                                                        $key_info["$key"]["cnt"] = 0; $key_info["$key"]["send"] = 0;
                                                }
                                                $search_info["$name"]["cnt"] += $cnt; $search_info["$name"]["send"] += $send;
                                                $search_info["$name"]["errcnt"] += $errcnt; $search_info["$name"]["errsend"] += $errsend;
                                                $key_info["$key"]["cnt"] += $cnt; $key_info["$key"]["send"] += $send;
                                                $key_info["$key"]["cnt"] += $errcnt; $key_info["$key"]["send"] += $errsend;

                                                $cur_search_cnt = $search_info["$name"]["cnt"]; $cur_search_send = $search_info["$name"]["send"];
                                                $cur_search_errcnt = $search_info["$name"]["errcnt"]; $cur_search_errsend = $search_info["$name"]["errsend"];
                                                $cur_key_cnt = $key_info["$key"]["cnt"];
                                                $cur_key_send = $key_info["$key"]["send"];

                                                if(isset($top_search["$name"]))
                                                {
                                                        $ref_data["$host"]["topsearch"]["$name"] = "${cur_search_cnt}-${cur_search_send}-${cur_search_errcnt}-${cur_search_errsend}";
                                                        $top_search["$name"] = $cur_search_cnt;
                                                        $min_search = min($top_search);
                                                }
                                                else
                                                {
                                                        if($top_search_num < $top_max)
                                                        {
                                                                $ref_data["$host"]["topsearch"]["$name"] = "${cur_search_cnt}-${cur_search_send}-${cur_search_errcnt}-${cur_search_errsend}";
                                                                $top_search["$name"] = $cur_search_cnt;
                                                                $top_search_num++;
                                                                $min_search = min($top_search);
                                                        }
                                                        else if($cur_search_cnt > $min_search)
                                                        {
                                                                $ref_data["$host"]["topsearch"]["$name"] = "${cur_search_cnt}-${cur_search_send}-${cur_search_errcnt}-${cur_search_errsend}";
                                                                $top_search["$name"] = $cur_search_cnt;
                                                                $min_search_name = array_search($min_search, $top_search);
                                                                unset($ref_data["$host"]["topsearch"]["$min_search_name"]);
                                                                unset($top_search["$min_search_name"]);
                                                                $min_search = min($top_search);
                                                        }
                                                }

                                                if(isset($top_key["$key"]))
                                                {
                                                        $ref_data["$host"]["topkey"]["$key"] = "${cur_key_cnt}-${cur_key_send}-0-0";
                                                        $top_key["$key"] = $cur_key_cnt;
                                                        $min_key = min($top_key);
                                                }
                                                else
                                                {
                                                        if($top_key_num < $top_max)
                                                        {
                                                                $ref_data["$host"]["topkey"]["$key"] = "${cur_key_cnt}-${cur_key_send}-0-0";
                                                                $top_key["$key"] = $cur_key_cnt;
                                                                $top_key_num++;
                                                                $min_key = min($top_key);
                                                        }
                                                        else if($cur_key_cnt > $min_key)
                                                        {
                                                                $ref_data["$host"]["topkey"]["$key"] = "${cur_key_cnt}-${cur_key_send}-0-0";
                                                                $top_key["$key"] = $cur_key_cnt;
                                                                $min_key_val = array_search($min_key, $top_key);
                                                                unset($ref_data["$host"]["topkey"]["$min_key_val"]);
                                                                unset($top_key["$min_key_val"]);
                                                                $min_key = min($top_key);
                                                        }
                                                }
                                        }

                                        $cur_ref_cnt = $ref_info["$ref_web"]["total"]["cnt"]; $cur_ref_send = $ref_info["$ref_web"]["total"]["send"];
                                        $cur_ref_errcnt = $ref_info["$ref_web"]["total"]["errcnt"]; $cur_ref_errsend = $ref_info["$ref_web"]["total"]["errsend"];

                                        if(isset($top_ref["$ref_web"]))
                                        {
                                                $ref_data["$host"]["topref"]["$ref_web"] = "${cur_ref_cnt}-${cur_ref_send}-${cur_ref_errcnt}-${cur_ref_errsend}";
                                                $top_ref["$ref_web"] = $cur_ref_cnt;
                                                $min_ref = min($top_ref);
                                        }
                                        else
                                        {
                                                if($top_ref_num < $top_ref_max)
                                                {
                                                        $ref_data["$host"]["topref"]["$ref_web"] = "${cur_ref_cnt}-${cur_ref_send}-${cur_ref_errcnt}-${cur_ref_errsend}";
                                                        $top_ref["$ref_web"] = $cur_ref_cnt;
                                                        $top_ref_num++;
                                                        $min_ref = min($top_ref);
                                                }
                                                else if($cur_ref_cnt > $min_ref)
                                                {
                                                        $ref_data["$host"]["topref"]["$ref_web"] = "${cur_ref_cnt}-${cur_ref_send}-${cur_ref_errcnt}-${cur_ref_errsend}";
                                                        $top_ref["$ref_web"] = $cur_ref_cnt;
                                                        $min_ref_web = array_search($min_ref, $top_ref);
                                                        unset($ref_data["$host"]["topref"]["$min_ref_web"]);
                                                        unset($top_ref["$min_ref_web"]);
                                                        $min_ref = min($top_ref);
                                                }
                                        }
                                }
                                fclose($handle);
                        }while($do_file_num < $file_count);

                        //echo "finish ref,search,key and begin ref url!\n";
                        foreach($top_ref as $ref_web=>$val)
                        {
                                for($i = 0; $i < $top_max; $i++)
                                {
                                        if(count($ref_url_cnt["$ref_web"]) == 0)
                                                break;
                                        $max_cnt = max($ref_url_cnt["$ref_web"]);
                                        $url = array_search($max_cnt, $ref_url_cnt["$ref_web"]);

                                        $cnt = $ref_info["$ref_web"]["$url"]["cnt"]; $send = $ref_info["$ref_web"]["$url"]["send"];
                                        $errcnt = $ref_info["$ref_web"]["$url"]["errcnt"]; $errsend = $ref_info["$ref_web"]["$url"]["errsend"];

                                        $ref_data["$host"]["toprefurl"]["$ref_web"]["$url"] = "${cnt}-${send}-${errcnt}-${errsend}";
                                        unset($ref_url_cnt["$ref_web"]["$url"]);
                                }
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
		//$ip_sql = "insert into `${month}_ip`(hostname,top_type,ip,cnt,send,errcnt,errsend,`date`) values ";
		$url_sql = "insert into `${month}_url`(hostname,top_type,url,cnt,send,errcnt,errsend,`date`) values ";
		//$ref_sql = "insert into `${month}_ref`(hostname,top_type,val,cnt,send,errcnt,errsend,`date`) values ";
                //$ref_target_sql = "insert into `${month}_ref_target`(hostname,ref,top_type,val,cnt,send,errcnt,errsend,`date`) values ";
		$gen_sql = "insert into `${day}_gen`(hostname,nettype,province,city,cnt,send,errcnt,errsend,ip,pv,`time`) values ";

		if($hour < 0)
		{
			$ip_sql = "insert into `${month}_ip`(hostname,top_type,ip,cnt,send,errcnt,errsend,`date`) values ";
                	$ref_sql = "insert into `${month}_ref`(hostname,top_type,val,cnt,send,errcnt,errsend,`date`) values ";
                	$ref_target_sql = "insert into `${month}_ref_target`(hostname,ref,top_type,val,cnt,send,errcnt,errsend,`date`) values ";
		}
		else
		{
			$ip_sql = "insert into `${day}_ip`(hostname,top_type,ip,cnt,send,errcnt,errsend,`time`) values ";
                        $ref_sql = "insert into `${day}_ref`(hostname,top_type,val,cnt,send,errcnt,errsend,`time`) values ";
                        $ref_target_sql = "insert into `${day}_ref_target`(hostname,ref,top_type,val,cnt,send,errcnt,errsend,`time`) values ";
		}

		if($hour < 0)
		{
			/*
			foreach($ip_data as $host=>$host_top)
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
						$errcnt = $tmp[2];
						$errsend = $tmp[3];
						if(!$val_num)
							$ip_val = "('$host', '$top_type', '$ip', $cnt, $send, $errcnt, $errsend, '$day')";
						else
							$ip_val .= "\n,('$host', '$top_type', '$ip', $cnt, $send, $errcnt, $errsend, '$day')";
						$val_num++;
					}
				}
				$sql = $ip_sql . $ip_val;
				db_query($dbobj, $sql);
			}
			*/

			foreach($url_data as $host=>$host_top)
			{
				foreach($host_top as $top_type=>$top_data)
				{
					foreach($top_data as $url=>$val)
					{
						$tmp = explode("-", $val);
						$cnt = $tmp[0];
						$send = $tmp[1];
						$errcnt = $tmp[2];
						$errsend = $tmp[3];
						$url_val = "('$host', '$top_type', '$url', $cnt, $send, $errcnt, $errsend, '$day')";
						$sql = $url_sql . $url_val;
                        			db_query($dbobj, $sql);
					}
				}
			}
			
			/*
			foreach($ref_data as $host=>$host_top)
                        {
                                foreach($host_top as $top_type=>$top_data)
                                {
                                        if($top_type == "toprefurl")
                                        {
                                                foreach($top_data as $ref=>$data)
                                                {
                                                        foreach($data as $url=>$val)
                                                        {
                                                                $tmp = explode("-", $val);
                                                                $cnt = $tmp[0];
                                                                $send = $tmp[1];
                                                                $errcnt = $tmp[2];
                                                                $errsend = $tmp[3];
                                                                $ref_target_val = "('$host','$ref','$top_type','$url',$cnt,$send,$errcnt,$errsend,'$day')";
                                                                $sql = $ref_target_sql . $ref_target_val;
                                                                db_query($dbobj, $sql);
                                                        }
                                                }
                                        }
                                        else
                                        {
                                                foreach($top_data as $key=>$val)
                                                {
                                                        $tmp = explode("-", $val);
                                                        $cnt = $tmp[0];
                                                        $send = $tmp[1];
                                                        $errcnt = $tmp[2];
                                                        $errsend = $tmp[3];
                                                        $ref_val = "('$host','$top_type','$key',$cnt,$send,$errcnt,$errsend,'$day')";
                                                        $sql = $ref_sql . $ref_val;
                                                        db_query($dbobj, $sql);
                                                }
                                        }
                                }
                        }
			*/
		
			foreach($gen_data as $host=>$host_data)
			{
				if($host_data == 1)
					continue;
				foreach($host_data as $area=>$data)
				{
					$ip = $data["ip"];
					$pv = $data["pv"];
				
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

					$sql = "update `${month}_gen` set ip=$ip,pv=$pv where hostname='$host' and nettype='$nettype' and province='$province' and city='$city' and `date`='$day';";
					db_query($dbobj, $sql);
				}
			}
		}
		else
		{
			foreach($gen_data as $host=>$host_data)
			{
				$val = "";
				$val_num = 0;
				if($host_data == 1)
					continue;
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
					$errcnt = $data["errcnt"];
					$errsend = $data["errsend"];
					$ip = $data["ip"];
					$pv = $data["pv"];

					if(!$val_num)
						$val = "('$host','$nettype','$province','$city',$cnt,$send,$errcnt,$errsend,$ip,$pv,'${hour}:00:00')";
					else
						$val .= "\n,('$host','$nettype','$province','$city',$cnt,$send,$errcnt,$errsend,$ip,$pv,'${hour}:00:00')";
					$val_num++;

					$sql = "insert into `${month}_gen`(hostname,nettype,province,city,cnt,send,errcnt,errsend,`date`)
							values('$host','$nettype','$province','$city',$cnt,$send,$errcnt,$errsend,'$day')
							on duplicate key update
							cnt=cnt+$cnt,send=send+$send,errcnt=errcnt+$errcnt,errsend=errsend+$errsend;";
					db_query($dbobj, $sql);
				}
				$sql = $gen_sql . $val;
				db_query($dbobj, $sql);
			}
		}

		if($hour < 0)
			$time_str = $day;
		else
			$time_str = "${hour}:00:00";
		foreach($ip_data as $host=>$host_top)
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
                                                $errcnt = $tmp[2];
                                                $errsend = $tmp[3];
                                                if(!$val_num)
                                                        $ip_val = "('$host', '$top_type', '$ip', $cnt, $send, $errcnt, $errsend, '${time_str}')";
                                                else
                                                        $ip_val .= "\n,('$host', '$top_type', '$ip', $cnt, $send, $errcnt, $errsend, '${time_str}')";
                                                $val_num++;
                                        }
                                }
                                $sql = $ip_sql . $ip_val;
                                db_query($dbobj, $sql);
                        }

		foreach($ref_data as $host=>$host_top)
                        {
                                foreach($host_top as $top_type=>$top_data)
                                {
                                        if($top_type == "toprefurl")
                                        {
                                                foreach($top_data as $ref=>$data)
                                                {
                                                        foreach($data as $url=>$val)
                                                        {
                                                                $tmp = explode("-", $val);
                                                                $cnt = $tmp[0];
                                                                $send = $tmp[1];
                                                                $errcnt = $tmp[2];
                                                                $errsend = $tmp[3];
                                                                $ref_target_val = "('$host','$ref','$top_type','$url',$cnt,$send,$errcnt,$errsend,'${time_str}')";
                                                                $sql = $ref_target_sql . $ref_target_val;
                                                                db_query($dbobj, $sql);
                                                        }
                                                }
                                        }
                                        else
                                        {
                                                foreach($top_data as $key=>$val)
                                                {
                                                        $tmp = explode("-", $val);
                                                        $cnt = $tmp[0];
                                                        $send = $tmp[1];
                                                        $errcnt = $tmp[2];
                                                        $errsend = $tmp[3];
                                                        $ref_val = "('$host','$top_type','$key',$cnt,$send,$errcnt,$errsend,'${time_str}')";
                                                        $sql = $ref_sql . $ref_val;
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
			`hostname` char(100) DEFAULT NULL,
			`nettype` char(30) DEFAULT NULL,
			`province` char(30) DEFAULT NULL,
			`city` char(30) DEFAULT NULL,
			`cnt` int(11) DEFAULT NULL,
			`send` bigint(20) DEFAULT NULL,
			`errcnt` int(11) DEFAULT NULL,
			`errsend` bigint(20) DEFAULT NULL,
			`ip` int(11) DEFAULT NULL,
			`pv` int(11) DEFAULT NULL,
			`time` time DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `hostname` (`hostname`,`nettype`,`province`,`city`,`time`),
			KEY `hostname_2` (`hostname`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$gen_m_sql = "CREATE TABLE IF NOT EXISTS `${month}_gen` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`hostname` char(100) DEFAULT NULL,
			`nettype` char(30) DEFAULT NULL,
			`province` char(30) DEFAULT NULL,
			`city` char(30) DEFAULT NULL,
			`cnt` int(11) DEFAULT NULL,
			`send` bigint(20) DEFAULT NULL,
			`errcnt` int(11) DEFAULT NULL,
			`errsend` bigint(20) DEFAULT NULL,
			`ip` int(11) DEFAULT 0,	
			`pv` int(11) DEFAULT 0,
			`date` date DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `hostname` (`hostname`,`nettype`,`province`,`city`,`date`),
			KEY `hostname_2` (`hostname`),
			KEY `date_2` (`date`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$ip_day_sql = "CREATE TABLE IF NOT EXISTS `${day}_ip` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`hostname` char(100) DEFAULT NULL,
			`top_type` char(20) DEFAULT NULL,
			`ip` char(20) DEFAULT NULL,
			`cnt` int DEFAULT NULL,
			`send` bigint DEFAULT NULL,
			`errcnt` int DEFAULT NULL,
			`errsend` bigint DEFAULT NULL,
			`time` time DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `hostname` (`hostname`,`ip`,`time`),
			KEY `hostname_2` (`hostname`),
			KEY `time_2` (`time`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$ip_sql = "CREATE TABLE IF NOT EXISTS `${month}_ip` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`hostname` char(100) DEFAULT NULL,
			`top_type` char(20) DEFAULT NULL,
			`ip` char(20) DEFAULT NULL,
			`cnt` int DEFAULT NULL,
			`send` bigint DEFAULT NULL,
			`errcnt` int DEFAULT NULL,
			`errsend` bigint DEFAULT NULL,
			`date` date DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `hostname` (`hostname`,`ip`,`date`),
			KEY `hostname_2` (`hostname`),
			KEY `date_2` (`date`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$url_sql = "CREATE TABLE IF NOT EXISTS `${month}_url` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`hostname` char(50) DEFAULT NULL,
			`top_type` char(20) DEFAULT NULL,
			`url` char(255) DEFAULT NULL,
			`cnt` int DEFAULT NULL,
			`send` bigint DEFAULT NULL,
			`errcnt` int DEFAULT NULL,
			`errsend` bigint DEFAULT NULL,
			`date` date DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `hostname_2` (`hostname`,`top_type`,`url`,`date`),
			KEY `hostname` (`hostname`),
			KEY `date_2` (`date`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$ref_day_sql = "CREATE TABLE IF NOT EXISTS `${day}_ref` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `hostname` char(50) DEFAULT NULL,
                        `top_type` char(20) DEFAULT NULL,
                        `val` char(255) DEFAULT NULL,
                        `cnt` int(11) DEFAULT NULL,
                        `send` bigint(20) DEFAULT NULL,
                        `errcnt` int(11) DEFAULT NULL,
                        `errsend` bigint(20) DEFAULT NULL,
                        `time` time DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `hostname_2` (`hostname`,`top_type`,`val`,`time`),
                        KEY `hostname` (`hostname`),
                        KEY `time_2` (`time`)
                        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$ref_sql = "CREATE TABLE IF NOT EXISTS `${month}_ref` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`hostname` char(50) DEFAULT NULL,
			`top_type` char(20) DEFAULT NULL,
			`val` char(255) DEFAULT NULL,
			`cnt` int(11) DEFAULT NULL,
			`send` bigint(20) DEFAULT NULL,
			`errcnt` int(11) DEFAULT NULL,
			`errsend` bigint(20) DEFAULT NULL,
			`date` date DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `hostname_2` (`hostname`,`top_type`,`val`,`date`),
			KEY `hostname` (`hostname`),
			KEY `date_2` (`date`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$ref_target_day_sql = "CREATE TABLE IF NOT EXISTS `${day}_ref_target` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `hostname` char(50) DEFAULT NULL,
                        `ref` char(255) DEFAULT NULL,
                        `top_type` char(20) DEFAULT NULL,
                        `val` char(255) DEFAULT NULL,
                        `cnt` int(11) DEFAULT NULL,
                        `send` bigint(20) DEFAULT NULL,
                        `errcnt` int(11) DEFAULT NULL,
                        `errsend` bigint(20) DEFAULT NULL,
                        `time` time DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `ref` (`ref`),
                        KEY `time_2` (`time`)
                        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$ref_target_sql = "CREATE TABLE IF NOT EXISTS `${month}_ref_target` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`hostname` char(50) DEFAULT NULL,
			`ref` char(255) DEFAULT NULL,
			`top_type` char(20) DEFAULT NULL,
			`val` char(255) DEFAULT NULL,
			`cnt` int(11) DEFAULT NULL,
			`send` bigint(20) DEFAULT NULL,
			`errcnt` int(11) DEFAULT NULL,
			`errsend` bigint(20) DEFAULT NULL,
			`date` date DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `ref` (`ref`),
			KEY `date_2` (`date`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		db_query($dbobj, $gen_d_sql);
		db_query($dbobj, $gen_m_sql);
		db_query($dbobj, $ip_day_sql);
		db_query($dbobj, $ip_sql);
		db_query($dbobj, $url_sql);
		db_query($dbobj, $ref_day_sql);
		db_query($dbobj, $ref_sql);
		db_query($dbobj, $ref_target_day_sql);
		db_query($dbobj, $ref_target_sql);
	}

        $day = date("Y-m-d");
        $hour = date("H", strtotime("-1 hour"));
        if($hour == "23")
                $day = date("Y-m-d", strtotime("-1 day"));
	$month = substr($day, 0, -3);

	$all_host_path = get_allhost_path("/opt/cdn_web_log_analyze", $day);
	$dbobj = db_gethandle("localhost", "root", "rjkj@rjkj", "cdn_web_log_general");

	create_table($dbobj, $month, $day);

	$gen_data = array();
        $ip_data = array();
        $url_data = array();
        $ref_data = array();

	handle_area($all_host_path, $day, $hour);
	handle_ip($all_host_path, $day, $hour);
	handle_url($all_host_path, $day, $hour);
	handle_ref($all_host_path, $day, $hour);
	data_to_db($dbobj, $day, $hour);

	if($hour == "01")
	{
		$day = date("Y-m-d", strtotime("-1 day"));
		$all_host_path = get_allhost_path("/opt/cdn_web_log_analyze", $day);
		
		$gen_data = array();
        	$ip_data = array();
        	$url_data = array();
        	$ref_data = array();

		handle_ip($all_host_path, $day, -1);
		handle_url($all_host_path, $day, -1);
		handle_ref($all_host_path, $day, -1);
		data_to_db($dbobj, $day, -1);
	}
?>

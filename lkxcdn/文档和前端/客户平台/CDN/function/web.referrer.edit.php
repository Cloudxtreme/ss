<?php
//来源统计
	require_once('./web.com.fun.php');
	require_once('./log.fun.php');
	ini_set("soap.wsdl_cache_enabled", "0"); 
	error_reporting(E_ALL^E_NOTICE);

	function get_hostname_list($channels)
	{
		$hostname_list = "";
		foreach($channels as $hostname)
                {
                        if(!strlen($hostname_list))
                                $hostname_list = "'$hostname'";
                        else
                                $hostname_list .= ",'$hostname'";
                }
		return $hostname_list;
	}
	
	function get_top_data($channels, $query_time, $zone, $isp)
	{
		$top_data = array();
		$hostname_list = "";
		$query_begin = $query_time[0];
		$query_end = $query_time[1];
		$sql = array();
		$top_type = array("topref", "topsearch", "topkey");
		
		$soap = new SoapClient("http://116.28.65.253:8080/cdn/services/url?wsdl");
		
		$argumensts = array();
		$type_arr = array("ref","key","search");
		foreach($type_arr as $type)
		{
			$argumensts[0] = $channels;
			$argumensts[1] = $query_begin;
			$argumensts[2] = $query_end;
			$argumensts[3] = $type;
		
			$arrResult = $soap->__Call("get_gen_ref",$argumensts);
		
		//流量类型比例
			if(count($arrResult) != 0)
			{
				foreach($arrResult as $values)
				{
					$data = explode("\t",$values);
					$channel = $data[0];
					$cnt = $data[1];
					$flow = $data[2];

					$top_data["top$type"]["$channel"]["cnt"] = $cnt;
					$top_data["top$type"]["$channel"]["send"] = $flow;
				}	
			}
			
			unset($argumensts);
			unset($arrResult);
		}

		//print_r($top_data);
		//exit;
		
		return $top_data;
	}

	
	function get_para($name, $format)
	{
		if((!isset($_POST["$name"])) || (!strlen($_POST["$name"])))
			$val = false;
		else
			$val = $_POST["$name"];
		if(($val) && ($format == "json"))
                        $val = json_decode($val, true);
                return $val;
        }

        function print_init($client)
        {
		print_r(get_client_hostname_list($client)); print("***");
		print_r(get_cdn_zone_list()); print("***");
		print_r(get_cdn_isp_list());
        }

        function print_ret($top_data)
        {
		$ref_web = array();
		$search = array();
		$key = array();
		foreach($top_data as $type=>$data)
		{
			foreach($data as $val=>$cnt_send)
			{
				$cnt = $cnt_send["cnt"];
				$send = $cnt_send["send"] / 1024 / 1024;
				$send = sprintf("%.2f", $send);
				switch($type)
				{
					case "topref":
						$ref_web[] = array('web' => $val, 'send' => $send, 'cnt' => $cnt);
						break;
					case "topsearch":
						$search[] = array('search' => $val, 'send' => $send, 'cnt' => $cnt);
						break;
					case "topkey":
						$key[] = array('key' => $val, 'send' => $send, 'cnt' => $cnt);
						break;
					default:;
				}
			}
		}
		if(count($ref_web) == 0)
		{
			$ref_web[] = array('web' => '#', 'send' => '-', 'cnt' => '-');
		}
		print_r(json_encode($ref_web));
		print("***");
		if(count($search) == 0)
		{
			$search[] = array('search' => '#', 'send' => '-', 'cnt' => '-');
		}
		print_r(json_encode($search));
		print("***");
		if(count($key) == 0)
		{
			$key[] = array('key' => '#', 'send' => '-', 'cnt' => '-');
		}
		print_r(json_encode($key));

	}


        if(($type = get_para("get_type", "normal")) == false)
			exit;
		if(($client = get_para("user", "normal")) == false)
			exit;


	switch($type)
	{
		case "_init":
			print_init($client);
			exit();
			break;
		case "_referrer":
			$channels = get_para("channel", "json");
			$channels = analyse_hostname_list($channels);
			$query_time = get_para("time", "json");
			if( count($channels) <= 0 ) { exit; }
			if( count($query_time) != 2 ) { exit; }
			$zone = array("中国大陆");
			$isp = array("电信", "网通", "移动");
			syslog_user_action($client,$_SERVER['SCRIPT_NAME'],$channels,$query_time[0],$query_time[1]);
			$top_data = get_top_data($channels, $query_time, $zone, $isp);
			print_ret($top_data);
			break;
		default:
			exit;
			break;
	}

?>

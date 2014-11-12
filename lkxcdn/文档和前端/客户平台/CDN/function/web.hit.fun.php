<?php
/*网页加速 - 访问概况*/

	/*一天对应秒数*/
	ini_set("soap.wsdl_cache_enabled", "0"); 
	define('ONE_DAY_SEC',86400);
	require_once('./log.fun.php');
	require_once('./web.com.fun.php');
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
	
	function get_bil_data($channels, $query_time, $zone, $isp, $type)
	{
		$bil_data = array();
		$total_cnt = 0;
		$total_hit_cnt = 0;
		$hostname_list = "";
		$query_begin = $query_time[0];
		$query_end = $query_time[1];
		
		$soap = new SoapClient("http://116.28.65.253:8080/cdn/services/url?wsdl");
		$isps = array('电信', '网通', '移动');
		
		$argumensts = array();
		$argumensts[0] = $channels;
		$argumensts[1] = $isps;
		$argumensts[2] = $query_begin;
		$argumensts[3] = $query_end;
		$argumensts[4] = "cnt";
	
		$arrResult = $soap->__Call("get_gen_hit",$argumensts);

		//流量类型比例
		if(count($arrResult) != 0)
		{
			foreach($arrResult as $values)
			{
				$data = explode("\t",$values);
				$isp = $data[0];
				$cnt = $data[1];
				$hit_cnt = $data[2];
				$total_cnt += $cnt;
				$total_hit_cnt += $hit_cnt;
				
			}
		}

		$bil_data[] = array('type' => "回源请求数", 'value' => ($total_cnt-$total_hit_cnt));
		$bil_data[] = array('type' => "边缘节点请求数", 'value' => ($total_hit_cnt));



		if(count($bil_data) == 0)
		{
			$bil_data[] = array('type' => "Null", 'value' => 100);
		}

		return $bil_data;
	}

	function get_map_data($channels, $query_time, $zone, $isp, $type)
	{
		$map_data = array();
		$query_begin = $query_time[0];
		$query_end = $query_time[1];


		$req = "";
		if($type == "_hit")
			$req = "cnt";
		else
			$req = "pv";

		$soap = new SoapClient("http://116.28.65.253:8080/cdn/services/url?wsdl");
		if($query_begin != $query_end)
		{
	
			$argumensts = array();
			$argumensts[0] = $channels;
			$argumensts[1] = $query_begin;
			$argumensts[2] = $query_end;
			$argumensts[3] = $req;
			//print_r($argumensts);
			$arrResult = $soap->__Call("get_gen_date",$argumensts);

		}
		else
		{
			$argumensts = array();
			$argumensts[0] = $channels;
			$argumensts[1] = $query_begin;
			$argumensts[2] = $req;
			
			$arrResult = $soap->__Call("get_gen_time",$argumensts);
		}

		
		//print_r($arrResult);
		if($query_begin == $query_end)
		{
			for($i = 0 ; $i < 24; $i++)
			{
				$hour = sprintf("%02d", $i);
				$map_data[$hour] = 0;
			}
		}
	
		else
		{
			for( $bday = @strtotime($query_begin); $bday <= @strtotime($query_end); ) 
			{
				$day = @date("Y-m-d", $bday);
				$bday += ONE_DAY_SEC;
				$map_data[$day] = 0;

			}
		}
		if(count($arrResult) != 0)
		{
			foreach($arrResult as $key => $values)
			{
				$values = explode("\t",$values);
				$time = $values[0];
			//if (strlen($url) == 0){continue;}
				$value = $values[1];
				if($query_begin == $query_end)
					$time = substr($time, 0, 2);
				$map_data["$time"] = $value;
			}
		}
		return $map_data;
    }

	function get_top_data($channels, $query_time, $zone, $isp, $type)
	{
		$top_data = array();
		$query_begin = $query_time[0];
		$query_end = $query_time[1];


		$req = "";
		if($type == "_hit")
			$req = "cnt";
		else
			$req = "pv";
			
		$soap = new SoapClient("http://116.28.65.253:8080/cdn/services/url?wsdl");

		$argumensts = array();
		$argumensts[0] = $channels;
		$argumensts[1] = $query_begin;
		$argumensts[2] = $query_end;
		$argumensts[3] = $req;
			
		$arrResult = $soap->__Call("get_gen_area",$argumensts);
		//print_r($arrResult);
					
		if(count($arrResult) != 0)
		{
			foreach($arrResult as $key => $values)
			{
				$values = explode("\t",$values);
				$area = $values[0];
			//if (strlen($url) == 0){continue;}
				$cnt = $values[1];
				$area = explode("_",$area);
				$province = $area[1];
				
				if ($province == "中国")
					continue;

				$top_data["top_province"][$province] += $cnt;
				$top_data["top_province"]["total"] += $cnt;
				
			}
		}
			
		return $top_data;

		/*
                if(1)
                {
                        $begin_month = substr($query_begin, 0, -3);
                        $end_month = substr($query_end, 0, -3);

                        $sql_province = "select province,sum($req) from `${begin_month}_gen` 
                                where hostname in (${hostname_list}) and `date` between '$query_begin' and '$query_end'
                                group by province";

						$sql_country = "select sum($req) from `${begin_month}_gen`
						where hostname in (${hostname_list}) and `date` between '$query_begin' and '$query_end'";

                        if($begin_month != $end_month)
                        {
                                $sql_province = "select province,sum($req) from (
					select province,$req from `${begin_month}_gen`
					where hostname in (${hostname_list}) and `date` between '$query_begin' and '$query_end'
					union all 
					select province,$req from `${end_month}_gen` 
                                        where hostname in (${hostname_list}) and `date` between '$query_begin' and '$query_end'
                                        ) as total group by province";

				$sql_country = "select sum($req) from (
                                        select $req from `${begin_month}_gen`
                                        where hostname in (${hostname_list}) and `date` between '$query_begin' and '$query_end'
                                        union all 
                                        select $req from `${end_month}_gen` 
                                        where hostname in (${hostname_list}) and `date` between '$query_begin' and '$query_end'
                                        ) as total";
                        }
                        $sql_province .= " order by `sum($req)` desc limit 0,10";
                }

                unset($mysql_class);
                $mysql_class = new MySQL('newcdn');
                $mysql_class -> opendb("cdn_web_log_general", "utf8");

		$top_data["top_province"]["total"] = 0;
		$top_data["top_country"]["total"] = 0;
                $result = $mysql_class->query($sql_province);
                while( ($row = mysql_fetch_array($result)) )
                {
                        $top_data["top_province"]["$row[0]"] = $row[1];
			$top_data["top_province"]["total"] += $row[1];
                }
		mysql_free_result($result);

		$result = $mysql_class->query($sql_country);
                while( ($row = mysql_fetch_array($result)) )
                {
                        $top_data["top_country"]["中国大陆"] = $row[0];
			$top_data["top_country"]["total"] += $row[0];
                }
		mysql_free_result($result);
		*/


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

	function print_ret($bil_data, $map_data, $top_data)
	{
		$map = array();
                foreach($map_data as $time=>$value)
                {
                	$map[] = array('date' => $time, 'value' => $value);
                }
		print_r(json_encode($map));
		print("***");

		$province = array();
		$country = array();
		arsort($top_data["top_province"]);
		$i = 0;
		foreach($top_data as $type=>$data)
		{
			//print_r($data);
			if($top_data["$type"]["total"] == 0)
				continue;
			foreach($data as $locate=>$val)
			{
				//print_r($val);
				if($locate == "total")
					continue;
				$per = $val / $top_data["$type"]["total"] * 100;
				$per = sprintf("%.2f", $per) . "%";
				if($type == "top_province")
					$province[] = array('province' => $locate, 'value' => $val, 'per' => $per);
				else if($type == "top_country")
					$country[] = array('country' => $locate, 'value' => $val, 'per' => $per);
				$i ++;
				if($i == 10)
					break;
			}
		}
		if(count($province) == 0)
		{
			$province[] = array('province' => '#', 'value' => '-', 'per' => '-');
		}
		
		print_r(json_encode($province));
		print("***");
		if(count($country) == 0)
		{
			$country[] = array('country' => '#', 'value' => '-', 'per' => '-');
		}
		print_r(json_encode($country));
		print("***");

		if($bil_data)
			print_r(json_encode($bil_data));
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
		case "_hit":
		case "_pv":
			$channels = get_para("channel", "json");
			$query_time = get_para("time", "json");
			$zone = array("中国大陆");
			$isp = array("电信", "网通", "移动");
			if($type == "_hit")
				$bil_data = get_bil_data($channels, $query_time, $zone, $isp, $type);
			else
				$bil_data = false;
			$map_data = get_map_data($channels, $query_time, $zone, $isp, $type);
			$top_data = get_top_data($channels, $query_time, $zone, $isp, $type);
			syslog_user_action($client,$_SERVER['SCRIPT_NAME'],$channels,$query_time[0],$query_time[1]);
			print_ret($bil_data, $map_data, $top_data);
			break;
		default:
			exit;
			break;
	}

?>

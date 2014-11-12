<?php
//访问概况

	/*一天对应秒数*/
	define('ONE_DAY_SEC',86400);
	require_once('./web.com.fun.php');

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
		$hostname_list = "";
                $query_begin = $query_time[0];
                $query_end = $query_time[1];
                $sql = "";

                $hostname_list = get_hostname_list($channels);
		$sql = "";

		for( $bday = @strtotime($query_begin); $bday <= @strtotime($query_end); ) 
		{
			$day = @date("Y-m-d", $bday);
			$bday += 86400;
			if(!strlen($sql))
				$sql = "select cnt,hit_cnt from `$day` where hostname in (${hostname_list})";
			else
				$sql .= " union all select cnt,hit_cnt from `$day` where hostname in (${hostname_list})";
		}
		$sql = "select sum(cnt),sum(hit_cnt) from (" . $sql . ") as total";

		unset($mysql_class);
                $mysql_class = new MySQL('webstats');
                $mysql_class -> opendb("cdn_client_hit", "utf8");
                $result = $mysql_class->query($sql);
				if($result)
				{
					while( ($row = mysql_fetch_array($result)) )
					{
                        $bil_data[] = array('type' => "回源请求数", 'value' => ($row[0]-$row[1]));
						$bil_data[] = array('type' => "边缘节点请求数", 'value' => ($row[1]));
					}
					mysql_free_result($result);
				}
				
				if(count($bil_data) == 0)
				{
					$bil_data[] = array('type' => "Null", 'value' => 100);
				}

		return $bil_data;
	}

	function get_map_data($channels, $query_time, $zone, $isp, $type)
	{
		$map_data = array();
		$hostname_list = "";
		$query_begin = $query_time[0];
		$query_end = $query_time[1];
		$sql = "";

		$hostname_list = get_hostname_list($channels);

		$req = "";
		if($type == "_hit")
			$req = "cnt";
		else
			$req = "pv";

		if($query_begin != $query_end)
		{
			$begin_month = substr($query_begin, 0, -3);
			$end_month = substr($query_end, 0, -3);

			$sql = "select `date`,sum($req) from `${begin_month}_gen` 
				where hostname in (${hostname_list}) and `date` between '$query_begin' and '$query_end'
				group by `date`";

			if($begin_month != $end_month)
			{
				$sql = "select * from (" . $sql . "union all select `date`,sum($req) from `${end_month}_gen` 
					where hostname in (${hostname_list}) and `date` between '$query_begin' and '$query_end'
					group by `date` ) as total";
			}
			$sql .= " order by `date`";
		}
		else
		{
			$sql = "select `time`,sum($req) from `${query_begin}_gen` where hostname in (${hostname_list}) group by `time` order by `time`";
		}
		//print_r($hostname_list);
		//print($sql);
		//exit;
		unset($mysql_class);
		
		
		if($query_begin == $query_end)
		{
			for($i = 0 ; $i < 24; $i++)
			{
				$hour = sprintf("%02d", $i);
				$map_data[$hour] = '0';
			}
		}
	
		else
		{
			for( $bday = @strtotime($query_begin); $bday <= @strtotime($query_end); ) 
			{
				$day = @date("Y-m-d", $bday);
				$bday += ONE_DAY_SEC;
				$map_data[$day] = '0';

			}
		}
		
		$mysql_class = new MySQL('newcdn');
		$mysql_class -> opendb("cdn_web_log_general", "utf8");
		$result = $mysql_class->query($sql);
		if($result)
		{
			while( ($row = mysql_fetch_array($result)) ) 
			{
				$time = "";
				if($query_begin == $query_end)
					$time = substr($row[0], 0, 2);
				else
					$time = $row[0];
				$map_data["$time"] = $row[1];
			}
			mysql_free_result($result);
		}
		return $map_data;
        }

	function get_top_data($channels, $query_time, $zone, $isp, $type)
	{
		$top_data = array();
                $hostname_list = "";
                $query_begin = $query_time[0];
                $query_end = $query_time[1];
                $sql_province = "";
		$sql_country = "";

		$hostname_list = get_hostname_list($channels);

		$req = "";
                if($type == "_hit")
                        $req = "cnt";
                else
                        $req = "pv";

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
		foreach($top_data as $type=>$data)
		{
			if($top_data["$type"]["total"] == 0)
				continue;
			foreach($data as $locate=>$val)
			{
				if($locate == "total")
					continue;
				$per = $val / $top_data["$type"]["total"] * 100;
				$per = sprintf("%.2f", $per) . "%";
				if($type == "top_province")
					$province[] = array('province' => $locate, 'value' => $val, 'per' => $per);
				else if($type == "top_country")
					$country[] = array('country' => $locate, 'value' => $val, 'per' => $per);
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
			print_ret($bil_data, $map_data, $top_data);
			break;
		default:
			exit;
			break;
	}

?>

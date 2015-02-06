<?php
	date_default_timezone_set("Asia/Shanghai");
	function flow_format($flow)
	{
		$flow_level = 0;
		$flow = $flow * 8;
		return $flow;
		while($flow > 1024)
		{
			$flow = $flow / 1024;
			$flow_level++;
		}
		switch($flow_level)
		{
			case 0:
				$flow = sprintf("%.2f b", $flow);
				break;
			case 1:
				$flow = sprintf("%.2f K", $flow);
				break;
			case 2:
				$flow = sprintf("%.2f M", $flow);
				break;
			case 3:
				$flow = sprintf("%.2f G", $flow);
				break;
			case 4:
				$flow = sprintf("%.2f T", $flow);
				break;
			default:$flow = "Too Large";
		}
		return $flow;
	}



	$name = trim($_GET['name']);
	if(!$name || !strlen($name))
	{
		echo "请输入出口名字";
		exit(0);
	}
	$type = trim($_GET['type']);
	//$type = "print";
	if($type == "gen")
	{
		$gen = array();
		$fd = fopen("/dev/shm/gen_$name", "r");
                flock($fd, LOCK_EX);
		$record = fgets($fd);
		$arr = explode(" ", $record);
		$gen["每秒总流入报文"] = /*number_format*/(trim($arr[0]));
		$gen["每秒总流入流量"] = flow_format(trim($arr[1]));//number_format(trim($arr[1]) * 8) . " bps";
		$gen["每秒总流出报文"] = /*number_format*/(trim($arr[2]));
                $gen["每秒总流出流量"] = flow_format(trim($arr[3]));//number_format(trim($arr[3]) * 8) . " bps";
		$gen["出口总ip数"] = /*number_format*/(trim($arr[4]));
		flock($fd, LOCK_UN);
                fclose($fd);
		echo json_encode($gen);
	}
	else if($type == "attack")
	{
		$arg_date = trim($_GET['date']);
		$arg_time = trim($_GET['time']);
		$datetime = 0;
		$attack_type = array("syn"=>"SYN流量异常", "tcp"=>"TCP连接异常", "udp"=>"UDP流量异常", "icmp"=>"ICMP流量异常", "http"=>"HTTP请求异常", "ack"=>"ACK流量异常", "dns"=>"DNS请求异常");
		$ret = array();
		$history = fopen("/var/log/attack_history_$name", "r");
		$fd = fopen("/dev/shm/attack_$name", "r");
		if(strlen($arg_date) && strlen($arg_time))
		{
			$strtime = "$arg_date $arg_time";
			$datetime = strtotime($strtime);
		}
		flock($fd, LOCK_EX);
		if($history && $datetime)
                {
                        while(!feof($history))
                        {
                                $attack = array();
                                $str = trim(fgets($history));
                                if(!strlen($str))
                                        break;
                                $arr = explode(" ", $str);
				$attack_time = trim($arr[6]) . " " . trim($arr[7]);
				$attack_second = trim($arr[8]);
				$attack_over = strtotime($attack_time) + $attack_second;
				if($attack_over < $datetime)
					continue;
                                $attack["ip"] = trim($arr[0]);
                                $attack["攻击状态"] = "已结束";
                                $attack["攻击类型"] = $attack_type[trim($arr[1])];
                                $attack["当前包数"] = 0;
                                $attack["当前流量"] = 0;// . " bps";
                                $attack["峰值包数"] = /*number_format*/(trim($arr[4]));
                                $attack["峰值流量"] = flow_format(trim($arr[5]));//number_format(trim($arr[5]) * 8) . " bps";
                                $attack["攻击时间"] = trim($arr[6]) . " " . trim($arr[7]);
                                $attack["持续时间"] = trim($arr[8]) . " 秒";

                                $ret[] = $attack;
                        }
                        fclose($history);
                }
                while(1)
                {
			$attack = array();
                        $str = trim(fgets($fd));
                        if($str == "end")
                                break;
                        $arr = explode(" ", $str);
                        $attack["ip"] = trim($arr[0]);
			$attack["攻击状态"] = "正在进行";
			$attack["攻击类型"] = $attack_type[trim($arr[1])];
			$attack["当前包数"] = /*number_format*/(trim($arr[2]));
			$attack["当前流量"] = flow_format(trim($arr[3]));//number_format(trim($arr[3]) * 8) . " bps";
			$attack["峰值包数"] = /*number_format*/(trim($arr[4]));
			$attack["峰值流量"] = flow_format(trim($arr[5]));//number_format(trim($arr[5]) * 8) . " bps";
			$attack["攻击时间"] = trim($arr[6]) . " " . trim($arr[7]);
			$attack["持续时间"] = trim($arr[8]) . " 秒";
			
			$ret[] = $attack;
                }
                flock($fd, LOCK_UN);
                fclose($fd);
                echo json_encode($ret);
	}
	else if($type == "print")
	{
		$begin = trim($_GET['begin']);
                $total = trim($_GET['total']);

		$ret = array();
		$data = array();
		
		$fd = fopen("/dev/shm/data_$name", "rb");
		flock($fd, LOCK_EX);
		$stat = fread($fd, 8);
		$info = unpack("I2", $stat);
		$record_total = $info[1];
		$record_len = $info[2];

		if(!$begin || $begin <= 0)
                        $begin = 1;
                if(!$total || $total <= 0)
                        $total = $record_total;
                if($begin > $record_total - $total + 1)
                        $begin = $record_total - $total + 1;

		fseek($fd, ($begin - 1) * $record_len, SEEK_CUR);
		for($i = 0; $i < $total; $i++)
		{
			$line = array();
			$record = fread($fd, $record_len);
			$info = unpack("N2ip/L2recv/L2send/L2inflow/L2outflow/L2tcp/L2udp/L12other", $record);
			$line['ip'] = long2ip($info["ip1"]);
			$line['recv'] = /*number_format*/($info["recv2"] << 32 | $info["recv1"]);
			$line['send'] = /*number_format*/($info["send2"] << 32 | $info["send1"]);
			$line['inflow'] = flow_format($info["inflow2"] << 32 | $info["inflow1"]);
			$line['outflow'] = flow_format($info["outflow2"] << 32 | $info["outflow1"]);
			$line['tcpflow'] = flow_format($info["tcp2"] << 32 | $info["tcp1"]);
			$line['udpflow'] = flow_format($info["udp2"] << 32 | $info["udp1"]);
			$data[] = $line;
		}

		$ret['total'] = $record_total;
		$ret['data'] = $data;
		flock($fd, LOCK_UN);
                fclose($fd);
		echo json_encode($ret);
	}
	else if($type == "top")
	{
		$top = array('ppsin'=>'每秒流入报文','ppsout'=>'每秒流出报文','bpsin'=>'每秒流入流量','bpsout'=>'每秒流出流量','session'=>'每秒新建连接','http'=>'每秒http请求');
		$data = array();
	
		$fd = fopen("/dev/shm/top_$name", "r");
                flock($fd, LOCK_EX);
		while(1)
		{
			$str = trim(fgets($fd));
			if($str == "end")
				break;
			$arr = explode(" ", $str);
			$type = $arr[0];
			$total = $arr[1];
			$column = $top["$type"];

			$keyval = array();
			$tmp = array();
                	for($i = 0; $i < $total; $i++)
                	{
                        	$record = fgets($fd);
                        	$info = explode(" ", $record);
                        	$ip = trim($info[0]);
				$val = (trim($info[1]));
				$tmp[$ip] = $val;
                	}
			arsort($tmp);
			foreach($tmp as $ip=>$val)
			{
				if(strstr($column, "流量"))
					$keyval[$ip] = flow_format($val);
				else
					$keyval[$ip] = /*number_format*/($val);
			}
			$data[$column] = $keyval;
		}
		flock($fd, LOCK_UN);
                fclose($fd);
		echo json_encode($data);
	}
	else
	{
		if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
        	$fd = fopen("/tmp/output", "r");
        	flock($fd, LOCK_EX);
		if($type == "add")
		{
			if(!isset($_GET['ip']))
			{
				echo "请输入ip";
				exit(0);
			}
			$ip = trim($_GET['ip']);
			system("/usr/sbin/efdetect --name $name --add-ip $ip -o /tmp/output");
                        $result = fgets($fd);
			if($result == "succ")
				system("/usr/sbin/efdetect --name $name --save-ip -o /etc/efdetect/ip_list");
			echo $result;
		}
		else if($type == "del")
		{
			if(!isset($_GET['ip']))
                        {
                                echo "请输入ip";
                                exit(0);
                        }
			$ip = trim($_GET['ip']);
			system("/usr/sbin/efdetect --name $name --del-ip $ip -o /tmp/output");
                        $result = fgets($fd);
			if($result == "succ")
                                system("/usr/sbin/efdetect --name $name --save-ip -o /etc/efdetect/ip_list");
                        echo $result;
		}
		else
		{
			echo "请输入请求类型!\n";
		}
		flock($fd, LOCK_UN);
        	fclose($fd);
	}
?>

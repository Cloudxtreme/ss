<?php
	if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
	$fd = fopen("/tmp/output", "r");
	flock($fd, LOCK_EX);

	$type = trim($_GET['type']);
	//$type = "print";
	if($type == "print")
	{
		system("/usr/sbin/eftrack --print-ip -o /tmp/output");
		while(!feof($fd))
		{
			$record = fgets($fd);
			if(trim($record) == "succ")
				continue;
			$info = explode(" ", $record);
			$ip = $info[0];
			$recv = $info[1];
			$send = $info[2];
			$inflow = $info[3] * 8;
			$outflow = $info[4] * 8;
			echo "$ip { 收包:$recv 发包:$send 流入:$inflow 流出:$outflow }\n</br>";
		}
	}
	else
	{
		if($type == "add")
		{
			if(!isset($_GET['ip']))
			{
				echo "请输入ip";
				exit(0);
			}
			$ip = trim($_GET['ip']);
			system("/usr/sbin/eftrack --add-ip $ip -o /tmp/output");
                        $result = fgets($fd);
			if($result == "succ")
				system("/usr/sbin/eftrack --save-ip -o /etc/eftrack/ip_list");
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
			system("/usr/sbin/eftrack --del-ip $ip -o /tmp/output");
                        $result = fgets($fd);
			if($result == "succ")
                                system("/usr/sbin/eftrack --save-ip -o /etc/eftrack/ip_list");
                        echo $result;
		}
		else
		{
			echo "请输出请求类型!\n";
		}
	}
	flock($fd, LOCK_UN);
	fclose($fd);
?>

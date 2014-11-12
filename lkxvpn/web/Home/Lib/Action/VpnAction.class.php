<?php

ini_set('date.timezone','Asia/Shanghai');
// 本类由系统自动生成，仅供测试用途
class VpnAction extends Action {

	//添加链路
	public function link_add()
	{
		if($_SESSION['user'] === 'admin'){
			//只有登录状态才执行代码
			$gate = trim($_POST['gate']);
			$vlan = trim($_POST['vlan']);
			$source = trim($_POST['source']);
			$dest = trim($_POST['dest']);

			if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
			$fd = fopen("/tmp/output", "r");
			flock($fd, LOCK_EX);
			system("/usr/sbin/efvpn -a $gate,$vlan,$source,$dest -o /tmp/output");
			$result = fgets($fd);
			if($result == "succ")
			{
				system("/usr/sbin/efvpn -s");
				$ret = array("result"=>1, "reason"=>"");
			}
			else
				$ret = array("result"=>0, "reason"=>$result);
			flock($fd, LOCK_UN);
			fclose($fd);
			echo json_encode($ret);
		}else{
			header("Location: ./timeout.html");
		}
		//print_r($_POST);
	}
	
	//链路删除
	public function link_del()
	{
		if($_SESSION['user'] === 'admin'){
			//只有登录状态才执行代码
			$id = $_POST['id'];

			if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        $fd = fopen("/tmp/output", "r");
                        flock($fd, LOCK_EX);
			system("/usr/sbin/efvpn -d $id -o /tmp/output");
                        $result = fgets($fd);
                        if($result == "succ")
			{
				system("/usr/sbin/efvpn -s");
                                $ret = array("result"=>1, "reason"=>"");
			}
                        else
                                $ret = array("result"=>0, "reason"=>$result);
                        flock($fd, LOCK_UN);
                        fclose($fd);
                        echo json_encode($ret);
		}else{
			header("Location: ./timeout.html");
		}
		//print_r($_POST);
	}

	public function link_enable()
	{
		if($_SESSION['user'] === 'admin'){
                        //只有登录状态才执行代码
                        $id = $_POST['id'];

			if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        $fd = fopen("/tmp/output", "r");
                        flock($fd, LOCK_EX);
                        system("/usr/sbin/efvpn --enable $id -o /tmp/output");
                        $result = fgets($fd);
                        if($result == "succ")
			{
				system("/usr/sbin/efvpn -s");
                                $ret = array("result"=>1, "reason"=>"");
			}
                        else
                                $ret = array("result"=>0, "reason"=>$result);
                        flock($fd, LOCK_UN);
                        fclose($fd);
                        echo json_encode($ret);
                }else{
                        header("Location: ./timeout.html");
                }
                //print_r($_POST);
	}

	public function link_disable()
	{
		if($_SESSION['user'] === 'admin'){
                        //只有登录状态才执行代码
                        $id = $_POST['id'];

			if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        $fd = fopen("/tmp/output", "r");
                        flock($fd, LOCK_EX);
                        system("/usr/sbin/efvpn --disable $id -o /tmp/output");
                        $result = fgets($fd);
                        if($result == "succ")
			{
				system("/usr/sbin/efvpn -s");
                                $ret = array("result"=>1, "reason"=>"");
			}
                        else
                                $ret = array("result"=>0, "reason"=>$result);
                        flock($fd, LOCK_UN);
                        fclose($fd);
                        echo json_encode($ret);
                }else{
                        header("Location: ./timeout.html");
                }
                //print_r($_POST);
	}
	
	//链路列表
	public function link_stat()
	{
		if($_SESSION['user'] === 'admin'){
			$links = array();
			$handle = fopen("/dev/shm/efvpn", "r");
			flock($handle, LOCK_EX);
			$link_tot = fgets($handle);
			for($i = 0; $i < $link_tot; $i++)
			{
				$link = fgets($handle);
				$link_arr = explode(",", $link);
				$id = $link_arr[0];
				$gate = $link_arr[1];	$mac = $link_arr[2];	$vlan = $link_arr[3];
				$sip = $link_arr[4];	$dip = $link_arr[5];
				$stat = $link_arr[6];	$conn = $link_arr[7];
				$delay = $link_arr[8];	$pkg = $link_arr[9];	$flow = $link_arr[10];	$lost = $link_arr[11] . "%";
				if($conn == 2)
                                        $conn = "green";
                                else if($conn == 1)
                                        $conn = "yellow";
                                else
                                        $conn = "red";
				if($stat == 1)
					$stat = "enable";
				else
				{
					$stat = "disable";
					$conn = "gray";
					$delay = 0;
				}
				$pkg = number_format($pkg);
				$flow_level = 0;
				while($flow > 1024)
				{
					$flow = $flow / 1024;
					$flow_level++;
				}
				switch($flow_level)
				{
					case 0:
						$flow = sprintf("%.2f B", $flow);
						break;
					case 1:
						$flow = sprintf("%.2f KB", $flow);
						break;
					case 2:
						$flow = sprintf("%.2f MB", $flow);
						break;
					case 3:
						$flow = sprintf("%.2f GB", $flow);
						break;
					case 4:
						$flow = sprintf("%.2f TB", $flow);
						break;
					default:$flow = "Too Large";
				}
				$links[$id] = array("id"=>$id, "gate"=>$gate, "mac"=>$mac, "vlan"=>$vlan, "source"=>$sip, "dest"=>$dip, "stat"=>$stat, "conn"=>$conn, "delay"=>$delay, "pkg"=>$pkg, "flow"=>$flow, "lost"=>$lost);
			}
			flock($handle, LOCK_UN);
			fclose($handle);
			if($_SERVER['REQUEST_METHOD' ] === 'GET')
			{
				$this->link = $links;
                        	$this->display();
			}
			else
			{
				$outbound = false;
                        	$backend = false;
                        	$manager = false;
				if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        	$fd = fopen("/tmp/output", "r");
                        	flock($fd, LOCK_EX);
                        	system("/usr/sbin/server_conf --devstat outbound --output /tmp/output");
                        	$result = fgets($fd);
                        	if($result == "succ")
                        	        $outbound = true;
                        	system("/usr/sbin/server_conf --devstat backend --output /tmp/output");
                        	fseek($fd, 0, SEEK_SET);
                        	$result = fgets($fd);
                        	if($result == "succ")
                        	        $backend = true;
                        	system("/usr/sbin/server_conf --devstat manager --output /tmp/output");
                        	fseek($fd, 0, SEEK_SET);
                        	$result = fgets($fd);
                        	if($result == "succ")
                                	$manager = true;
                        	flock($fd, LOCK_UN);
                        	fclose($fd);
				$dev = array("outbound"=>$outbound, "backend"=>$backend, "manager"=>$manager);
				$ret = array("link"=>$links, "dev"=>$dev);
				echo json_encode($ret);
			}
		}else{
			header("Location: ./timeout.html");
		}
    }
	
	public function monitor_chart()
	{
		if($_SESSION['user'] === 'admin')
                {
			$this->display();
		}
		else
		{
			header("Location: ./timeout.html");
		}
	}

	public function monitor_data()
	{
		if($_SESSION['user'] === 'admin')
		{
                        //只有登录状态才执行代码
			{
				$fd = fopen("/dev/shm/stat.dat", "rb");
                        	flock($fd, LOCK_EX);
                	        $data = fread($fd, 20);
        	                $info = unpack("I5", $data);
	
        	                $link_tot = $info[1];
	                        $link_record_position = $info[2];
                        	$stat_record_position = $info[3];
                	        $link_record_len = $info[4];
        	                $stat_record_len = $info[5];

				
				if(isset($_GET['time']))
                        	       	$cur_time = $_GET['time'];
				else
					$cur_time = 0;
	                        $gate = $_GET['gate'];
                        	$vlan = $_GET['vlan'];
                	        $source = $_GET['source'];
        	                $dest = $_GET['dest'];
				
				/*
				$cur_time = 0;
				$gate = "192.168.200.2";
				$vlan = -1;
				$source = "192.168.200.3";
				$dest = "192.168.200.2";
				*/
	
        	                for($i = 0; $i < $link_tot; $i++)
	                        {
                                	fseek($fd, $link_record_position + $i * $link_record_len, SEEK_SET);
                        	        $data = fread($fd, $link_record_len);
                	                $record = unpack("I1id/a64info/I4stat", $data);
        	                        $link_id = $record["id"];
	                                $link_info = $record["info"];
                                	$link_stat_ptr = $record["stat1"];
                        	        $link_stat_position = $record["stat2"];
                	                $link_stat_tot = $record["stat3"];
        	                        $link_stat_cur = $record["stat4"];
	
	                               	$link_info_arr = explode(",", $link_info);
                               		$link_gate = $link_info_arr[0];
                       	        	$link_vlan = $link_info_arr[1];
               	                	$link_source = $link_info_arr[2];
                                	$link_dest = $link_info_arr[3];
	
                	                if(($gate != $link_gate) || ($vlan != $link_vlan) || ($source != $link_source) || ($dest != $link_dest))
        	                                continue;
	
                	                fseek($fd, $link_stat_position, SEEK_SET);
					$link = array();
					$times = array();
                	                for($j = 0; $j < $link_stat_tot; $j++)
        	                        {
	                                        $cur_stat_position = $link_stat_position + $link_stat_cur * $stat_record_len;
                                        	fseek($fd, $cur_stat_position, SEEK_SET);
                                	        $data = fread($fd, $stat_record_len);
                        	                $stat = unpack("L2time/L2delay/L2pps/L2speed/L2lost", $data);
                	                        $time = $stat["time2"] << 32 | $stat["time1"];
						$datetime = date("Y-m-d H:i:s", $time);
        	                                $delay = $stat["delay2"] << 32 | $stat["delay1"];
						$delay = round($delay/1000);
	                                        $pps = $stat["pps2"] << 32 | $stat["pps1"];
                                        	$speed = ($stat["speed2"] << 32 | $stat["speed1"]) * 8;
                                	        $lost = $stat["lost2"] << 32 | $stat["lost1"];
                        	                //echo "$time--$delay--$pkg--$flow--$lost\n";
                	                        if($time > $cur_time)
        	                                {
							$link[] = array("time"=>$time, "datetime"=>$datetime, "delay"=>$delay, "pps"=>$pps, "speed"=>$speed, "lost"=>$lost);
							$times[] = $time;
                        	                }
                	                        else
        	                                        break;
	                                        if($link_stat_cur > 0)
                                        	        $link_stat_cur--;
                                	        else
                        	                        $link_stat_cur = $link_stat_tot - 1;
                	                }
					array_multisort($times, SORT_ASC, $link);
					$ret = array("link"=>$link, "ret"=>1, "error"=>"");
					echo json_encode($ret);
	                        }
	                        flock($fd, LOCK_UN);
                        	fclose($fd);
			}
		}else
		{
                        header("Location: ./timeout.html");
                }
                //print_r($_POST);
	}
	
	//高级设置
	public function super_option(){
		if($_SESSION['user'] === 'admin'){
			if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$this->display();
			}
			else{
				print_r($_POST['password']); exit();
			}
		}else{
			header("Location: ./timeout.html");
		}
	}
	
	//修改密码
	public function change_pwd(){
		if($_SESSION['user'] === 'admin'){
			if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$this->display();
			}
			else{
				$password = trim(md5($_POST['password']));
				if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        	$fd = fopen("/tmp/output", "r");
                        	flock($fd, LOCK_EX);
                        	system("/usr/sbin/server_conf --pass $password --output /tmp/output");
                        	$result = fgets($fd);
                        	if($result == "succ")
                                	$ret = array("result"=>1, "reason"=>"");
                        	else
                                	$ret = array("result"=>0, "reason"=>$result);
                        	flock($fd, LOCK_UN);
                        	fclose($fd);
                        	echo json_encode($ret);
			}
		}else{
			header("Location: ./timeout.html");
		}
	}

	public function dev_stat()
	{
		if($_SESSION['user'] === 'admin')
		{
			$outbound = false;
			$backend = false;
			$manager = false;
			if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        $fd = fopen("/tmp/output", "r");
                        flock($fd, LOCK_EX);
                        system("/usr/sbin/server_conf --devstat outbound --output /tmp/output");
                        $result = fgets($fd);
                        if($result == "succ")
                                $outbound = true;
			system("/usr/sbin/server_conf --devstat backend --output /tmp/output");
			fseek($fd, 0, SEEK_SET);
			$result = fgets($fd);
                        if($result == "succ")
                                $backend = true;
			system("/usr/sbin/server_conf --devstat manager --output /tmp/output");
                        fseek($fd, 0, SEEK_SET);
                        $result = fgets($fd);
                        if($result == "succ")
                                $manager = true;
			$ret = array("outbound"=>$outbound, "backend"=>$backend, "manager"=>$manager);
                        flock($fd, LOCK_UN);
                        fclose($fd);
                        echo json_encode($ret);
		}
		else
		{
			header("Location: ./timeout.html");
		}
	}

	public function wnet_mgr()
	{
		if($_SESSION['user'] === 'admin'){
                        if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$fd = fopen("/etc/efvpn/dev", "r");
				if($fd)
				{
					$devs = fgets($fd);
					$arr = explode(",", $devs);
					$dev = trim($arr[1]);
					$nc = fopen("/etc/sysconfig/network-scripts/ifcfg-$dev", "r");
					if($nc)
					{
						while(($info = fgets($nc)))
						{
							if(strstr($info, "IPADDR"))
							{
								$irr = explode("=", $info);
								$this->ip = $irr[1];
							}
							if(strstr($info, "NETMASK"))
							{
								$irr = explode("=", $info);
								$this->mask = $irr[1];
							}
							if(strstr($info, "GATEWAY"))
							{
								$irr = explode("=", $info);
								$this->gateway = $irr[1];
							}
						}
						fclose($nc);
					}
					fclose($fd);
				}
                                $this->display();
                        }
                        else{
                        }
                }else{
                        header("Location: ./timeout.html");
                }
	}

	public function vpn_mgr()
	{
		if($_SESSION['user'] === 'admin'){
                        if($_SERVER['REQUEST_METHOD' ] === 'GET'){
                                $fd = fopen("/etc/efvpn/dev", "r");
                                if($fd)
                                {
                                        $devs = fgets($fd);
                                        $arr = explode(",", $devs);
                                        $dev = trim($arr[0]);
                                        $nc = fopen("/etc/sysconfig/network-scripts/ifcfg-$dev", "r");
                                        if($nc)
                                        {
                                                while(($info = fgets($nc)))
                                                {
                                                        if(strstr($info, "IPADDR"))
                                                        {
                                                                $irr = explode("=", $info);
                                                                $this->ip = $irr[1];
                                                        }
                                                        if(strstr($info, "NETMASK"))
                                                        {
                                                                $irr = explode("=", $info);
                                                                $this->mask = $irr[1];
                                                        }
                                                        if(strstr($info, "GATEWAY"))
                                                        {
                                                                $irr = explode("=", $info);
                                                                $this->gateway = $irr[1];
                                                        }
                                                }
                                                fclose($nc);
                                        }
                                        fclose($fd);
                                }
                                $this->display();
                        }
                        else{
                        }
                }else{
                        header("Location: ./timeout.html");
                }
	}

	public function vnet_mgr()
	{
		if($_SESSION['user'] === 'admin'){
                        if($_SERVER['REQUEST_METHOD' ] === 'GET'){
                                $fd = fopen("/etc/efvpn/dev", "r");
                                if($fd)
                                {
                                        $devs = fgets($fd);
                                        $arr = explode(",", $devs);
                                        $dev = trim($arr[2]);
                                        $nc = fopen("/etc/sysconfig/network-scripts/ifcfg-$dev", "r");
                                        if($nc)
                                        {
                                                while(($info = fgets($nc)))
                                                {
                                                        if(strstr($info, "IPADDR"))
                                                        {
                                                                $irr = explode("=", $info);
                                                                $this->ip = $irr[1];
                                                        }
                                                        if(strstr($info, "NETMASK"))
                                                        {
                                                                $irr = explode("=", $info);
                                                                $this->mask = $irr[1];
                                                        }
                                                        if(strstr($info, "GATEWAY"))
                                                        {
                                                                $irr = explode("=", $info);
                                                                $this->gateway = $irr[1];
                                                        }
                                                }
                                                fclose($nc);
						$dc = fopen("/usr/local/etc/dhcpd.conf", "r");
						if($dc)
						{
							while(($data = fgets($dc)))
							{
								if(strstr($data, "range"))
								{
									$dhcp_pool = explode(" ", $data);
									$this->dhcp_begin = trim($dhcp_pool[1]);
									$this->dhcp_end = trim(trim($dhcp_pool[2]), ";");
									break;
								}
							}
						}
                                        }
                                        fclose($fd);
                                }
                                $this->display();
                        }
                        else{
                        }
                }else{
                        header("Location: ./timeout.html");
                }
	}

	public function set_outbound()
	{
		if($_SESSION['user'] === 'admin'){
                        if($_SERVER['REQUEST_METHOD' ] === 'GET'){
                                $this->display();
                        }
                        else{
				$ip = trim($_POST['ip']);
                                $mask = trim($_POST['mask']);
				$gateway = trim($_POST['gateway']);
				if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                                $fd = fopen("/tmp/output", "r");
                                flock($fd, LOCK_EX);
                                system("/usr/sbin/server_conf --outbound $ip,$mask,$gateway --output /tmp/output");
                                $result = fgets($fd);
                                if($result == "succ")
                                        $ret = array("result"=>1, "reason"=>"");
                                else
                                        $ret = array("result"=>0, "reason"=>$result);
                                flock($fd, LOCK_UN);
                                fclose($fd);
                                echo json_encode($ret);
                        }
                }else{
                        header("Location: ./timeout.html");
                }
	}

	public function set_backend()
	{
		if($_SESSION['user'] === 'admin'){
                        if($_SERVER['REQUEST_METHOD' ] === 'GET'){
                                $this->display();
                        }
                        else{
				$ip = trim($_POST['ip']);
                                $mask = trim($_POST['mask']);
				if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                                $fd = fopen("/tmp/output", "r");
                                flock($fd, LOCK_EX);
                                system("/usr/sbin/server_conf --backend $ip,$mask --output /tmp/output");
                                $result = fgets($fd);
                                if($result == "succ")
                                        $ret = array("result"=>1, "reason"=>"");
                                else
                                        $ret = array("result"=>0, "reason"=>$result);
                                flock($fd, LOCK_UN);
                                fclose($fd);
                                echo json_encode($ret);
                        }
                }else{
                        header("Location: ./timeout.html");
                }
	}

	public function set_manager()
	{
		if($_SESSION['user'] === 'admin'){
                        if($_SERVER['REQUEST_METHOD' ] === 'GET'){
                                $this->display();
                        }
                        else{
				$ip = trim($_POST['ip']);
				$mask = trim($_POST['mask']);
				$dhcp_begin = trim($_POST['dhcp_begin']);
				$dhcp_end = trim($_POST['dhcp_end']);
				if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                                $fd = fopen("/tmp/output", "r");
                                flock($fd, LOCK_EX);
                                system("/usr/sbin/server_conf --dhcp $ip,$mask,$dhcp_begin,$dhcp_end --output /tmp/output");
                                $result = fgets($fd);
                                if($result == "succ")
                                        $ret = array("result"=>1, "reason"=>"");
                                else
                                        $ret = array("result"=>0, "reason"=>$result);
                                flock($fd, LOCK_UN);
                                fclose($fd);
                                echo json_encode($ret);
                        }
                }else{
                        header("Location: ./timeout.html");
                }
	}

	public function get_logger()
	{
		if($_SESSION['user'] === 'admin'){
                        //只有登录状态才执行代码
			if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        $fd = fopen("/tmp/output", "r");
                        flock($fd, LOCK_EX);
                        system("/usr/sbin/efvpn -p -o /tmp/output");
			$result = "";
                        while(($result = fgets($fd)))
			{
				if(strstr($result, "link-log"))
					break;
			}
			if(strstr($result, "link-log"))
			{
				$arr = explode(" ", $result);
				$info = explode(",", $arr[1]);
				$enable = $info[0];
				$ip = $info[1];
				$port = $info[2];
				$level = $info[3];
				if($enable == 1)
					$enable = "true";
				else
					$enable = "false";
				$ret = array("result"=>1, "ip"=>$ip, "port"=>$port, "level"=>$level, "enable"=>$enable);
			}
			else
			{
				$ret = array("result"=>0, "reason"=>"");
			}
                        flock($fd, LOCK_UN);
                        fclose($fd);
                        echo json_encode($ret);
                }else{
                        header("Location: ./timeout.html");
                }
	}

	public function set_logger()
	{
		if($_SESSION['user'] === 'admin'){
                        //只有登录状态才执行代码
                        $ip = trim($_POST['ip']);
                        $port = trim($_POST['port']);
                        $level = trim($_POST['level']);
			$enable = trim($_POST['enable']);

			if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        $fd = fopen("/tmp/output", "r");
                        flock($fd, LOCK_EX);
			if($enable == "true")
                        	system("/usr/sbin/efvpn --log-enable $ip,$port,$level -o /tmp/output");
			else
				system("/usr/sbin/efvpn --log-disable -o /tmp/output");
                        $result = fgets($fd);
                        if($result == "succ")
                                $ret = array("result"=>1, "reason"=>"");
                        else
                                $ret = array("result"=>0, "reason"=>$result);
                        flock($fd, LOCK_UN);
                        fclose($fd);
                        echo json_encode($ret);
                }else{
                        header("Location: ./timeout.html");
                }
	}

	public function get_timezone()
	{
		if($_SESSION['user'] === 'admin'){
			$current = "0.centos.pool.ntp.org";
			$zonelist = array("0.centos.pool.ntp.org", "1.centos.pool.ntp.org", "2.centos.pool.ntp.org");
			$fd = fopen("/etc/efvpn/update_time", "r");
			if($fd)
			{
				if(($info = fgets($fd)))
				{
					$arr = explode(" ", $info);
					$current = trim($arr[1]);
				}
                	}
			$ret = array("ret"=>1, "result"=>$zonelist, "current"=>$current);
			echo json_encode($ret);
		}
		else{
                        header("Location: ./timeout.html");
                }
	}

	public function set_timezone()
	{
		if($_SESSION['user'] === 'admin'){
			$server = trim($_POST['server']);
			if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        $fd = fopen("/tmp/output", "r");
                        flock($fd, LOCK_EX);
                        system("/usr/sbin/server_conf --ntp $server --output /tmp/output");
                        $result = fgets($fd);
                        if($result == "succ")
                                $ret = array("result"=>1, "reason"=>"");
                        else
                                $ret = array("result"=>0, "reason"=>$result);
                        flock($fd, LOCK_UN);
                        fclose($fd);
                        echo json_encode($ret);
                }
                else{
                        header("Location: ./timeout.html");
                }
	}

	public function get_license()
	{
		if($_SESSION['user'] === 'admin')
		{
			if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        $fd = fopen("/tmp/output", "r");
                        flock($fd, LOCK_EX);
			
			$method = trim($_POST['method']);
			if($method == "auto")
			{
				system("/usr/sbin/server_conf --license-get 61.142.208.98,5556 --output /tmp/output");
				$ret = trim(fgets($fd));
				if($ret == "Connect server fail!")
					$result = "连接超时，请检查网络!";
				else if($ret == "succ")
				{
					system("/usr/sbin/server_conf --license-check /etc/efvpn/license --output /tmp/output");
					fseek($fd, 0, SEEK_SET);
					$ret = fgets($fd);
					if($ret != "succ")
						$result = "授权失败，请重试!";
					else
						$result = "succ";
				}
				else
					$result = "授权失败，请检查是否合法设备!";
			}
			else if($method == "usb")
			{
			}
			else
			{
				if(file_exists("/tmp/license"))
				{
					
					system("/usr/sbin/server_conf --license-check /tmp/license --output /tmp/output");
                                	fseek($fd, 0, SEEK_SET);
                                	$ret = fgets($fd);
                                	if($ret != "succ")
                                		$result = "授权失败，请重试!";
					else
						$result = "succ";
				}
				else
					$result = "请上传授权文件!";
			}

                        if($result == "succ")
                                $ret = array("result"=>1, "reason"=>"");
                        else
                                $ret = array("result"=>0, "reason"=>$result);
                        flock($fd, LOCK_UN);
                        fclose($fd);
                        echo json_encode($ret);
		}
		else
		{
                        header("Location: ./timeout.html");
                }
	}

	public function sys_info()
	{
		if($_SESSION['user'] === 'admin'){
                        if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$sys_name = "";
				$sys_version = "";
				$sys_os = "";
				$sys_on = "";
				$sys_right = "广东睿江科技有限公司";
				system("/bin/rm /tmp/output");
				if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        	$fd = fopen("/tmp/output", "r");
                        	flock($fd, LOCK_EX);
                        	system("/usr/sbin/efvpn -p -o /tmp/output");
                        	$result = "";
                        	while(($result = fgets($fd)))
                        	{
                                	if(strstr($result, "sys_name"))
                                        {
						$arr = explode(" ", $result);
						$sys_name = trim($arr[1]);
					}
					if(strstr($result, "sys_version"))
					{
						$arr = explode(" ", $result);
						$sys_version = trim($arr[1]);
					}
                        	}
				//system("head -n 1 /etc/issue > /tmp/output");
				//system("uname -r > /tmp/output");
				//system("cat /proc/uptime| awk -F. '{run_days=$1 / 86400;run_hour=($1 % 86400)/3600;run_minute=($1 % 3600)/60;run_second=$1 % 60;printf(\"系统已运行 %d天%d时%d分%d秒\",run_days,run_hour,run_minute,run_second)}' >> /tmp/output");
				system("/usr/sbin/server_conf --sysinfo --output /tmp/output");
				fseek($fd, 0, SEEK_SET);
				$sys_id = trim(fgets($fd));
				$sys_os = /* trim(fgets($fd)) . */trim(fgets($fd)) ."";
				$sys_on = trim(fgets($fd));
				flock($fd, LOCK_UN);
				fclose($fd);
				$this->sys_name = $sys_name;
				$this->sys_ver = $sys_version;
				$this->sys_id = $sys_id;
				$this->sys_os = $sys_os;
				$this->sys_on = $sys_on;
				$this->sys_right = $sys_right;
                                $this->display();
				
                        }
                        else{
                        }
                }else{
                        header("Location: ./timeout.html");
                }
	}

	public function reset()
	{
		if($_SESSION['user'] === 'admin'){
                        //只有登录状态才执行代码

			if(!file_exists("/tmp/output")) {$fd = fopen("/tmp/output", "w");fclose($fd);}
                        $fd = fopen("/tmp/output", "r");
                        flock($fd, LOCK_EX);
                        //system("/usr/sbin/server_conf --reset --output /tmp/output");
			system("/usr/sbin/efvpn -c -o /tmp/output");
                        $result = fgets($fd);
                        if($result == "succ")
			{
				system("/usr/sbin/efvpn -s");
                                sleep(1);
                                system("/usr/sbin/efvpn --log-clean");
                                sleep(1);
				//system("service efvpnd restart");
				//system("/bin/rm -rf /etc/efvpn/pass");
				system("/usr/sbin/server_conf --pass admin");
				system("/usr/sbin/server_conf --ntp 0.centos.pool.ntp.org");
				system("/usr/sbin/server_conf --outbound 10.0.0.10,255.255.255.0,10.0.0.1");
				system("/usr/sbin/server_conf --backend 192.168.10.1,255.255.255.0");
				system("/usr/sbin/server_conf --dhcp 192.168.0.1,255.255.255.0,192.168.0.2,192.168.0.254");
                                $ret = array("result"=>1, "reason"=>"");
			}
                        else
                                $ret = array("result"=>0, "reason"=>$result);
                        flock($fd, LOCK_UN);
                        fclose($fd);
                        echo json_encode($ret);
                }else{
                        header("Location: ./timeout.html");
                }
	}
	
	public function upload() {
		if($_SESSION['user'] === 'admin'){
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();    //实例化上传类
			$upload->maxSize  = 3145728;  //设置附件上传大小
			$upload->uploadReplace = true; //覆盖同名文件
			//$upload->allowExts  = array('sh', 'rar', 'zip', 'jar', 'txt');// 设置附件上传类型
			//$upload->saveRule = uniqid;  //命名规则, 留空则使用原来文件名保存
			$arr = C("TMPL_PARSE_STRING");
			$path = $_SERVER['DOCUMENT_ROOT'].$arr['__PUBLIC__']."/Uploads/"; //设置附件上传目录
			//print_r($path);exit;
			$upload->savePath = $path;
			if(!$upload->upload()) {
				//上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{
				//上传成功
				$info = $upload->getUploadFileInfo();
				$p = "Public/Uploads/".$info[0]['savename'];
				$ret = array("result"=>1, "reason"=>"下发成功！");
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}

	public function restart()
	{
		if($_SESSION['user'] === 'admin'){
			system("/usr/sbin/efvpnd -s");
			sleep(3);
			//system("/sbin/reboot");
			system("/usr/sbin/server_conf --reboot");
		}else{
                        header("Location: ./timeout.html");
                }
	}

	public function shutdown()
	{
		if($_SESSION['user'] === 'admin'){
			system("/usr/sbin/efvpnd -s");
			sleep(3);
			//system("/sbin/shutdown -h now");
			system("/usr/sbin/server_conf --shutdown");
                }else{
                        header("Location: ./timeout.html");
                }
	}

}

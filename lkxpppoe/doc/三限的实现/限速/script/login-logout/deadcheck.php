<?php
require_once("/etc/ppp/config/share/httpsqs_client.php");
require_once("/etc/ppp/pppoe-recordupdate/killmark.php");
require_once("/etc/ppp/config/share/now.php");
require_once("/etc/ppp/libpro/libpro.php");
require_once("/etc/ppp/login-logout/getPPP.php");
define("PING_FILE","/etc/ppp/login-logout/ping");
define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");
define("QUENE_SERVER","localhost");
define("QUENE_PORT","12001");

define("WEIGHT_TOP",10);
define("WEIGHT_LESS",0);

$oldNet = array();
$ppps = array();

try
{
	prostart("deadcheck",getmypid());	
	
	while(true)
	{
		$records = getRecords();
		if(!$records)
		{
			continue;
		}
		
		pppCount();
		
		
		foreach($records as $ppp => $info)
		{
			if($info["pid"] == "" || $info["username"] == "")
			{
				system("php /etc/ppp/login-logout/unregister.php {$ppp}");
				echo getNowTime()." [{$argv[0]}]:"."set a user to off in pppoe.db\n";
				$ipaddrweignts[$info["ipaddr"]] = WEIGHT_LESS;
				continue;
			}
	
			if(!is_file("/proc/{$info["pid"]}/cmdline") || (!checkPPP($info["ipaddr"]) && !ping($info["ipaddr"])))
			{
				$username = $info["username"];	
				echo getNowTime()." [{$argv[0]}]:"."user[{$username}] lose connect and pid is down\n";
				system("php /etc/ppp/login-logout/logout.php {$username}");
			  	system("php /etc/ppp/pppoe-recordupdate/userdown.php {$username}");
				system("php /etc/ppp/login-logout/unregister.php {$ppp}");
				$ipaddrweignts[$info["ipaddr"]] = WEIGHT_LESS;
				update($username);
			}
		}
		
		//print_r($ipaddrweignts);
		sleep(1);
	}
}
catch(Exception $e)
{
	echo getNowTime()." [{$argv[0]}]:"."Caught exception:{$e->getMessage()}\n";
}

function getRecords()
{
	$records = array();
	$db = new SQLite3(SQLITE_DB_FILE);
	$results = $db->query("select * from pppoe where status='true'");
	if(!$results)
	{
		return false;
	}
	while($row = $results->fetchArray())
	{
		$ppp = $row["ppp"];
		$records[$ppp] = array();
		$records[$ppp]["pid"] = $row["pid"];
		$records[$ppp]["ipaddr"] = $row["ipaddr"];
		$records[$ppp]["username"] = $row["username"];
	}
	$db->close();
	return $records;
}


function checkPPP($ppp)
{
	global $ppps;
	if($ppps[$ppp] >= WEIGHT_TOP)
	{
		return false;
	}
	else
	{
		return true;
	}
}

function pppCount()
{
	global $oldNet;
	global $ppps;

	$newnet = getPPPNetworks();
	
	foreach($ppps as $ppp => $val)
	{
		if(empty($newnet[$ppp]))
		{
			$ppps[$ppp] = WEIGHT_TOP;
		}
	}
	
	foreach($newnet as $ppp => $data)
	{
		if(empty($oldnet[$ppp]))
		{
			$ppps[$ppp] = WEIGHT_LESS;
		}
		else if($oldnet[$ppp]["ReceivePackets"] == $newnet[$ppp]["ReceivePackets" && $ppps[$ppp]<WEIGHT_TOP])
		{
			$ppps[$ppp]++;
		}
		else
		{
			$ppps[$ppp] = WEIGHT_LESS;
		}
	}
	
	//print_r($ppps);
}

function ping($ipaddr)
{
	global $ipaddrweignts;
	$count = 0;
	for($i = 0;$i<4;$i++)
	{
		usleep(100);
		system(PING_FILE." {$ipaddr}", $result);
		if($result == 0)
		{
			$count++;
		}
	}
	if($count == 0)
	{
		return false;
	}
	else
	{
		$ipaddrweignts[$ipaddr] = WEIGHT_MID;
		return true;
	}
}
?>

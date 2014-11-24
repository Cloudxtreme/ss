<?php
require_once("/etc/ppp/config/share/mysqlconnection.php");
require_once("/etc/ppp/config/pppoe-config.php");
require_once("/etc/ppp/config/share/now.php");
require_once("/etc/ppp/libpro/libpro.php");
//define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");
define("UPDATE_PART_SLEEP_TIME",10);
ini_set('error_reporting', E_ALL | E_STRICT);

openlog("syn-user",  LOG_ODELAY, LOG_LOCAL3);
try
{
	prostart("synuser",getmypid());	
	while(true)
	{
		syn_ppp_sqlite();
		syn_sqlite_mysql();
		sleep(UPDATE_PART_SLEEP_TIME);
	}
}
catch (Exception $e)
{
	echo getNowTime()." [{$argv[0]}]:"."Caught exception: {$e->getMessage()}\n";
}

closelog();

function syn_sqlite_mysql()
{
	global $config;
	
	$database = $config->configs["radius-db"];
	$pppoe_server = $config->configs["pppoe-server"];
	$users = array();
	$connection = new mysqlconnection($database["server"],$database["username"],$database["password"]);
	$connection->useDB($database["db"]);
	$statement = "select `username`,`pid` from `userinfo` where `server`='{$pppoe_server["pppoe_server"]}'";
	//echo $statement."\n";
	$result = $connection->query($statement);
	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$users[$row["username"]] = $row["pid"];
	}
	
	//get users from sqlite3
	$localusers = array();
	$lite = new SQLite3(SQLITE_DB_FILE);
	$results = $lite->query("select username,pid from pppoe where username!='null' and status='true'");
	while($row = $results->fetchArray())
	{
		$localusers[$row["username"]] = $row["pid"];
	}	
	$lite->close();
	
	foreach($localusers as $username => $pid)
	{
		if(!isset($users[$username]))
	    {
	     	$users[$username] = $pid;
	     	$statement = "update `userinfo` set `server`='{$pppoe_server["pppoe_server"]}',`pid`='{$pid}' where `username`='{$username}'";
	     	$connection->query($statement);
	     	syslog(LOG_INFO, "syn-on-to-radius:[{$username}->{$pid}]");
	     	//echo getNowTime()." [{$argv[0]}]:"."update a userlogin[{$username}] to radius database with pid[{$pid}]\n";
	    }		
	}
	
	foreach($users as $username => $pid)
	{
		if(!isset($localusers[$username]))
		{
			$statement = "update `userinfo` set `server`='off',`pid`='0' where `username`='{$username}'";
	      	$connection->query($statement);
	      	syslog(LOG_INFO, "syn-off-to-radius:[{$username}->{$pid}]");
	      	//echo getNowTime()." [{$argv[0]}]:"."update a userlogout[{$username}] to radius database with pid[0]\n";	
		}
	}
	
	//print_r($users);
	//print_r($localusers);
	/*
	foreach($users as $user => $pid)
	{
		if(!checkPid($pid))
		{
			$statement = "update `userinfo` set `server`='off',`pid`='0' where `username`='{$user}'";
			$connection->query($statement);
			syslog(LOG_INFO, "syn-off-to-radius:[{$username}->{$pid}]");
			//echo getNowTime()." user[{$user} is down with pid[{$pid}]]\n";
		}
	}
	*/
}

function syn_ppp_sqlite()
{
	$infos = getPPPNetworks();
	$ppps = get_ppp_from_sqlite();
	
	foreach($ppps as $ppp=>$val)
	{
		if(!isset($infos[$ppp]) && !checkPid($ppps[$ppp]["pid"]))
		{
			setoff_ppp_to_sqlite($ppp);
		}
	}
	
	foreach($infos as $ppp=>$ipaddr)
	{
		if(!isset($ppps[$ppp]))
		{
			echo "{$ppp}\n";
			seton_ppp_to_sqlite($ppp);
		}
		else
		{
			$username = getLatestUsername($ipaddr);
			if($ipaddr != $ppps[$ppp]["ipaddr"] || $username != $ppps[$ppp]["username"])
			{
				setupdate_ppp_to_sqlite($ppp,$username,$ipaddr);
			}
		}
	}
}

function setoff_ppp_to_sqlite($ppp)
{
	syslog(LOG_INFO, "syn-off-to-sqlite:[{$ppp}]");
	$lite = new SQLite3(SQLITE_DB_FILE);
	while(!$lite->exec("update pppoe set status='false',username='null',ipaddr='null' where ppp='{$ppp}'"))
	{
		usleep(1000);
	}
	$lite->close();
}

function seton_ppp_to_sqlite($ppp)
{
	syslog(LOG_INFO, "syn-on-to-sqlite:[{$ppp}]");
	$lite = new SQLite3(SQLITE_DB_FILE);
	while(!$lite->exec("update pppoe set status='true' where ppp='{$ppp}'"))
	{
		usleep(1000);
	}
	$lite->close();
}

function setupdate_ppp_to_sqlite($ppp,$username,$ipaddr)
{
	echo "syn-update-to-sqlite:[{$ppp}--{$username}--{$ipaddr}]\n";
	syslog(LOG_INFO, "syn-update-to-sqlite:[{$ppp}--{$username}--{$ipaddr}]");
	$lite = new SQLite3(SQLITE_DB_FILE);
	while(!@$lite->exec("update pppoe set status='true',username='{$username}',ipaddr='{$ipaddr}' where ppp='{$ppp}'"))
	{
		usleep(1000);
	}
	$lite->close();
}

function getLatestIP($username)
{
	global $config;

	$database = $config->configs["radius-db"];
	$connection = new mysqlconnection($database["server"],$database["username"],$database["password"]);
    $connection->useDB($database["db"]);
    $statement = "select framedipaddress from radacct where username='{$username}' order by acctstarttime desc limit 1";
    //echo $statement."\n";
    $result = $connection->query($statement);
    if($row = mysql_fetch_array($result, MYSQL_ASSOC))
    {
             return  $row["framedipaddress"];
    }
	return false;
}

function getLatestUsername($ip)
{
	global $config;

	$database = $config->configs["radius-db"];
	$connection = new mysqlconnection($database["server"],$database["username"],$database["password"]);
    $connection->useDB($database["db"]);
    $statement = "select username from radacct where framedipaddress='{$ip}' order by acctstarttime desc limit 1";
    //echo $statement."\n";
    $result = $connection->query($statement);
    if($row = mysql_fetch_array($result, MYSQL_ASSOC))
    {
             return  $row["username"];
    }
	return false;
}

function checkPid($pid)
{
	$filename = "/proc/{$pid}/stat";	
	if(file_exists($filename) )
	{
		$content = file_get_contents($filename);
		if(strpos($content,"pppd") !== FALSE)
		{
			return true;
		}
	}
	return false;
}

function checkPPP($username,$pid)
{
	$infos = getPPPNetworks();
	$localusers = array();
	$lite = new SQLite3(SQLITE_DB_FILE);
	$results = $lite->query("select ppp from pppoe where username='{$username}' and pid='{$pid}' and status='true'");
	while($results && $row = $results->fetchArray())
	{
		if(isset($infos[$row["ppp"]]))
		{
			//$lite->query("update pppoe set status='true' where ppp='{$row["ppp"]}'");
			$lite->close();
			return true;
		}
	}
	$lite->close();
	return false;
}

function get_ppp_from_sqlite()
{
	$localppp = array();
	$lite = new SQLite3(SQLITE_DB_FILE);
	$results = $lite->query("select ppp,pid,username,ipaddr from pppoe where status='true'");
	while($results && $row = $results->fetchArray())
	{
		$localppp[$row["ppp"]]["pid"] = $row["pid"];
		$localppp[$row["ppp"]]["username"] = $row["username"];
		$localppp[$row["ppp"]]["ipaddr"] = $row["ipaddr"];
	}
	$lite->close();
	return $localppp;
}

function get_null_username_from_sqlite()
{
	$localppp = array();
	$lite = new SQLite3(SQLITE_DB_FILE);
	$results = $lite->query("select ppp,ipaddr from pppoe where status='true' and username='null'");
	while($results && $row = $results->fetchArray())
	{
		$localppp[$row["ppp"]] = $row["ipaddr"];
	}
	$lite->close();
	return $localppp;
}

function get_null_ip_from_sqlite()
{
	$localppp = array();
	$lite = new SQLite3(SQLITE_DB_FILE);
	$results = $lite->query("select ppp,username from pppoe where status='true' and ipaddr='null'");
	while($results && $row = $results->fetchArray())
	{
		$localppp[$row["ppp"]] = $row["username"];
	}
	$lite->close();
	return $localppp;
}

function getPPPNetworks()
{
	$infos = array();
	@exec("ip add|grep ppp|grep inet",$array);
	foreach($array as $line)
	{
		if (preg_match( '/peer[ \t]+(.*?)\\/32.*(ppp.*?)$/', $line, $found ))
		{
			$ipaddr = trim($found[1]);
			$eth = trim($found[2]);
			$infos[$eth] = $ipaddr;
		}   
	}
	return $infos;
}
?>

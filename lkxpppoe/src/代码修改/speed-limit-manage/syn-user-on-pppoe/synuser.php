<?php
require_once("/etc/ppp/config/share/mysqlconnection.php");
require_once("/etc/ppp/config/pppoe-config.php");
require_once("/etc/ppp/config/share/now.php");
require_once("/etc/ppp/libpro/libpro.php");
define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");
define("UPDATE_PART_SLEEP_TIME",5);
try
{
	prostart("synuser",getmypid());	
	$database = $config->configs["radius-db"];
	$pppoe_server = $config->configs["pppoe-server"];
	
	//print_r($database);
	//print_r($pppoe_server);

	while(true)
	{
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
	        $results = $lite->query("select username,pid from pppoe where status='true'");
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
	                        echo getNowTime()." [{$argv[0]}]:"."update a userlogin[{$username}] to radius database with pid[{$pid}]\n";
	                }		
		}
	
		foreach($users as $username => $pid)
		{
			if(!isset($localusers[$username]))
			{
				$statement = "update `userinfo` set `server`='off',`pid`='0' where `username`='{$username}'";
	                        $connection->query($statement);
	                        echo getNowTime()." [{$argv[0]}]:"."update a userlogout[{$username}] to radius database with pid[0]\n";	
			}
		}
	
		//print_r($users);
		//print_r($localusers);
		foreach($users as $user => $pid)
		{
			if(!checkPid($pid))
			{
				$statement = "update `userinfo` set `server`='off',`pid`='0' where `username`='{$user}'";
				$connection->query($statement);
				echo getNowTime()." user[{$user} is down with pid[{$pid}]]\n";
			}
		}
		sleep(UPDATE_PART_SLEEP_TIME);
	}
	
}
catch (Exception $e)
{
	echo getNowTime()." [{$argv[0]}]:"."Caught exception: {$e->getMessage()}\n";
}

function checkPid($pid)
{
	$filename = "/proc/{$pid}/stat";	
	if(file_exists($filename) )
	{
		$content = file_get_contents($filename);
		if(strpos($content,"pppd") !== FALSE)
		{
			return TRUE;
		}
	}
	return TRUE;
}
?>

<?php
require_once("/etc/ppp/config/share/mysqlconnection.php");
require_once("/etc/ppp/config/pppoe-config.php");
require_once("/etc/ppp/config/share/now.php");
require_once("/etc/ppp/config/share/httpsqs_client.php");
define("QUENE_SERVER","localhost");
define("QUENE_PORT","12001");
define("QUENE_NAME","pppoe_mgr_user_kil");
define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");

openlog("kill-mul",  LOG_ODELAY, LOG_LOCAL3);

$users = getMulUser();
foreach($users as $user=>$time)
{
	$latestIp = getLatestIP($user);
	$pids = getKillPids($user,$latestIp);
	if(count($pids) != 0)
	{
		$logstr = "keep [{$user}->{$latestIp}];kill ";
		foreach($pids as $pid)
		{
			kill($user,$pid);
			$logstr = $logstr."[{$pid}] ";	
		}
		syslog(LOG_INFO, $logstr);
	}
}
closelog();

function getMulUser()
{
	$localusers = array();
    $lite = new SQLite3(SQLITE_DB_FILE);
    $results = $lite->query("select username,count(*) as time from pppoe where username!='null' and status='true' group by username;");
    while($row = $results->fetchArray())
    {
            $localusers[$row["username"]] = $row["time"];
    }
    $lite->close();	
	$mulUsers = array();
	foreach($localusers as $username => $time)
	{
		if($time > 1)
		{
			$mulUsers[$username] = $time;
		}
	}
	return $mulUsers;
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
	return null;
}

function getKillPids($username,$latestIp)
{
	$pids = array();
    $lite = new SQLite3(SQLITE_DB_FILE);
    $results = $lite->query("select pid  from pppoe where status='true' and username='{$username}' and ipaddr != '{$latestIp}'");
    while($row = $results->fetchArray())
    {
    	$pids[] = $row["pid"];
    }
    $lite->close();
	return $pids;
}

function kill($username,$pid)
{
	$object["pid"] = $pid;
    $object["username"] = $username;
    $info = json_encode($object);
    $pppoeserver = new httpsqs(QUENE_SERVER,QUENE_PORT);
    $result = $pppoeserver->put(QUENE_NAME,$info);		
}

?>

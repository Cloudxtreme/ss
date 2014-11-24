<?php
require_once("/etc/raddb/config/share/mysqlconnection.php");
require_once("/etc/raddb/config/share/httpsqs_client.php");
require_once("/etc/raddb/config/radius-config.php");
require_once("/etc/raddb/config/share/now.php");
$radius_db = $config->configs["radius-db"];

openlog("kill-login",  LOG_ODELAY, LOG_LOCAL3);

define("PPPOE_QUENE_PORT",12001);
define("PPPOE_QUENE_NAME","pppoe_mgr_user_kil");

function checkkill()
{
	global $radius_db;
	$manage = new mysqlconnection($radius_db["server"],$radius_db["username"],$radius_db["password"]);
	$manage->useDB($radius_db["db"]);
	$statement = "select username,server,pid from userinfo where username in(select username from userdate where forbidden='true' or (`begin`>now() or (UNIX_TIMESTAMP(`end`)+86400)<UNIX_TIMESTAMP(now()))) and server!='off'";
	$result = $manage->query($statement);
	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$username = $row["username"];
		$server = $row["server"];
		$pid = $row["pid"];
		kill($server,$username,$pid);

        syslog(LOG_INFO, "kill-user-for-forbidden:[{$username}->{$server}->{$pid}]");
	}
}

function kill($server,$username,$pid)
{
	$object["pid"] = $pid;
    $object["username"] = $username;
    $info = json_encode($object);
    $pppoeserver = new httpsqs($server,PPPOE_QUENE_PORT);
    $result = $pppoeserver->put(PPPOE_QUENE_NAME,$info);
}

checkkill();
closelog();
?>

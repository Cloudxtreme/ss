<?php
require_once("/etc/raddb/config/share/mysqlconnection.php");
require_once("/etc/raddb/config/share/httpsqs_client.php");
require_once("/etc/raddb/config/radius-config.php");
require_once("/etc/raddb/config/share/now.php");
$radius_db = $config->configs["radius-db"];

define("PPPOE_QUENE_PORT",12001);
define("PPPOE_QUENE_NAME","pppoe_mgr_user_kil");

function checkkill()
{
	global $radius_db;
	$manage = new mysqlconnection($radius_db["server"],$radius_db["username"],$radius_db["password"]);
	$manage->useDB($radius_db["db"]);
	$statement = "select username,server,pid from userinfo where username in(select username from userdate where exceed='true' or forbidden='true') and server!='off'";
	$result = $manage->query($statement);
	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		echo getNowTime()."kill user[{$username}]\n";
		$head = "synuser.php";
        	$content = "update center database user[{$username}] status to :{$status}";
        	mylog($head,$content);
		
		$username = $row["username"];
		$server = $row["server"];
		$pid = $row["pid"];
		kill($server,$username,$pid);

		$head = "killlogin.php";
                $content = "kill user[{$username}] in server[{$server}] with pid[{$pid}]";
                mylog($head,$content);
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
?>

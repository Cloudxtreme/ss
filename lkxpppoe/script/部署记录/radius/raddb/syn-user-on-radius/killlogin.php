<?php
require_once("mysqlconnection.php");
require_once("httpsqs_client.php");
define("LOCAL_DB_SERVER","localhost");
define("LOCAL_DB_USERNAME","root");
define("LOCAL_DB_PASSWORD","rjkj@rjkj");
define("LOCAL_DB_DB","radius");

define("PPPOE_QUENE_PORT",12001);
define("PPPOE_QUENE_NAME","pppoe_mgr_user_kil");

function checkkill()
{
	$manage = new mysqlconnection(LOCAL_DB_SERVER,LOCAL_DB_USERNAME,LOCAL_DB_PASSWORD);
	$manage->useDB(LOCAL_DB_DB);
	$statement = "select username,server,pid from userinfo where username in(select username from userdate where exceed='true' or forbidden='true')";
	$result = $manage->query($statement);
	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$username = $row["username"];
		$server = $row["server"];
		$pid = $row["pid"];
		kill($server,$username,$pid);
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

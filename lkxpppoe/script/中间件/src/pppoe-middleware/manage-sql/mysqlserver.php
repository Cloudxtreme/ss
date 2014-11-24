<?php
ini_set('max_execution_time','0');
require_once("/root/config/share/mysqlconnection.php");
require_once("/root/config/share/httpsqs_client.php");
require_once("/root/config/mid-config.php");

$center_db = $config->configs["center-db"];

define("QUENE_NAME","pppoe_mgr_manage_sql");
define("QUENE_SERVER","mid.pppoe.rightgo.net");
define("QUENE_PORT","12001");
define("SLEEP_TIME",10000);

$httpsqs = new httpsqs(QUENE_SERVER,QUENE_PORT);
while(1)
{
	$sql = $result = $httpsqs->get(QUENE_NAME);
    if("HTTPSQS_GET_END" == $sql)
    {
        usleep(SLEEP_TIME);
    }
    else
	{
		echo $sql."\n";
		executesql($sql);
	}
}

function executesql($sql)
{
	$manage = new mysqlconnection($center_db["server"],$center_db["username"],$center_db["password"]);
	$manage->useDB($center_db["record_db"]);
	$manage->query($sql);
}
?>

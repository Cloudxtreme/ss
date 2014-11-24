<?php
define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");

if($argc != 4)
{
	return;
}

$eth = $argv[1];
$pid = $argv[2];
$ipaddr = $argv[3];

$db = new SQLite3(SQLITE_DB_FILE);
$results = $db->query("select ppp from pppoe where ppp='{$eth}'");
if($row = $results->fetchArray()) 
{
	$results = $db->exec("update pppoe set pid='{$pid}',ipaddr='{$ipaddr}',status='true' where ppp='{$eth}'");
}
else
{
        $results = $db->exec("insert into pppoe values('{$eth}','{$pid}','{$ipaddr}','','true')");
}
$db->close();
?>

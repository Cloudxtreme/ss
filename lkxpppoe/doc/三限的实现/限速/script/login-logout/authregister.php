<?php
require_once("/etc/ppp/config/share/now.php");
define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");

if($argc != 4)
{
	return;
}

$eth = $argv[1];
$username = $argv[2];
$pid = $argv[3];

echo getNowTime()." [{$argv[0]}]:"."user[{$username}] login use eth[{$eth}] and pid[{$pid}]\n";

$db = new SQLite3(SQLITE_DB_FILE);
$results = $db->query("select ppp from pppoe where ppp='{$eth}'");
if($row = $results->fetchArray()) 
{
	$results = $db->exec("update pppoe set username='{$username}',pid='{$pid}' where ppp='{$eth}'");
}
else
{
	$results = $db->exec("insert into pppoe values('{$eth}','{$pid}','','{$username}','true')");
}
$db->close();
?>

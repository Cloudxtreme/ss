<?php
define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");

if($argc != 4)
{
	return;
}

openlog("register",  LOG_ODELAY, LOG_LOCAL3);
$eth = $argv[1];
$pid = $argv[2];
$ipaddr = $argv[3];
syslog(LOG_INFO, "sqlite-pppoe-on-addr:[{$eth}->{$pid}->{$ipaddr}]");
$db = new SQLite3(SQLITE_DB_FILE);
$results = $db->query("select ppp from pppoe where ppp='{$eth}'");
if($row = $results->fetchArray()) 
{
	$results = $db->exec("update pppoe set pid='{$pid}',ipaddr='{$ipaddr}',status='true' where ppp='{$eth}'");
}
else
{
    $results = $db->exec("insert into pppoe values('{$eth}','{$pid}','{$ipaddr}','null','true')");
}
$db->close();
closelog();
?>

<?php
define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");

if($argc != 2)
{
	return;
}
openlog("unregister",  LOG_ODELAY, LOG_LOCAL3);
$eth = $argv[1];
syslog(LOG_INFO, "sqlite-pppoe-off:[{$eth}]");
$db = new SQLite3(SQLITE_DB_FILE);
$results = $db->exec("update pppoe set status='false',username='null',ipaddr='null' where ppp='{$eth}'");
$db->close();
closelog();
?>

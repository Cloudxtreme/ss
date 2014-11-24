<?php
define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");

if($argc != 2)
{
	return;
}

$eth = $argv[1];

$db = new SQLite3(SQLITE_DB_FILE);
$results = $db->exec("update pppoe set status='false' where ppp='{$eth}'");
$db->close();

?>

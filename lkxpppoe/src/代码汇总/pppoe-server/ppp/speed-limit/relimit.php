<?php
require_once("/etc/ppp/config/share/mysqlconnection.php");
require_once("/etc/ppp/config/pppoe-config.php");
require_once("/etc/ppp/config/share/now.php");
require_once("/etc/ppp/libpro/libpro.php");
//define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");
define("UPDATE_PART_SLEEP_TIME",10);
ini_set('error_reporting', E_ALL | E_STRICT);

$ppps = get_ppp_from_sqlite();
foreach($ppps as $ppp => $username)
{
	system("php /etc/ppp/speed-limit/unspeedLimit.php {$ppp}");
	system("php /etc/ppp/speed-limit/speedLimit.php {$ppp} {$username}");
}

function get_ppp_from_sqlite()
{
	$localppp = array();
	$lite = new SQLite3(SQLITE_DB_FILE);
	$results = $lite->query("select ppp,pid,username,ipaddr from pppoe where status='true'");
	while($results && $row = $results->fetchArray())
	{
		$localppp[$row["ppp"]] = $row["username"];
	}
	$lite->close();
	return $localppp;
}

?>

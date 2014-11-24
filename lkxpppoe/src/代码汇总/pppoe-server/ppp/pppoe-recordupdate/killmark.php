<?php

require_once("/etc/ppp/config/share/mysqlconnection.php");
require_once("/etc/ppp/config/pppoe-config.php");
require_once("/etc/ppp/config/share/now.php");

$radius_db = $config->configs["radius-db"];


function update($username)
{
//	echo getNowTime()." [killmark.php]:"."force update radacct,complete data of user[{$username}] when acctstoptime is null\n";
	openlog("killmask-update",  LOG_ODELAY, LOG_LOCAL3);
	system("php /etc/ppp/login-logout/logout.php {$username}");
	system("php /etc/ppp/pppoe-recordupdate/userdown.php {$username}");
	global $radius_db;
	$record = array();
	$connection = new mysqlconnection($radius_db["server"],$radius_db["username"],$radius_db["password"]);
	$connection->useDB($radius_db["db"]);
	$statement = "update radacct set acctstoptime=FROM_UNIXTIME(UNIX_TIMESTAMP(acctstarttime)+acctsessiontime),acctterminatecause='program' where username='{$username}' and acctstoptime is null"; 
	$result = $connection->query($statement);
	syslog(LOG_INFO, "mysql-radacct-makeup:[{$username}]");
	closelog();
}

?>

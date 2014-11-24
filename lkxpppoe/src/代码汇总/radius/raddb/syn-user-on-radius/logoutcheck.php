<?php
ini_set('max_execution_time','0');
require_once("/etc/raddb/config/share/mysqlconnection.php");
require_once("/etc/raddb/config/radius-config.php");
require_once("/etc/raddb/libpro/libpro.php");
require_once("/etc/raddb/config/share/now.php");
require_once("/etc/raddb/config/share/log.php");

define("LOG_FILE","/var/log/radius-log.log");
define("UPDATE_PART_SLEEP_TIME",5);

$radius_db = $config->configs["radius-db"];

openlog("logout-check",  LOG_ODELAY, LOG_LOCAL3);
prostart("logoutcheck",getmypid());

while(true)
{
	$connection = new mysqlconnection($radius_db["server"],$radius_db["username"],$radius_db["password"]);
	$connection->useDB($radius_db["db"]);
	$statement = "update radacct set acctstoptime=FROM_UNIXTIME(UNIX_TIMESTAMP(acctstarttime)+acctsessiontime),acctterminatecause='program' where acctstoptime is null and username not in(select username from userinfo where server!='off')";
	$connection->query($statement);
	$num = mysql_affected_rows();
	if($num != 0)
	{
        	syslog(LOG_INFO, "logout-check:reset {$num} users");
	}

	sleep(UPDATE_PART_SLEEP_TIME);
}
closelog();
?>

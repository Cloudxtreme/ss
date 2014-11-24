<?php
ini_set('max_execution_time','0');
require_once("/etc/raddb/config/share/mysqlconnection.php");
require_once("/etc/raddb/config/radius-config.php");

require_once("/etc/raddb/config/share/now.php");
require_once("/etc/raddb/config/share/log.php");
require_once("/etc/ppp/libpro/libpro.php");
define("LOG_FILE","/var/log/radius-log.log");

define("UPDATE_PART_SLEEP_TIME",5);

//prostart("logoutcheck",getmypid());
$radius_db = $config->configs["radius-db"];

while(true)
{
	$connection = new mysqlconnection($radius_db["server"],$radius_db["username"],$radius_db["password"]);
	$connection->useDB($radius_db["db"]);
	$statement = "update radacct set acctstoptime=FROM_UNIXTIME(UNIX_TIMESTAMP(acctstarttime)+acctsessiontime),acctterminatecause='program' where acctstoptime is null and username not in(select username from userinfo where server!='off')";
	$connection->query($statement);
	$num = mysql_affected_rows();
	if($num != 0)
	{
        	$head = "logoutcheck.php";
        	$content = "reset {$num} users";
        	mylog($head,$content);
	}

	sleep(UPDATE_PART_SLEEP_TIME);
}
?>

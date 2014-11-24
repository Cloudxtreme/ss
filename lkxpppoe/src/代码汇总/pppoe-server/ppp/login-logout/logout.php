<?php
require_once("/etc/ppp/config/pppoe-config.php");
require_once("/etc/ppp/pppoe-recordupdate/killmark.php");
require_once("/etc/ppp/config/share/now.php");
$database = $config->configs["radius-db"];
$pppoe_server = $config->configs["pppoe-server"];

if($argc!=2)
{
	//exec default limit
	return;
}
openlog("logout",  LOG_ODELAY, LOG_LOCAL3);
$username = $argv[1];

syslog(LOG_INFO, "mysql-userinfo-off:[{$username}]");
//echo getNowTime()." [{$argv[0]}]:"."{$username} logout\n";

$result = mysql_pconnect($database["server"],$database["username"],$database["password"]) or die("Could not connect: " . mysql_error());
mysql_select_db($database["db"]);
$result = mysql_query("update userinfo set `server`='off',`pid`='0' where username='{$username}'");
closelog();
?>

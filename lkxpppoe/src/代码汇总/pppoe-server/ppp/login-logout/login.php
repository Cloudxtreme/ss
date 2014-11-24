<?php
require_once("/etc/ppp/config/pppoe-config.php");
require_once("/etc/ppp/config/share/mail/mail.php");
require_once("/etc/ppp/config/share/now.php");

$radius_db = $config->configs["radius-db"];
$pppoe_server = $config->configs["pppoe-server"];
   

if($argc!=3)
{
	//exec default limit
	return;
}
openlog("login",  LOG_ODELAY, LOG_LOCAL3);
$username = $argv[1];
$pid = $argv[2];
$server = $pppoe_server["pppoe_server"];

syslog(LOG_INFO, "mysql-userinfo-on:[{$username}->{$server}->{$pid}]");
//echo getNowTime()." [{$argv[0]}]:"."{$username} login\n";

$result = mysql_pconnect($radius_db["server"],$radius_db["username"],$radius_db["password"]);
if(!$result)
{
        echo "Could not connect: " . mysql_error()."\n";
        $title = "pppoe-server[{$server}]";
        $content = "pppoe-server[{$server}] <p>";
        $content .= "{radius:{$radius_db["server"]}\n <p>";
        $content .= "Could not connect: " . mysql_error()."\n <p>";
        sendmail($title,$content);
}
mysql_select_db($radius_db["db"]);
$result = mysql_query("update userinfo set `server`='{$server}',`pid`='{$pid}' where username='{$username}'");
closelog();
?>

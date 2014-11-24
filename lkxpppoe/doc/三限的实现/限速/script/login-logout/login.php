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

$username = $argv[1];
$pid = $argv[2];
$server = $pppoe_server["pppoe_server"];

echo getNowTime()." [{$argv[0]}]:"."{$username} login\n";

$result = mysql_pconnect($radius_db["server"],$radius_db["username"],$radius_db["password"]);
if(!$result)
{
        echo "Could not connect: " . mysql_error()."\n";
        $content = "pppoe-server[{$server}] <p>";
        $content .= "{radius:{$radius_db["server"]}\n <p>";
        $content .= "Could not connect: " . mysql_error()."\n <p>";
        sendmail($content);
}
mysql_select_db($radius_db["db"]);
$result = mysql_query("update userinfo set `server`='{$server}',`pid`='{$pid}' where username='{$username}'");

?>

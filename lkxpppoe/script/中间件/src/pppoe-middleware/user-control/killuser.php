<?php
//process the adduser command
//listen on the 
require_once("/etc/ppp/config/share/httpsqs_client.php");
require_once("/etc/ppp/pppoe-recordupdate/killmark.php");
require_once("/etc/ppp/config/share/now.php");
require_once("/etc/ppp/config/share/mail/mail.php");
require_once("/etc/ppp/config/pppoe-config.php");
require_once("/etc/ppp/libpro/libpro.php");

$pppoe_server = $config->configs["pppoe-server"]["pppoe_server"];

define("QUENE_NAME","pppoe_mgr_user_kil");
define("QUENE_SERVER","127.0.0.1");
define("QUENE_PORT","12001");
define("SLEEP_TIME",100);

prostart("killuser",getmypid());
$httpsqs = new httpsqs(QUENE_SERVER,QUENE_PORT);
while(1)
{
	$info = $httpsqs->get(QUENE_NAME);
	if("HTTPSQS_GET_END" == $info)
	{
		usleep(SLEEP_TIME);
	}
	else
	{	
		$object = json_decode($info,true);
		if(!isset($object["pid"]) || !isset($object["username"]))
		{
			continue;
		}
		$pid = $object["pid"];
		$username = $object["username"];
		echo getNowTime()." [{$argv[0]}]:"."kill user[{$username}] with pid[{$pid}]\n";
		$content = "pppoe-server-kill alerm<p>";
		$content .= "pppoe-server:[{$pppoe_server}]<p>";
		$content .= "username:[{$username}]<p>";
		$content .= "pid:[{$pid}]<p>";
		sendmail($content);
		if($pid<=1)
		{
			//echo "recieve error pid [{$pid}]\n";
		}
		else
		{
			//echo "ready to kill pid[{$pid}]\n";
			system("kill -9 {$pid}");
			//echo "kill pid {$pid}\n";
			update($username);
			system("php /etc/ppp/login-logout/auth-down.php {$username} {$pid}");
			//system("php /etc/ppp/login-logout/logout.php {$username}");
			//system("php /etc/ppp/pppoe-recordupdate/userdown.php {$username}");
		}
	}
	usleep(100000); //0.1 second
}
?>



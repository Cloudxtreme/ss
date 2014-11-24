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

openlog("kill-user",  LOG_ODELAY, LOG_LOCAL3);

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
		$pid = trim($object["pid"]);
		$username = trim($object["username"]);
		
		$ppp = check_pid_username($pid,$username);
		if($ppp != false && $ppp != "")
		{
			system("kill -9 {$pid}");
			system("/etc/ppp/login-logout/unregister.php {$username}");
			update($username);
			syslog(LOG_INFO, "kill-succ {$username}->{$pid}");
		}
		else
		{
			syslog(LOG_INFO, "kill-fail {$username}->{$pid}");
		}
	}
	usleep(100000); //0.1 second
}

closelog();

function check_pid_username($pid,$username)
{
	$records = array();
	$db = new SQLite3(SQLITE_DB_FILE);
	$results = $db->query("select ppp from pppoe where status='true' and username='{$username}' and pid='{$pid}'");
	if(!$results || !($row = $results->fetchArray()))
	{
		$db->close();
		return false;
	}
	
	$db->close();
	return $row["ppp"];
}
?>



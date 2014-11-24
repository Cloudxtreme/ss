<?php
//process the adduser command
//listen on the 
require_once("/etc/ppp/config/share/httpsqs_client.php");
require_once("/etc/ppp/pppoe-recordupdate/killmark.php");
require_once("/etc/ppp/config/share/now.php");
require_once("/etc/ppp/libpro/libpro.php");
require_once("/etc/ppp/login-logout/getPPP.php");


define("PING_FILE","/etc/ppp/login-logout/ping");
$pppoe_server = $config->configs["pppoe-server"]["pppoe_server"];
define("QUENE_NAME","pppoe_mgr_user_mul");
define("QUENE_SERVER","127.0.0.1");
define("QUENE_PORT","12001");
define("SLEEP_TIME",100000);

openlog("handle_mul",  LOG_ODELAY, LOG_LOCAL3);
prostart("handle_mul_login",getmypid());
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
		echo "$info\n";
		$parts = explode('-',$info);
		if($parts[1] !== "MUL")
		{
			continue;
		}
		
		$username = $parts[0];
		$records = getRecords($username);
		if(count($records)>0)
		{
			print_r($records);
		}
		else
		{
			continue;
		}
		foreach($records as $ppp => $info)
		{
			$timeout = getTimeout($info["ipaddr"]);
			if($timeout!==false && ((int)$timeout)>60 && !ping($info["ipaddr"]))
			{
				$username = $info["username"];	
				$pid = $info["pid"];
				if(((int)$pid)>1)
				{
					system("kill -9 {$pid}");
					syslog(LOG_INFO, "kill-succ {$username}->{$pid}");
					system("php /etc/ppp/login-logout/unregister.php {$ppp}");
					update($username);
				}
			}
			else
			{
				syslog(LOG_INFO, "user[{$username}] ping still alive\n");
			}
		}
	}
	usleep(SLEEP_TIME); //0.1 second
}
closelog();

function getRecords($username)
{
	$records = array();
	$db = new SQLite3(SQLITE_DB_FILE);
	$results = $db->query("select * from pppoe where status='true' and username='{$username}'");
	if(!$results)
	{
		return false;
	}
	while($row = $results->fetchArray())
	{
		$ppp = $row["ppp"];
		$records[$ppp] = array();
		$records[$ppp]["pid"] = $row["pid"];
		$records[$ppp]["ipaddr"] = $row["ipaddr"];
		$records[$ppp]["username"] = $row["username"];
	}
	$db->close();
	return $records;
}

function ping($ipaddr)
{
	global $ipaddrweignts;
	$count = 0;
	for($i = 0;$i<4;$i++)
	{
		usleep(100);
		system(PING_FILE." {$ipaddr}", $result);
		if($result == 0)
		{
			$count++;
		}
	}
	if($count < 2)
	{
		return false;
	}
	else
	{
		return true;
	}
}

function getTimeout($input)
{
	$server = '127.0.0.1';
	$port = 9999;
	
	if(!($sock = socket_create(AF_INET, SOCK_DGRAM, 0))) {
	    $errorcode = socket_last_error();
	    $errormsg = socket_strerror($errorcode);
	    die("Couldn't create socket: [$errorcode] $errormsg \n");
	}
	
    if( ! socket_sendto($sock, $input , strlen($input) , 0 , $server , $port)) {

        $errorcode = socket_last_error();

        $errormsg = socket_strerror($errorcode);

        die("Could not send data: [$errorcode] $errormsg \n");

    }

    if(socket_recv ( $sock , $reply , 1024 , 0 ) !== FALSE) 
    {
        return trim($reply);
    }
    return false;
}
?>



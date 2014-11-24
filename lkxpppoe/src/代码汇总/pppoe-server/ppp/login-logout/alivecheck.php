<?php
require_once("/etc/ppp/config/share/httpsqs_client.php");
define("PING_FILE","/etc/ppp/login-logout/ping");
define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");
define("QUENE_SERVER","localhost");
define("QUENE_PORT","12001");
define("QUENE_NAME","pppoe_mgr_user_kil");


$records = getRecords();
foreach($records as $ppp => $info)
{
	if($info["pid"] == "" || $info["username"] == "")
	{
		system("php /etc/ppp/login-logout/unregister.php {$ppp}");
		return;
	}

	if(!check($info["ipaddr"]))
		if(!check($info["ipaddr"]))
			if(!check($info["ipaddr"]))
				if(!check($info["ipaddr"]))
				{
					kill($info["pid"],$info["username"]);
					echo "kill progress [{$info["pid"]}]\n";
					system("php /etc/ppp/login-logout/unregister.php {$ppp}");
					
				}
}


function kill($pid,$username)
{
	$object["pid"] = $pid;
	$object["username"] = $username;
	$info = json_encode($object);
	$pppoeserver = new httpsqs(QUENE_SERVER,QUENE_PORT);
	$result = $pppoeserver->put(QUENE_NAME,$info);
}

function getRecords()
{
	$records = array();
	$db = new SQLite3(SQLITE_DB_FILE);
	$results = $db->query("select * from pppoe where status='true'");
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

function check($ipaddr)
{
	$result = 0;
	system(PING_FILE." {$ipaddr}", $result);
	if($result == 0)
	{
		return true;
	}
	else
	{
		return false;
	}
}

?>

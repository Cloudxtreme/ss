<?php
ini_set('max_execution_time','0');
require_once("/etc/raddb/config/share/mysqlconnection.php");
require_once("/etc/raddb/config/radius-config.php");
require_once("/etc/raddb/libpro/libpro.php");
require_once("/etc/raddb/config/share/now.php");
require_once("/etc/raddb/config/share/log.php");
require_once("/etc/raddb/libpro/libpro.php");
define("LOG_FILE","/var/log/radius-log.log");


$radius_db = $config->configs["radius-db"];
$center_db = $config->configs["center-db"];


define("UPDATE_ALL_SLEEP_TIME",3600);
define("UPDATE_PART_SLEEP_TIME",10);


function getUserStatus()
{
	global $radius_db;
	$users = array();
	$connection = new mysqlconnection($radius_db["server"],$radius_db["username"],$radius_db["password"]);
	$connection->useDB($radius_db["db"]);
	$statement = "select `username` from `userinfo` where server != 'off'";
	$result = $connection->query($statement);
	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$users[$row["username"]] = true;
	}
	return $users;
}

function getUserStatusPage()
{
	$users = array();
	global $center_db;
	$manage = new mysqlconnection($center_db["server"],$center_db["username"],$center_db["password"]);
	$manage->useDB($center_db["db"]);
	$statement = "select user from client where online='on'";
	$result = $manage->query($statement);
	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$users[$row["user"]] = true;
	}
	return $users;
}

function compareUserStatus($old,$new)
{
	$differs = array();
	foreach($new as $username=>$status)
	{
		if(!isset($old[$username]))
		{
			$differs[$username] = "on";
		}
	}
	
	foreach($old as $username=>$status)
	{
		if(!isset($new[$username]))
		{
			$differs[$username] = "off";
		}
	}
	return $differs;
}

function updateUser($username,$status)
{
	global $center_db;
	$manage = new mysqlconnection($center_db["server"],$center_db["username"],$center_db["password"]);
	$manage->useDB($center_db["db"]);
	$statement = "update `client` set `online`='{$status}' where `user`='{$username}'";
	$manage->query($statement);
	$head = "synuser.php";
    $content = "update center database user[{$username}] status to :{$status}";
    mylog($head,$content);
}

function updateUsers($users)
{
	foreach($users as $username=>$status)
	{
		updateUser($username,$status);
		usleep(1);
	}
}

prostart("synuser",getmypid());

try
{
	//prostart("synuser",getmypid());
	

	while(true)
	{
		$differs = array();
		$news = getUserStatus();
		$olds = getUserStatusPage();

		$differs = compareUserStatus($olds,$news);
		updateUsers($differs);
		sleep(UPDATE_PART_SLEEP_TIME);
	}
}
catch (Exception $e) 
{
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}


?>

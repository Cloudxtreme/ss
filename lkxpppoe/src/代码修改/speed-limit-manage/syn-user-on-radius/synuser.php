<?php
ini_set('max_execution_time','0');
require_once("/etc/raddb/config/share/mysqlconnection.php");
require_once("/etc/raddb/config/radius-config.php");

require_once("/etc/raddb/config/share/now.php");
require_once("/etc/raddb/config/share/log.php");
require_once("/etc/raddb/libpro/libpro.php");
define("LOG_FILE","/var/log/radius-log.log");


$radius_db = $config->configs["radius-db"];
$center_db = $config->configs["center-db"];


define("UPDATE_ALL_SLEEP_TIME",3600);
define("UPDATE_PART_SLEEP_TIME",5);


function getUserStatus()
{
	global $radius_db;
	$users = array();
	$connection = new mysqlconnection($radius_db["server"],$radius_db["username"],$radius_db["password"]);
	$connection->useDB($radius_db["db"]);
	$statement = "select `username`,`server` from `userinfo`";
	$result = $connection->query($statement);
	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$username = $row["username"];
		$status = $row["server"];
		if($status != "off")
		{
			$status = "on";
		}
		$users[$username] = $status;
	}
	return $users;
}

function compareUserStatus($old,$new)
{
	$differs = array();
	foreach($new as $username=>$status)
	{
		if(!isset($old[$username]) || $status != $old[$username])
		{
			$differs[$username] = $status;
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


try
{
//	prostart("synuser",getmypid());
	$news = getUserStatus();
	$olds = $news;
	$differs = array();

	updateUsers($news);
	$time = UPDATE_ALL_SLEEP_TIME/UPDATE_PART_SLEEP_TIME;
	$counter = 0;
	while(true)
	{
		$news = getUserStatus();
		if($counter == $time)
		{
			updateUsers($news);
			$counter = 0;
		}
		else
		{
			$differs = compareUserStatus($olds,$news);
			updateUsers($differs);
		}
		$olds = $news;
		$counter++;
		sleep(UPDATE_PART_SLEEP_TIME);
	}
}
catch (Exception $e) 
{
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}


?>

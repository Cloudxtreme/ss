<?php
ini_set('max_execution_time','0');
require_once("mysqlconnection.php");

define("LOCAL_DB_SERVER","localhost");
define("LOCAL_DB_USERNAME","root");
define("LOCAL_DB_PASSWORD","rjkj@rjkj");
define("LOCAL_DB_DB","radius");

define("UPDATE_ALL_SLEEP_TIME",3600);
define("UPDATE_PART_SLEEP_TIME",5);


function getUserStatus()
{
	$users = array();
	$connection = new mysqlconnection(LOCAL_DB_SERVER,LOCAL_DB_USERNAME,LOCAL_DB_PASSWORD);
	$connection->useDB(LOCAL_DB_DB);
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
	$manage = new mysqlconnection(DB_SERVER,DB_USERNAME,DB_PASSWORD);
	$manage->useDB(DB_DB);
	$statement = "update `client` set `online`='{$status}' where `user`='{$username}'";
	$manage->query($statement);
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
			$ocunter = 0;
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

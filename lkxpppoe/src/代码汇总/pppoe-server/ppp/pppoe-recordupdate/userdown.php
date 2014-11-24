<?php
require_once("/etc/ppp/config/share/mysqlconnection.php");
require_once("/etc/ppp/pppoe-recordupdate/mysqlquery.php");
require_once("/etc/ppp/config/pppoe-config.php");
require_once("/etc/ppp/config/share/now.php");

$radius_db = $config->configs["radius-db"];
$center_db = $config->configs["center-db"];

if($argc != 2)
{
	return;
}

$username = $argv[1];

$record = getRecord($username);
uploadRecord($record);
echo getNowTime()."add record to center database for user[{$username}]\n";

function getRecord($username)
{
	global $radius_db;
	$record = array();
	$connection = new mysqlconnection($radius_db["server"],$radius_db["username"],$radius_db["password"]);
	$connection->useDB($radius_db["db"]);
	$statement = "select `username`,`acctstarttime`,`acctstoptime`,`acctsessiontime`,`acctinputoctets`,`acctoutputoctets`,`framedipaddress`  from `radacct` where `username`='{$username}' group by `acctstoptime` desc limit 1";
	$result = $connection->query($statement);
	if($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$record["username"] = $row["username"];
		$record["acctstarttime"] = $row["acctstarttime"];
		$record["acctstoptime"] = $row["acctstoptime"];
		$record["acctsessiontime"] = $row["acctsessiontime"];
		$record["acctinputoctets"] = $row["acctinputoctets"];
		$record["acctoutputoctets"] = $row["acctoutputoctets"];
		$record["framedipaddress"] = $row["framedipaddress"];
		return $record;
	}
	else
	{
		return false;
	}
}

function uploadRecord($record)
{
	//print_r($record);
	global $center_db;
	if(!$record)
	{
		return;
	}
	global $center_db;
	$serverip = $center_db["server"];
	$connection = new mysqlconnection($center_db["server"],$center_db["username"],$center_db["password"]);
	$connection->useDB($center_db["manage_db"]);
	$statement = "select `id` from `radiusInfo` where `serverip`='{$serverip}'";
	echo $statement."\n";
	$result = $connection->query($statement);
	if($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$id = $row["id"];
		echo $id."\n";
		$connection->useDB($center_db["record_db"]);
		$statement = "insert into `{$id}`(`username`,`logintime`,`logouttime`,`usertime`,`uploadoctets`,`downloadoctets`,`address`) values('{$record["username"]}','{$record["acctstarttime"]}','{$record["acctstoptime"]}','{$record["acctsessiontime"]}','{$record["acctinputoctets"]}','{$record["acctoutputoctets"]}','{$record["framedipaddress"]}')";
		$query = new mysqlquery();
		$query->query($statement);
	}
}
?>

<?php
require_once("mysqlconnection.php");
define("LOCAL_DB_SERVER","192.168.22.6");
define("LOCAL_DB_USERNAME","root");
define("LOCAL_DB_PASSWORD","rjkj@rjkj");
define("LOCAL_DB_DB","radius");

define("PPPOE_QUENE_PORT",12001);
define("PPPOE_QUENE_NAME","pppoe_mgr_user_kil");

function checktime($username,$timelimit,$begin)
{
	$manage = new mysqlconnection(LOCAL_DB_SERVER,LOCAL_DB_USERNAME,LOCAL_DB_PASSWORD);
	$manage->useDB(LOCAL_DB_DB);
	$statement = "SELECT SUM(acctsessiontime - GREATEST((UNIX_TIMESTAMP({$begin}) - UNIX_TIMESTAMP(acctstarttime)), 0)) as usetime FROM radacct WHERE username='{$username}' AND UNIX_TIMESTAMP(acctstarttime) + acctsessiontime > UNIX_TIMESTAMP({$begin})";
	$result = $manage->query($statement);
	$usetime = 0;
	if($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$usetime = $row["usetime"];
	}
	return ($timelimit-$usetime);
}

function checktraffic($username,$trafficlimit,$begin)
{
	$manage = new mysqlconnection(LOCAL_DB_SERVER,LOCAL_DB_USERNAME,LOCAL_DB_PASSWORD);
	$manage->useDB(LOCAL_DB_DB);
	$statement = "SELECT SUM(acctinputoctets + acctoutputoctets) as usetraffic FROM radacct WHERE UserName='{$username}' AND UNIX_TIMESTAMP(AcctStartTime) > UNIX_TIMESTAMP({$begin})"; 
	$result = $manage->query($statement);
	$traffic = 0;
	if($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$traffic = $row["usetraffic"];
	}
	//echo "traffic:{$traffic}\n";
	return ($trafficlimit-$traffic);
}

function checklimit($username,$timelimit,$trafficlimit,$begin)
{
	$time = false;
	$traffic = false;
	if($timelimit == 0)
	{
		$time = true;
	}
	else
	{
		$check = checktime($username,$timelimit,$begin);
		if($check>0)
		{
			$time = true;
		}
		else
		{
			$time = false;
		}
	}

	if($trafficlimit == 0)
	{
		$traffic = true;
	}
	else
	{
		$check = checktraffic($username,$trafficlimit,$begin);
		if($check>0)
		{
			$traffic = true;
		}
		else
		{
			$traffic = false;
		}
	}
	$statement = "";
	if($time && $traffic)
	{
		$statement = "update userdate set useup='false' where username='{$username}'";
	}
	else
	{
		$statement = "update userdate set useup='true' where username='{$username}'";
	}
	$manage = new mysqlconnection(LOCAL_DB_SERVER,LOCAL_DB_USERNAME,LOCAL_DB_PASSWORD);
	$manage->useDB(LOCAL_DB_DB);
	$result = $manage->query($statement);
	
}

function check()
{
	$manage = new mysqlconnection(LOCAL_DB_SERVER,LOCAL_DB_USERNAME,LOCAL_DB_PASSWORD);
	$manage->useDB(LOCAL_DB_DB);
	$statement = "select username,timeLimit,trafficLimit from userinfo"; 
	$result = $manage->query($statement);
	$infos = array();
	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$username = $row["username"];
		$infos[$username] = array();
		$infos[$username]["time"] = $row["timeLimit"];
		$infos[$username]["traffic"] = $row["trafficLimit"];
	}
	//print_r($infos);	
	$monthstart = "";
	$statement = "SELECT concat(date_format(LAST_DAY(now()),'%Y-%m-'),'01') as monthstart";
	$result = $manage->query($statement);
	if($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$monthstart = $row["monthstart"];
	}
	else
	{
		return;
	}

	foreach($infos as $username => $info)
	{
		checklimit($username,$info["time"],$info["traffic"],$monthstart);
	}
}

check();

?>

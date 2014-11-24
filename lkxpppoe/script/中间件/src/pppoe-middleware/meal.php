<?php
require_once("/root/config/share/mysqlconnection.php");

class Meal
{
//	public $startDate = -1;
//	public $endDate = -1;

	public $uploadSpeedLimit = 0;
	public $downloadSpeedLimit = 0;

	public $secondPerMonth = 0;

	public $octetsPerMonth = 0;

	private $connection;

	const MATH_MILLION = 131072;	//1024*1024/8
	const MATH_GIBI = 134217728;	//1024*1024*1024/8
	const MATH_HOUR = 3600;

//	const ATTRIBUTE_DATE = "LOGIN-END-TIME";
	const ATTRIBUTE_TIME = "Max-Monthly-Time";
	const ATTRIBUTE_TRAFFIC = "Max-Monthly-Traffic";
	
	function __construct($connection)
	{
		$this->connection = $connection;
	}


	//do nothing about the exception throw by mysql
	public function contructbyid($id)
	{
		return  $this->getInfoById($id);
	}

	private function getInfoById($id)
	{
//		$roleDateId = -1;
		$roleSpeedId = -1;
		$roleTimeId = -1;
		$roleTrafficId = -1;

		$statement = "select `roleSpeed`,`roleTime`,`roleTraffic` from `mealinfo` where `id`={$id}";
		$result = $this->connection->query($statement);
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
//			$roleDateId = $row["roelDate"];
			$roleSpeedId = $row["roleSpeed"];
			$roleTimeId = $row["roleTime"];
			$roleTrafficId = $row["roleTraffic"];
		}
		else
		{
			return false;
		}
		
//		$this->getDateById($roleDateId);
		$this->getSpeedById($roleSpeedId);
		$this->getTimeById($roleTimeId);
		$this->getTrafficById($roleTrafficId);
		return true;
	}
/*
	private function getDateById($id)
	{
		$statement = "select startdate,enddate from roleDate where id={$id}";
		$result = $this->connection->query($statement);
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$this->startDate = $row["startdate"];
			$this->endDate = $row["enddate"];
		}
	}
*/
	private function getSpeedById($id)
	{
		$statement = "select `limit` from `roleSpeed` where `id`={$id}";
		$result = $this->connection->query($statement);
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$this->downloadSpeedLimit = $row["limit"]*1024;	//turn mbit/s to kbit/s
			$this->uploadSpeedLimit = $this->downloadSpeedLimit/2;
		}
	}

	private function getTimeById($id)
	{
		$statement = "select `limit` from `roleTime` where `id`={$id}";
		$result = $this->connection->query($statement);
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$hoursPerMonth = $row["limit"];
			$this->secondPerMonth = $hoursPerMonth*self::MATH_GIBI;
		}
	}
	
	private function getTrafficById($id)
	{
		$statement = "select `limit` from `roleTraffic` where `id`={$id}";
		$result = $this->connection->query($statement);
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$this->octetsPerMonth = $row["limit"]*self::MATH_GIBI;
		}
	}
	

/*	public function dataLimit($username)
	{	
		if(($this->startDate==-1) || ($this->endDate==-1))
		{
			return;
		}

		$begin = getTime($this->startDate);
		$now = time();
		if($now>=$begin)
		{
			doBegin($username);
		}
		else
		{
			waitBging($username);
		}
	}
*/

	public function updateLimit($username,$connection)
	{
		$statement = "update `userinfo` set `uploadLimit`='{$this->uploadSpeedLimit}',`downloadLimit`='{$this->downloadSpeedLimit}',`timeLimit`='{$this->secondPerMonth}',`trafficLimit`='{$this->octetsPerMonth}' where `username`='{$username}'";
		$result = $connection->query($statement);
	}
/*
	public function speedLimit($username,$connection)
	{
		if($this->downloadSpeedLimit==-1)
		{
			return;
		}
		
		$statement = "update `userinfo` set `uploadLimit`='{$this->uploadSpeedLimit}',`downloadLimit`='{$this->downloadSpeedLimit}' where `username`='{$username}'";
		$result = $connection->query($statement);
	}

	public function timeLimit($username,$connection)
	{
		if($this->hoursPerMonth == -1)
		{
			return;
		}
		$attribute = self::ATTRIBUTE_TIME;
		$groupname = "TIME_LIMIT_{$this->hoursPerMonth}_H";
		$hoursPerMonth = $this->hoursPerMonth*self::MATH_HOUR;
		$result = $this->groupExist($groupname,$connection);
		if(!$result)
		{
			$statement = "insert into `radgroupcheck` values(null,'{$groupname}','{$attribute}',':=','{$hoursPerMonth}')";
			$result = $connection->query($statement);
		}
		$statement = "insert into `radusergroup` values('{$username}','{$groupname}',1)";
		$result = $connection->query($statement);
	}

	public function trafficLimit($username,$connection)
	{
		if($this->octetsPerMonth==-1)
		{
			return;
		}
		$attribute = self::ATTRIBUTE_TRAFFIC;
		$groupname = "TIME_LIMIT_{$this->octetsPerMonth}_G";
		$hoursPerMonth = $this->octetsPerMonth*self::MATH_GIBI;
		$result = $this->groupExist($groupname,$connection);
		if(!$result)
		{
			$statement = "insert into `radgroupcheck` values(null,'{$groupname}','{$attribute}',':=','{$hoursPerMonth}')";
			$result = $connection->query($statement);
		}

		$statement = "insert into `radusergroup` values('{$username}','{$groupname}',1)";
		$result = $connection->query($statement);
	}
*/
/*
private function getTime($timestring)
{
$array = explode(" ",$timestring);
$timestring = $array[0];

$array = explode("-",$timestring);
$yesr = $array[0];
$month = $array[1];
$date = $array[2];
		
return mktime(0, 0, 0, $month,$date,$year);
}

private function doBegin($username,$connection)
{
$attribute = self::ATTRIBUTE_DATE;
$statement = "delete from `radcheck` where `username`='{$username}'";
$result = $connection->query($statement);

$statement = "insert into `radcheck` values(null,'{$username}','{$attribute}',':=','{$this->endDate}')";
$result = $connection->query($statement);
}

private function waitBegin($username,$connection)
{
$attribute = self::ATTRIBUTE_DATE;
$statement = "delete from `radcheck` where `username`='{$username}'";
$result = $connection->query($statement);
$statement = "insert into `radcheck` values(null,'{$username}','{$attribute}',':=','2000-01-01')";
$result = $connection->query($statement);
}
	
	private function groupExist($groupname,$connection)
	{
		$statement = "select groupname from `radgroupcheck` where `groupname`='{$groupname}'";
		$result = $connection->query($statement);
		return mysql_num_rows($result); 
	}
*/
}

?>

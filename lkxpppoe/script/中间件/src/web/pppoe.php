<?php
	include_once("/root/config/mid-config.php");
	include_once("/root/config/share/mysqlconnection.php");

	if(!isset($_GET["action"]))
	{
		echo "ARGUMENT_ERROR\n";
		return;
	}
	$action = $_GET["action"];
	switch($action)
	{
		case "usercheck":
			if(!isset($_GET["username"]))
			{
				echo "ARGUMENT_ERROR";
				return;
			}
			$center_db = $config->configs["center-db"];
			$username = $_GET["username"];
			usercheck($username,$center_db); 
			break;
		default:
			break;	
	}
	
	function usercheck($username,$center_db)
	{
		$center = new mysqlconnection($center_db["server"],$center_db["username"],$center_db["password"]);
		$center->useDB($center_db["db"]);
		$statement = "select radiusId from client where user='{$username}'";
		$radiusid = 0;
		$result = $center->query($statement);
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$radiusid = $row["radiusId"];
		}
		else
		{
			echo "ARGUMENT_ERROR";
    	return;
		}
		$statement = "select serverip,dbname,username,password from radiusInfo where id={$radiusid}";
		$result = $center->query($statement);
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))
    {
    	$radius = new mysqlconnection($row["serverip"],$row["username"],$row["password"]);
			$radius->useDB($row["dbname"]);
			$statement = "select username from userdate where username='{$username}'";
			$result = $radius->query($statement);
			$num = mysql_num_rows($result);
			if($num<=0)
			{
				//user no found
				echo "USER_NOT_EXIST";
    		return;
			}
			$statement = "select exceed,useup,forbidden  from userdate where username='{$username}' and now()>begin and now()<end";
			$result = $radius->query($statement);
			if($row = mysql_fetch_array($result, MYSQL_ASSOC))
      {
      	$exceed = $row["exceed"];
      	$useup = $row["useup"];
      	$forbidden = $row["forbidden"];
      	if($exceed == "true")
      	{
      		echo "USER_EXCEED";
    			return;
      	}
      	
      	if($useup == "true")
      	{
      		echo "USER_USEUP";
    			return;
      	}
      	
      	if($forbidden == "true")
      	{
      		echo "USER_FORBIDDEN";
    			return;
      	}
      	echo "OK";
    		return;
      }
      else
      {
      	//meal use up
      	echo "USER_USEUP";
    		return;
      }
    }
    else
    {
    	echo "RADIUS_NOT_EXIST";
    	return;
    }
	}
?>

<?php
define("SQLITE_DB_FILE","sqlite:/root/libpro/pppoe.db");

function prostart($programn,$pid)
{
	try
	{
		$lite = new PDO(SQLITE_DB_FILE);
		$lite->exec("update programn set pid='{$pid}',status='on' where name='{$programn}'");
	}
	catch(PDOException $e)
	{
		echo 'Connection failed: ' . $e->getMessage();
	}
}

function procheck()
{
	$programns = array();
	
	try
	{
		$lite = new PDO(SQLITE_DB_FILE);
		$result = $lite->query("select name,pid,command from programn where status='on'");
		foreach ($result as $row) 
		{
	        $programns[$row["name"]]["pid"] = $row["pid"];
			$programns[$row["name"]]["command"] = $row["command"];
    	}
	}
	catch(PDOException $e)
	{
		$programns = array();
		echo 'Connection failed: ' . $e->getMessage();
	}	
  	
  	foreach($programns as $name=>$prog)
  	{
	  	if(!checkProPid($prog["pid"]))
	  	{
	  		echo getNowTime()." programn[{$name}] is download\n";
	  		$content = "programn[{$name}] is download<p>";
	      	$content .= "command:{$prog['command']}";
	      	sendmail($content);
			setoff($name);
	      	system($prog['command']);
	  	}
	  	
  	}
}

function checkProPid($pid)
{
	$filename = "/proc/{$pid}/stat";
	if(file_exists($filename) )
	{
	 	$content = file_get_contents($filename);
	 	if(strpos($content,"php") !== FALSE)
	 	{
	 		return TRUE;
	 	}
	}
	return false;
}

function setoff($name)
{
	try
	{
		$lite = new PDO(SQLITE_DB_FILE);
		$lite->exec("update programn set status='off' where name='{$name}'");
	}
	catch(PDOException $e)
	{
		echo 'Connection failed: ' . $e->getMessage();
	}	
}
?>

<?php
require_once("/etc/ppp/config/pppoe-config.php");
define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");

function prostart($programn,$pid)
{
	$lite = new SQLite3(SQLITE_DB_FILE);
	$lite->query("update programn set pid='{$pid}',status='on' where name='{$programn}'");
	$lite->close();
}

function procheck()
{
	global $config;
	$servers = $config->configs["pppoe-server"];
	
	$programns = array();
  	$lite = new SQLite3(SQLITE_DB_FILE);
  	$results = $lite->query("select name,pid,command from programn where status='on'");
  	while($row = $results->fetchArray())
  	{
		$programns[$row["name"]]["pid"] = $row["pid"];
		$programns[$row["name"]]["command"] = $row["command"];
  	}
  	$lite->close();
  	foreach($programns as $name=>$prog)
  	{
	  	if(!checkProPid($prog["pid"]))
	  	{
	  		echo getNowTime()." programn[{$name}] is download\n";
	  		$title = "pppoe-server[{$servers['pppoe_server']}]";
	  		$content = "programn[{$name}] is download<p>";
	      	$content .= "command:{$prog['command']}";
	      	sendmail($title,$content);
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
	$lite = new SQLite3(SQLITE_DB_FILE);
	$results = $lite->query("update programn set status='off' where name='{$name}'");
	$lite->close();
}
?>

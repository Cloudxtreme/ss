<?php
   

define("DB_SERVER","192.168.22.135");
define("DB_USERNAME","root");
define("DB_PASSWORD","rjkj@rjkj");
define("DB_DB","radius");

if($argc!=3)
{
	//exec default limit
	return;
}

$eth = $argv[1];
$username = $argv[2];
$uploadspeed = 0;
$downloadspeed = 0;

$result = mysql_pconnect(DB_SERVER,DB_USERNAME,DB_PASSWORD) or die("Could not connect: " . mysql_error());
mysql_select_db(DB_DB);
$result = mysql_query("select uploadLimit,downloadLimit from userinfo where username='{$username}'");
if($row = mysql_fetch_array($result,MYSQL_ASSOC)) 
{
	$uploadspeed = $row["uploadLimit"];
	$downloadspeed = $row["downloadLimit"];
}
mysql_free_result($result);

//echo $uploadspeed."\n";
//echo $downloadspeed."\n";

limit($eth,$username,$uploadspeed,$downloadspeed);

function limit($eth,$username,$upload=0,$download=0)
{
	$command = "/etc/ppp/speed-limit/speedLimit -S {$eth} -n {$username} -u {$upload} -d {$download}";
	system($command);
}
?>

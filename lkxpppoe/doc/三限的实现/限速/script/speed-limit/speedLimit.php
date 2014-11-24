<?php
require_once("/etc/ppp/config/share/now.php");   
require_once("/etc/ppp/config/pppoe-config.php");
$database = $config->configs["radius-db"];
$radius_db = $config->configs["radius-db"];
$center_db = $config->configs["center-db"];
$speed_limit = $config->configs["speed-limit"];
$factor = $speed_limit["limit_factor"];

if($argc!=3)
{
	//exec default limit
	return;
}

$eth = $argv[1];
$username = $argv[2];
$uploadspeed = $speed_limit["default_upload"];
$downloadspeed = $speed_limit["default_download"];

$result = mysql_pconnect($radius_db["server"],$radius_db["username"],$radius_db["password"]) or die("Could not connect: " . mysql_error());
mysql_select_db($radius_db["db"]);
$result = mysql_query("select uploadLimit,downloadLimit from userinfo where username='{$username}'");
if($row = mysql_fetch_array($result,MYSQL_ASSOC)) 
{
	$uploadspeed = $row["uploadLimit"];
	$downloadspeed = $row["downloadLimit"];
}
mysql_free_result($result);


if($downloadspeed<$speed_limit["default_upload"] || $uploadspeed<$speed_limit["default_download"])
{
	$uploadspeed = $speed_limit["default_upload"];
	$downloadspeed = $speed_limit["default_download"];
}

echo getNowTime()." [{$argv[0]}]:"."speed limit to user[{$username}] with {$downloadspeed}kbit/s\n";

$uploadspeed = ceil($uploadspeed*$factor);
$downloadspeed = ceil($downloadspeed*$factor);

$ipaddrs = array();
mysql_pconnect($center_db["server"],$center_db["username"],$center_db["password"]) or die("Could not connect: " . mysql_error());
mysql_select_db($center_db["manage_db"]);
$result = mysql_query("select ipaddr from  nonespeedlimit");
while($row = mysql_fetch_array($result,MYSQL_ASSOC))
{
	$ipaddrs[] = $row["ipaddr"];
}
mysql_free_result($result);

limit($eth,$uploadspeed,$downloadspeed,$ipaddrs);

function limit($eth,$upload,$download,$ipaddrs)
{
	$inter_download = $download*4;
//	echo $eth."\n";
//	echo $upload."\n";
//	echo $download."\n";
//	echo $inter_download."\n";

//	var_dump($ipaddrs);
	$r2q = (int)$download/1.5;

	system("/sbin/tc qdisc del dev {$eth} root");
	system("/sbin/tc qdisc del dev {$eth} ingress");
	//system("/sbin/tc qdisc add dev $eth root handle 1: htb r2q 1 default 1");

	system("/sbin/tc qdisc add dev $eth root handle 1: htb default 1");
	system("/sbin/tc class add dev $eth parent 1: classid 1:1 htb rate {$download}kbit burst 1540");
	system("/sbin/tc class add dev $eth parent 1: classid 1:2 htb rate {$inter_download}kbit burst 1540");	

	foreach($ipaddrs as $ipaddr)
	{
		system("/sbin/tc filter add dev $eth  parent 1:0 protocol ip prio 1 u32 match ip src {$ipaddr} flowid 1:2");
	}

//upload
	system("/sbin/tc qdisc add dev $eth handle ffff: ingress");
	system("/sbin/tc filter add dev $eth parent ffff: protocol ip prio 50 u32 match ip src 0.0.0.0/0 police rate {$upload}kbit burst 10k drop flowid :1");
}

?>

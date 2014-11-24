<?php
define("NETWORK_STORE_FILE","/etc/ppp/maintain/network/store.txt");

define("SQLITE_DB_FILE","/etc/ppp/pppoe.db");
$infos = getUserAll();
$output = json_encode($infos);
echo $output;

function getUserAll()
{
	$newworksNew = getPPPNetworks();
	$newworksOld = read_PPPNetworks();
	write_PPPNetworks($newworksNew);
	
	if($newworksOld === false)
	{
		return false;
	}
	
	$users = getUserInfos();
	$newworks = mergeInfos($newworksOld,$newworksNew,$users);
	return $newworks;
}

function getPPPNetworks()
{
	
	$infos = array();
	$devfile = "/proc/net/dev";
	$fh = fopen( $devfile, 'r' );
	while($line = fgets( $fh ))
	{
		if(strpos($line,"ppp") !== false)
		{
			if (preg_match( '/^(.*?):[ \t]*([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]*([0-9]+?)[ \t]+([0-9]+?)[ \t]*([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]*$/', $line, $found ))
		    {
		    	$eth = trim($found[1]);
		    	$infos[$eth] = array();
		    	$infos[$eth]["ReceiveByte"] = $found[2];
		    	$infos[$eth]["ReceivePackets"] = $found[3];
		    	
		    	$infos[$eth]["TransmitByte"] = $found[10];
		    	$infos[$eth]["TransmitPackets"] = $found[11];
		    }  
		    else
		    {
		    	var_dump($line);
		    } 
		}
	}
	return $infos;
}

function write_PPPNetworks($newworks)
{
	$output = json_encode($newworks);
	file_put_contents(NETWORK_STORE_FILE,$output);
}

function read_PPPNetworks()
{
	$input = file_get_contents(NETWORK_STORE_FILE);
	if(false === $input)
	{
		return false;
	}
	else
	{
		return json_decode($input,true);
	}
}

function getUserInfos()
{
	$localusers = array();
	$lite = new SQLite3(SQLITE_DB_FILE);
	$results = $lite->query("select ppp,username,ipaddr from pppoe where status='true'");
	while($row = $results->fetchArray())
	{
		$localusers[$row["username"]] = $row["ppp"];
	}
	$lite->close();
	return $localusers;
}

function mergeInfos($old,$new,$userInfos)
{
	$networks = array();
	foreach($userInfos as $username => $ppp)
	{
		if(isset($old[$ppp]) && isset($new[$ppp]))
		{
			$networks[$username]["ReceiveByte"] = $new[$ppp]["ReceiveByte"] - $old[$ppp]["ReceiveByte"];
			$networks[$username]["ReceivePackets"] = $new[$ppp]["ReceivePackets"] - $old[$ppp]["ReceivePackets"];
			$networks[$username]["TransmitByte"] = $new[$ppp]["TransmitByte"] - $old[$ppp]["TransmitByte"];
			$networks[$username]["TransmitPackets"] = $new[$ppp]["TransmitPackets"] - $old[$ppp]["TransmitPackets"];
		}
	}
	return $networks;
}

function getNow()
{
	date_default_timezone_set("Asia/Shanghai");
	return date( "=Y-m-d-H-i-s",time()).":";
}
?>

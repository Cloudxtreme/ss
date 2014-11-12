<?php

$min_cnt = 10;

$handle = @fopen("ip.txt", "r");
if( ! $handle) { exit; }

$ips = array();
while (!feof($handle)) 
{
	$ip = fgets($handle, 1024);
	$ip = ltrim($ip);
	$ip = rtrim($ip);
	$temp = explode(' ', $ip);
	if( count($temp) != 2 ) { continue; }
	
	$ip = $temp[0];
	$cnt = $temp[1];
	if( $cnt < $min_cnt ) { continue; }
	
	$ip = explode('.', $ip);
	$ip = "$ip[0].$ip[1].$ip[2].0";
	$ips[$ip] = 0;
	//print("$ip $cnt \n");
}

fclose($handle);

print(count($ips));

$filename = 'ip.data';
if( ! $handle = fopen($filename, 'w+') ) { exit; }

foreach( $ips as $ip => $temp ) {
	fwrite($handle, "$ip\n");
}

fclose($handle);

?>



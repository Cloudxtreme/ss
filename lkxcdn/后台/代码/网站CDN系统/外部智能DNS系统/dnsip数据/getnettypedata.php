<?php
require_once('iplocation.class.php');

/*
cat ../dns/ctlog ../dns/cnclog > log
awk '{print $1;}' log > ip.txt
*/

$dnsname = 'cache.rightgo.net';
$dnsdbname = 'cache_rightgo_net_ex';

$ips = array();
$ip_l = new ipLocation();

$handle = @fopen("ip.txt", "r");
if($handle) {
  while (!feof($handle)) {
		$ip = fgets($handle, 1024);
		$ip = trim($ip);
		$address = $ip_l->getaddress($ip);
		//$address["area1"] = iconv('GB2312', 'utf-8', $address["area1"]);
		//$address["area2"] = iconv('GB2312', 'utf-8', $address["area2"]);
		$user_nettype = $address["area2"];
		$user_zone = $address["area1"];
		//print("$ip $user_zone $user_nettype \n");
		
		$ip = explode('.', $ip);
		$ip = "$ip[0].$ip[1].$ip[2].0";

		if( strstr($user_nettype, '电信') ) {
			$ips['电信'][$ip] = $ip;
		}
		else if( strstr($user_nettype, '网通') || strstr($user_nettype, '联通') ) {
			$ips['网通'][$ip] = $ip;
		}
		else if( strstr($user_nettype, '移动') ) {
			$ips['移动'][$ip] = $ip;
		}
		else if( strstr($user_nettype, '长城宽带') ) {
			$ips['长城宽带'][$ip] = $ip;
		}
		else if( strstr($user_nettype, '大学') ) {
			$ips['教育'][$ip] = $ip;
		}		
		else {
			$ips['其它'][$ip] = $ip;
		}
   }
  fclose($handle);
}

$handle = fopen("ct.txt", 'w');
fwrite($handle, "acl \"ct\" {\n");
if( $handle ) { 
	foreach( $ips['电信'] as $ip ) {
		fwrite($handle, "$ip/24;\n");
	}
}
fwrite($handle, "};");
fclose($handle);	

$handle = fopen("cnc.txt", 'w');
fwrite($handle, "acl \"cnc\" {\n");
if( $handle ) { 
	foreach( $ips['网通'] as $ip ) {
		fwrite($handle, "$ip/24;\n");
	}
}
fwrite($handle, "};");
fclose($handle);	

$handle = fopen("mobile.txt", 'w');
fwrite($handle, "acl \"mobile\" {\n");
if( $handle ) { 
	foreach( $ips['移动'] as $ip ) {
		fwrite($handle, "$ip/24;\n");
	}
}
fwrite($handle, "};");
fclose($handle);

$handle = fopen("gwbn.txt", 'w');
fwrite($handle, "acl \"gwbn\" {\n");
if( $handle ) { 
	foreach( $ips['长城宽带'] as $ip ) {
		fwrite($handle, "$ip/24;\n");
	}
}
fwrite($handle, "};");
fclose($handle);				

$handle = fopen("edu.txt", 'w');
fwrite($handle, "acl \"edu\" {\n");
if( $handle ) { 
	foreach( $ips['教育'] as $ip ) {
		fwrite($handle, "$ip/24;\n");
	}
}
fwrite($handle, "};");
fclose($handle);				


?>



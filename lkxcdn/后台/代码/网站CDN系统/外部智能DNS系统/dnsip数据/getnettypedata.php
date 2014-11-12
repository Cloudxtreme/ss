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

		if( strstr($user_nettype, '����') ) {
			$ips['����'][$ip] = $ip;
		}
		else if( strstr($user_nettype, '��ͨ') || strstr($user_nettype, '��ͨ') ) {
			$ips['��ͨ'][$ip] = $ip;
		}
		else if( strstr($user_nettype, '�ƶ�') ) {
			$ips['�ƶ�'][$ip] = $ip;
		}
		else if( strstr($user_nettype, '���ǿ��') ) {
			$ips['���ǿ��'][$ip] = $ip;
		}
		else if( strstr($user_nettype, '��ѧ') ) {
			$ips['����'][$ip] = $ip;
		}		
		else {
			$ips['����'][$ip] = $ip;
		}
   }
  fclose($handle);
}

$handle = fopen("ct.txt", 'w');
fwrite($handle, "acl \"ct\" {\n");
if( $handle ) { 
	foreach( $ips['����'] as $ip ) {
		fwrite($handle, "$ip/24;\n");
	}
}
fwrite($handle, "};");
fclose($handle);	

$handle = fopen("cnc.txt", 'w');
fwrite($handle, "acl \"cnc\" {\n");
if( $handle ) { 
	foreach( $ips['��ͨ'] as $ip ) {
		fwrite($handle, "$ip/24;\n");
	}
}
fwrite($handle, "};");
fclose($handle);	

$handle = fopen("mobile.txt", 'w');
fwrite($handle, "acl \"mobile\" {\n");
if( $handle ) { 
	foreach( $ips['�ƶ�'] as $ip ) {
		fwrite($handle, "$ip/24;\n");
	}
}
fwrite($handle, "};");
fclose($handle);

$handle = fopen("gwbn.txt", 'w');
fwrite($handle, "acl \"gwbn\" {\n");
if( $handle ) { 
	foreach( $ips['���ǿ��'] as $ip ) {
		fwrite($handle, "$ip/24;\n");
	}
}
fwrite($handle, "};");
fclose($handle);				

$handle = fopen("edu.txt", 'w');
fwrite($handle, "acl \"edu\" {\n");
if( $handle ) { 
	foreach( $ips['����'] as $ip ) {
		fwrite($handle, "$ip/24;\n");
	}
}
fwrite($handle, "};");
fclose($handle);				


?>



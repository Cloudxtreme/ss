<?php
	require_once('data/iplocation.class.php');


	$ipl = new ipLocation('/opt/cdnfilelog/data/qqwry.dat');
	$address = $ipl->getaddress("101.245.103.69");
        $ip_nettype = iconv('GB2312', 'utf-8', $address["area2"]);
	echo "$ip_nettype\n";


?>

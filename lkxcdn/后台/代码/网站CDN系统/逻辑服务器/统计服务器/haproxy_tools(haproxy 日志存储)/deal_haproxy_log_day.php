<?php

$hostname_listfile = '/opt/haproxy_tools/hostname_list.txt';
$hostname_list = array();

$fileinfo = array();
$hostname_fileinfo = array();

$log_temp_path = '/opt/haproxy_temp_log';
$log_path = '/opt/haproxy_log';

$handle = @fopen($hostname_listfile, "r");
if ($handle) {
    while (!feof($handle)) {
        $line = fgets($handle, 4096);
		$line = ltrim($line);
		$line = rtrim($line);
		if( ! strlen($line)	) { continue; }
		$hostname_list[$line] = $line;
    }
    fclose($handle);
}

//print_r($hostname_list);

$day = @date("Y-m-d",strtotime("-1 day"));
//print_r($day);

$ips = scandir($log_temp_path);
//print_r($ips);
foreach( $ips as $ip ) {
	if( $ip[0] == '.' ) { continue; }
	$ipdir = "$log_temp_path/$ip/$day";
	$logs = scandir($ipdir);
	//print_r($logs);
	foreach( $logs as $logfile ) {
		if( $logfile[0] == '.' ) { continue; }
		$file = "$ipdir/$logfile";
		//print("$file\n");

		if( strstr($file, 'cache.rightgo.net') ) { continue; }
		if( strstr($file, ':80') ) { continue; }

		$cmd = "/usr/bin/lzop -o $log_path/$day/$ip".'_'."$logfile.lzo $file";
		print("$cmd\n");
	}
}


?>


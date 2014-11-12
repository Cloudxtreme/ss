<?php

$hostname_listfile = '/opt/haproxy_tools/hostname_list.txt';
$hostname_list = array();

$server_listfile = '/opt/haproxy_tools/server_list.txt';
$server_list = array();

$log_temp_path = '/opt/haproxy_temp_log_30min';
$log_path = '/opt/haproxy_log_30min';

$day = @date("Y-m-d", strtotime("-30 minutes"));
$hour = @date("H", strtotime("-30 minutes"));
$min = @date("i", strtotime("-30 minutes"));
$minidx = (int)($min/30 + 1);
print("$day $hour $min $minidx\n");

mkdir("$log_temp_path/$day");
mkdir("$log_path/$day");
mkdir("$log_path/$day/$hour".'_'."$minidx");

$handle = @fopen($hostname_listfile, "r");
if ($handle) {
    while (!feof($handle)) {
    	$line = fgets($handle, 4096);
			$line = ltrim($line);
			$line = rtrim($line);
			if( ! strlen($line)	) { continue; }
			$hostname = $line;
			$hostname_list[$hostname] = $hostname;
    }
    fclose($handle);
}
//print_r($hostname_list);

$handle = @fopen($server_listfile, "r");
if ($handle) {
    while (!feof($handle)) {
    	$line = fgets($handle, 4096);
			$line = ltrim($line);
			$line = rtrim($line);
			if( ! strlen($line)	) { continue; }
			$temp = explode(':', $line);
			$ip = $temp[0];
			$server_list[$ip] = $ip;
			@mkdir("$log_temp_path/$day/$ip");
    }
    fclose($handle);
}
//print_r($server_list);

foreach( $server_list as $ip ) {
	$url = sprintf("http://%s:7654/%s_h/%s_%s.tar.gz", $ip, $day, $hour, $minidx);
	//print("$url\n");
	$zipfile = sprintf("%s_%s.tar.gz", $hour, $minidx);
	$cmd = "/usr/bin/wget -t 2 -T 180 $url -O $log_temp_path/$day/$ip/$zipfile && cd $log_temp_path/$day/$ip/ && /bin/tar zxf $zipfile";
	print("$cmd\n");
	system($cmd);
}

foreach( $server_list as $ip ) {
	$subdir = sprintf("%s_%s", $hour, $minidx);
	$dir = "$log_temp_path/$day/$ip/$subdir";
	//print("$dir\n");
	
	//$cmd = "/bin/rm -rf $log_temp_path/$day/$ip/$zipfile";
	//print("$cmd\n");
	//system($cmd);
	
	$files = scandir($dir);
	if( $files === FALSE ) { continue; }
	foreach( $files as $file ) {
		$logfile = "$dir/$file";
		$lzofile = sprintf("%s_%s.lzo", $ip, $file);
		$lzofile = str_replace(':80', '', $lzofile);
		$cmd = "/usr/bin/lzop -o $log_path/$day/$subdir/$lzofile $logfile\n";
		print("$cmd\n");
		system($cmd);
	}
}

?>


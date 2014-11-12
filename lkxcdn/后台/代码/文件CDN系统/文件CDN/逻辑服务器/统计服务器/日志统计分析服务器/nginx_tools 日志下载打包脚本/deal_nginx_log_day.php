<?php

$config_listfile = '/opt/nginx_tools/config';
$log_list = array();

$log_temp_path = '/opt/nginx_temp_log';
$log_path = '/opt/nginx_log';

$handle = @fopen($config_listfile, "r");
if ($handle) {
    while (!feof($handle)) {
        $line = fgets($handle, 4096);
		$line = ltrim($line);
		$line = rtrim($line);
		$temp = explode(' ', $line);
		if( count($temp) != 2 ) { continue; }
		$loginfo = $temp[1];
		if( ! strlen($loginfo)	) { continue; }

		$loginfo = substr($loginfo, strrpos($loginfo, '/')+1);
		//print("$loginfo ".strrpos($loginfo, '/')."\n");
		$log_list[$loginfo] = $loginfo;
    }
    fclose($handle);
}

//print_r($log_list); exit;

$day = @date("Y-m-d",strtotime("-1 day"));
//print_r($day);

$daytemplogpath = "$log_temp_path/*/$day";
$daylogpath = "$log_path/$day";

foreach( $log_list as $log ) {
	$cmd = "/bin/cat $daytemplogpath/$log > $daylogpath/$log.txt";
	print("$cmd\n");
}

?>


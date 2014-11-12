<?php

$user_log_list = array();

$log_org_path = '/opt/cdnfilelog/logs';
$log_path = '/opt/nginx_log_30min';

$day = @date("Y-m-d", strtotime("-30 minutes"));
$hour = @date("H", strtotime("-30 minutes"));
$smin = (int)($argv[1]);
$emin = (int)($argv[2]);

for( $i = $smin; $i <= $emin; $i++ ) {
	$logdir = "$log_org_path/$day/$hour/$i";
	$temps = scandir($logdir);
	if( $temps == FALSE ) { continue; }
	foreach( $temps as $file ) {
		if( $file[0] == '.' ) { continue; }
		$user = str_replace('.log', '', $file);
		$user_log_list[$user][] = "$logdir/$file";
	}
	//print_r($logdir); print_r($temps);
}
//print_r($user_log_list);

$logdir = sprintf("%s_%s", "$log_path/$day/$hour", (int)($emin/5));
//print_r($logdir);
mkdir($logdir, 0777, true);

foreach( $user_log_list as $user => $files ) {
	$cmd = "/bin/cat ";
	foreach( $files as $file ) {
		$cmd .= "$file ";
	}
	$cmd .= " > $logdir/$user.log";
	print("$cmd\n");
	system("$cmd\n");
	
	//$cmd = "/usr/bin/lzop -o $logdir/$user.lzo $logdir/$user.log\n";
	//print("$cmd\n");
	//system("$cmd\n");
}

//call hadoop 
$subdir = sprintf("%s_%s", $hour, (int)($emin/5));
$cmd = "/usr/local/hadoop/run_cdn_file_half.sh $day $subdir";
system($cmd);

?>


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

print_r($log_list); 
$day = @date("Y-m-d",strtotime("-1 day"));
print_r($day);

$serverips = array();
$temp = scandir($log_temp_path);
foreach( $temp as $serverip ) {
  if( $serverip[0] != '.' ) {
    $serverips[$serverip] = $serverip;
  }
}
print_r($serverips);

$i = 0;
$proc_list = array();
foreach( $serverips as $serverip )
{
  $logfile = "$log_temp_path/$serverip/$day";
  $cmd = "/usr/bin/php /opt/mjfilelog/cdnfilelog_stat.php $logfile";
  print("$cmd\n");
  $handle = popen($cmd, 'r');
  $proc_list[$i++] = $handle;
}  

foreach( $proc_list as $i => $handle )
{   
    $contents = '';
    while( ! feof($handle) ) {
      $contents .= fread($handle, 1024);
    }
    print_r($contents);
    pclose($handle);
}

?>
 

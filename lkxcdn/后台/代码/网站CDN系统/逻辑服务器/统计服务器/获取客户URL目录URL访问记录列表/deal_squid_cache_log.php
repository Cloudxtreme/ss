<?php
require_once('cdn_db.php');

$create_fmt = "CREATE TABLE IF NOT EXISTS `%s` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `url` varchar(256) NOT NULL,
 `timestamp` datetime NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `url_index` (`url`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";

$day = @date("Y-m-d",strtotime("-1 day"));
//print($day);

$log_path = "/opt/squid_log/$day";
//print("$log_path\n");

$files = scandir($log_path);
//print_r($files);

$hostnames = array();
$handle = @fopen('/opt/squid_tools/hostname_list.txt', "r");
if( $handle ) {
	while( ! feof($handle) ) {
		$hostname = fgets($handle, 100);
		$hostname = trim($hostname);
		if( $hostname == '' ) { continue; }
		$hostnames[$hostname] = $hostname;
	}
	fclose($handle);
}
//print_r($hostnames);

//exit;

$urls = array();
foreach( $files as $filename )
{
	if( $filename[0] == '.' ) { continue; }
	$urls = array();
	parse_log($filename);
	print("$filename ".count($urls)."\n");
	update_cache_db($filename, $urls);
	//print_r($urls);
	//break;
}

function parse_log($filename)
{
	global $log_path, $urls;

	$hostname = $filename;
	$filepathname = "$log_path/$filename";

	//print("$hostname $filepathname \n");
	$handle = @fopen($filepathname, "r");
	if( ! $handle) { return false; }

    while (!feof($handle)) 
	{
        $url = fgets($handle, 4096);
		$url = ltrim($url); 
		$url = rtrim($url);
		if( ! strlen($url) ) { continue; }
		$urls[$url] = 0;
    }   
    fclose($handle);
}

function update_cache_db($filename, &$urls)
{
	global $create_fmt, $global_databasename, $hostnames;

	$dbobj = new DBObj;
	if( ! $dbobj->conn() ) {
		print($dbobj->error()); return false;
	}

	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_databasename);

	$tablename = $filename;
	foreach( $hostnames as $hostname ) {
		if( strstr($filename, $hostname) ) {
			$tablename = $hostname; break;
		}
	}

	$query = sprintf($create_fmt, $tablename);
	if( ! ($result = $dbobj->query($query)) ) {
		return false;
	}	

	foreach( $urls as $url => $temp ) 
	{
		$query = "insert into `$tablename`(`url`, `timestamp`) values('$url', now()) ON DUPLICATE KEY UPDATE `timestamp` = now();";
		$dbobj->query($query);
	}
}

?>


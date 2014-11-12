<?php
require_once('cdn_db.php');

global $global_databasename;

$logroot = '/opt/nginx_log';

header("Content-type: text/html; charset=UTF-8");

if( ! isset($_GET['client']) ) { exit; }

$client = $_GET['client'];

$dbobj = new DBObj;
if( ! $dbobj->conn() ) { exit; }
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

$query = "SELECT * FROM `user` where `user` = '$client';";
//print($query);

if( ! ($result = $dbobj->query($query)) ) {
	exit;
}

if( mysql_num_rows($result) <= 0 ) { exit; }

mysql_free_result($result);

$filelist = filelist_core($logroot);
//print_r($filelist); 

$loginfo = array();

foreach( $filelist['dir'] as $dir => $temp ) 
{
	$logfilelist = filelist_core("$logroot/$dir");
	//print_r($logfilelist);
	foreach( $logfilelist['file'] as $filename => $fileinfo )
	{
		if( strstr($filename, $client) ) 
		{
			$tempinfo['date'] = $dir;
			$tempinfo['file'] = $filename;
			$tempinfo['size'] = $fileinfo['size'];
			$loginfo[] = $tempinfo;
		}
	}
}
//print_r($loginfo);
print_r(json_encode($loginfo));

///////////////////////////////////////////////////////////////////////////////////

function filelist_core($dir)
{
	$files = scandir($dir);
	//print_r($files);
	$filelist = array(
			'dir' => array(), 
			'file' => array()
			);

	foreach( $files as $file )
	{
		if( $file[0] == '.' ) { continue; }
		$filename = "$dir/$file";

		$info['time'] = @date("Y-m-d H:i:s", filemtime($filename));
		$info['size'] = sprintf("%u", filesize($filename));//round($filesize/1024/1024, 2);

		if( is_dir($filename) ) {
			$filelist['dir'][$file] = $info;
		} else {
			$filelist['file'][$file] = $info;
		}
	}

	return $filelist;
}

?>


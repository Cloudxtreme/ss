<?php
require_once('db.php');

global $global_filecdn_db;
global $global_ftp_cdn_dir, $global_source_cdn_dir;

$dbobj = new DBObj;
if( ! $dbobj->conn() ) { return false; }
	
$dbobj->query("set names utf8;");
$dbobj->select_db($global_filecdn_db);
	
$query = "select * from auto_delivery where `status` = 'true';";
//print($query);
if( ! ($result = $dbobj->query($query)) ) { return false; }
if( ! mysql_num_rows($result) ) { return false; }

$file_list = array();
while( ($row = mysql_fetch_array($result)) ) 
{
	$id = $row['id'];
	$filename = $row['filename'];
	$filepath = $row['filepath'];
	$filesize = $row['filesize'];
	$md5 = strtolower($row['md5']);
	$owner = $row['owner'];
	
	$file_list[$id] = array('filename' => $filename, 
													'filepath' => $filepath,
													'filesize' => $filesize,
													'md5' => $md5,
													'owner' => $owner);
}
mysql_free_result($result);

//print_r($file_list);

foreach( $file_list as $id => $info )
{
	$owner = $info['owner'];
	$filepath = $info['filepath'];
	
	if( $info['filepath'] == '/' ) {
		$ftppath = $global_ftp_cdn_dir . '/' . $owner;
	} else {
		$ftppath = $global_ftp_cdn_dir . '/' . $owner . $info['filepath'];
	}
	
	if( $info['filepath'] == '/' ) {
		$rsyncpath = $global_source_cdn_dir . '/' . $owner;
	} else {
		$rsyncpath = $global_source_cdn_dir . '/' . $owner . $info['filepath'];
	}

	if( substr($ftppath, -1) == '/' ) {
		$ftppath = substr($ftppath, 0, -1);
	}

	if( ! checkurl($ftppath) ||
			! checkurl($rsyncpath)  ) { continue; }
	
	$filename = $info['filename'];
	$filesize = $info['filesize'];
	$ftp_file = "$ftppath/$filename";
	$rsync_file = "$rsyncpath/$filename";
	$md5 = $info['md5'];
		
	$query = "select * from public_file where 
						filename = '$filename' and
						filesize = '$filesize' and
						filepath = '$ftppath';";
	print("$query \n");
	
	if( ! ($result = $dbobj->query($query)) ) { continue; }
	if( ! mysql_num_rows($result) ) { continue; }
	mysql_free_result($result);
	
	
	$cmd = "md5sum $ftp_file";
	$ret = this_server_run($cmd);
	if( $ret ) { 
		$ret = explode(' ', $ret);	
		$ret = $ret[0];
	} else {
		continue;
	}
	if( strlen($ret) != strlen($md5) ) { continue; }
	
	if( $md5 == $ret )
	{
		//print("$md5 $ret $ftppath \n");
		$cmd = "mkdir -p $rsyncpath && ln -s $ftp_file $rsync_file";
		//print("$cmd\n");
		$ret = this_server_run($cmd);
		
		$query = "update public_file
					set `status` = '分发文件'
					where
					filename = '$filename' and
					filesize = '$filesize' and
					filepath = '$ftppath';";
		//print("$query \n");
		$dbobj->query($query);
		
		$query = "update auto_delivery
					set `status` = 'false'
					where
					filename = '$filename' and
					filepath = '$filepath' and
					owner = '$owner';";
		//print("$query \n");
		$dbobj->query($query);		
	}
}

function checkurl($str)
{
	if( strstr($str, ';') ) { return false; }
	if( strstr($str, '|') ) { return false; }
	if( strstr($str, '&') ) { return false; }
	if( strstr($str, '<') ) { return false; }
	if( strstr($str, '>') ) { return false; }
	if( strstr($str, '..') ) { return false; }
	
	return true;	
}

function this_server_run($cmd)
{
	$conn = ssh2_connect('127.0.0.1', '7997');

	if( ! $conn ) { return false; }

	if( ! ssh2_auth_password($conn, 'root', 'rjkj@rjkj') ) { return false; }

	$stream = ssh2_exec($conn, $cmd);
	if( ! $stream ) { return false; }

	stream_set_blocking($stream, true);
	$ret = stream_get_contents($stream);
	//print_r("$ret\n");
	if( $ret ) {
		return $ret;
	}
	return false;
}

?>

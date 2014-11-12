<?php
require_once('cdn_db.php');

global $global_databasename;
global $global_ftp_cdn_dir;

$now_filelist = array();
$old_filelist = array();

if( ! isset($_POST['myip']) || ! isset($_POST['filelist']) ) {
	exit;
}

print_r($_POST);

$serverip = $_POST['myip'];
$filelist = $_POST['filelist'];

$filelist = str_replace('[', '', $filelist);
$filelist = str_replace(']', '', $filelist);
	
if( strlen($filelist) ) 
{
	$filelist = str_replace('\'', '', $filelist);
	$filelist = str_replace(' ', '', $filelist);
	$filelist = explode(',', $filelist);
	
	foreach( $filelist as $key => $value )
	{
		$value = str_replace('\x', '%', $value);
		$value = urldecode($value);
			
		$fileinfo = explode(':', $value);
		$filename = $fileinfo[0];
		if( is_utf8($filename) )
		{
			
		}
		else
		{
			//gb2312
			//$filename = iconv('GB2312', 'UTF-8', $filename);
		}
		
		$filesize = $fileinfo[1];
		$filepath = $fileinfo[2];
		$filemtime = $fileinfo[3];
		$subpath = str_replace($global_ftp_cdn_dir, '', $filepath);
		$fileid = md5("$subpath/$filename");
	
		$now_filelist[$fileid]['name'] = $filename;
		$now_filelist[$fileid]['size'] = $filesize;
		$now_filelist[$fileid]['path'] = $filepath;
		$now_filelist[$fileid]['subpath'] = $subpath;
		$now_filelist[$fileid]['mtime'] = $filemtime;
	}
}
//print_r($now_filelist);

//get now filelist
$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}

$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

//get server file list
$query = "select * from public_file;";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( mysql_num_rows($result) )
{
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$fileid = $row['fileid'];
		$old_filelist[$fileid]['name'] = $row['filename'];
		$old_filelist[$fileid]['size'] = $row['filesize'];
		$old_filelist[$fileid]['path'] = $row['filepath'];
		$old_filelist[$fileid]['status'] = $row['status'];
	}
	mysql_free_result($result);
}

//print_r($now_filelist);
//print_r($old_filelist);

//add new file
////////////////////////////////////////////////////////////////////////////
$add_filelist = array_diff_key($now_filelist, $old_filelist);
//print_r($add_filelist);

foreach( $add_filelist as $fileid => $fileinfo )
{
	$filename = $fileinfo['name'];
	$filesize = $fileinfo['size'];
	$filepath = $fileinfo['path'];
	$filesubpath = $fileinfo['subpath'];

	$query = "insert into public_file(`fileid`, `filename`, `filesize`, `filepath`, `subpath`, `timestamp`, `status`) 
						values('$fileid', '$filename', '$filesize', '$filepath', '$filesubpath', now(), '');";
						
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		break;
	}
}

//del source deleted file
////////////////////////////////////////////////////////////////////////////
$del_filelist = array_diff_key($old_filelist, $now_filelist);
//print_r($del_filelist);

foreach( $del_filelist as $fileid => $fileinfo )
{
	$query = "delete from public_file where `fileid` = '$fileid';";
						
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		break;
	}	
}

//update file info
////////////////////////////////////////////////////////////////////////////
foreach( $old_filelist as $fileid => $fileinfo )
{
	if( ! array_key_exists($fileid, $now_filelist) ) {
		continue;
	}

	$filename = $fileinfo['name'];
	$filepath = $fileinfo['path'];
		
	//$subpath = str_replace($global_ftp_cdn_dir, '', $filepath);
	//$fileid = md5("$subpath/$filename");
	//print("$fileid\n");
		
	$old_filesize = $fileinfo['size'];
	$new_filesize = $now_filelist[$fileid]['size'];
	$filemtime = $now_filelist[$fileid]['mtime'];
	
	if( $old_filesize == $new_filesize && $fileinfo['status'] == '' ) 
	{
		$query = "update public_file set `status` = '不做分发'
				where `fileid` = '$fileid' and `status` = '';";
		$dbobj->query($query);
		continue;
	}
	
	$mtime = date("Y-m-d H:i:s", $filemtime);
	$query = "update public_file set `filesize` = '$new_filesize', `timestamp` = '$mtime'
						where `fileid` = '$fileid' and `filepath` = '$filepath';";
						
	if( ! ($result = $dbobj->query($query)) ) {
		break;
	}
}

//////////////////////////////////////////////////

function is_utf8($str)
{
	if(preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$str) == true || 
		preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$str) == true || 
		preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$str) == true) 
	{
		return true;
	}
	else
	{
	return false;
	}
}

?>

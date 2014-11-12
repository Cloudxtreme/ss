<?php
require_once('cdn_db.php');

global $global_databasename;
global $global_source_cdn_dir;

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
		$subpath = str_replace($global_source_cdn_dir, '', $filepath);
		$fileid = md5("$subpath/$filename");
	
		$now_filelist[$fileid]['name'] = $filename;
		$now_filelist[$fileid]['size'] = $filesize;
		$now_filelist[$fileid]['path'] = $filepath;
	}
}

print_r($now_filelist);

//get now filelist
$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}

$dbobj->select_db($global_databasename);

//get server file list
$query = "select * from source_file where `serverip` = '$serverip';";
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
		$old_filelist[$fileid]['size'] = 0;
		$old_filelist[$fileid]['path'] = $row['filepath'];
	}
	mysql_free_result($result);
}

//print_r($now_filelist);
//print_r($old_filelist);

/*
foreach( $now_filelist as $filepathname => $fileinfo )
{
	$filename = $fileinfo['name'];
	$filesize = $fileinfo['size'];
	$filepath = $fileinfo['path'];
	
	$subpath = str_replace($global_source_cdn_dir, '', $filepath);
	$fileid = md5("$subpath/$filename");
	
	$query = "update source_file set
						`serverip` = '$serverip', `fileid` = '$fileid', `subpath` = '$subpath'
						where `filename` = '$filename' and `filepath` = '$filepath';";
						
	$dbobj->query($query);
}
*/

//add new file
$add_filelist = array_diff_key($now_filelist, $old_filelist);
//print_r($add_filelist);

foreach( $add_filelist as $fileid => $fileinfo )
{
	$filename = $fileinfo['name'];
	$filesize = $fileinfo['size'];
	$filepath = $fileinfo['path'];
	
	$subpath = str_replace($global_source_cdn_dir, '', $filepath);
	
	$query = "insert into source_file(`serverip`, `fileid`, `filename`, `filesize`, `filepath`, `subpath`, `timestamp`) 
						values('$serverip', '$fileid', '$filename', '$filesize', '$filepath', '$subpath', now());";
							
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		break;
	}
}

//del source deleted file
$del_filelist = array_diff_key($old_filelist, $now_filelist);
//print_r($del_filelist);

foreach( $del_filelist as $fileid => $fileinfo )
{
	$query = "delete from source_file where `fileid` = '$fileid';";
	print_r($query);
						
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		break;
	}	
}

//del node deleted file
$query = "delete from node_file where filemd5id not in( select fileid from source_file );";
$dbobj->query($query);

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

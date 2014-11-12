<?php
require_once('cdn_db.php');

global $global_databasename;
global $global_source_cdn_dir;

$now_node_filelist = array();
$old_node_filelist = array();
$server_filelist = array();

if( ! isset($_POST['myip']) || 
		! isset($_POST['nodepath']) ||
		! isset($_POST['filelist']) ) {
	exit;
}

//print_r($_POST);

$serverip = $_POST['myip'];
$nodepath = $_POST['nodepath'];
$filelist = $_POST['filelist'];

$filelist = str_replace('[', '', $filelist);
$filelist = str_replace(']', '', $filelist);
	
if( strlen($filelist) ) 
{
	$filelist = str_replace('\'', '', $filelist);
	$filelist = str_replace(' ', '', $filelist);
	
	$filelist = explode(',', $filelist);
	//print_r($filelist);
	
	foreach( $filelist as $key => $value )
	{
		$value = str_replace('\x', '%', $value);
		$value = urldecode($value);
		
		$fileinfo = explode(':', $value);
		$filepathname = str_replace($nodepath, '', $fileinfo[1]);
		$filepathname = $filepathname . '/' . $fileinfo[0];
		$fileid = md5($filepathname);
		$now_node_filelist[$fileid]['name'] = $fileinfo[0];
		$now_node_filelist[$fileid]['path'] = $fileinfo[1];
	}
}

//print_r($now_node_filelist);

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}

$dbobj->select_db($global_databasename);

//get server file list
//////////////////////////////////////////////////////////
$query = "select * from source_file;";
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
		$server_filelist[$fileid]['name'] = $row['filename'];
		$server_filelist[$fileid]['path'] = $row['filepath'];
		$server_filelist[$fileid]['subpath'] = $row['subpath'];
	}
	mysql_free_result($result);
}
//print_r($server_filelist);

//get node file list
//////////////////////////////////////////////////////////
$query = "select * from node_file where `serverip` = '$serverip';";
					
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( mysql_num_rows($result) )
{
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$fileid = $row['filemd5id'];
		$old_node_filelist[$fileid]['path'] = $row['filepath'];
	}
	mysql_free_result($result);
}

//print_r($now_node_filelist);
//print_r($old_node_filelist);
//print_r($server_filelist);

//add new file
$add_filelist = array_diff_key($server_filelist, $old_node_filelist);
//print_r($add_filelist);

foreach( $add_filelist as $fileid => $value )
{
	if( ! array_key_exists($fileid, $now_node_filelist) ) {
		continue;
	}
	
	$filepath = $now_node_filelist[$fileid]['path'];
	
	$query = "insert into node_file(`serverip`, `filemd5id`, `filepath`, `timestamp`) 
						values('$serverip', '$fileid', '$filepath', now());";
						
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
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

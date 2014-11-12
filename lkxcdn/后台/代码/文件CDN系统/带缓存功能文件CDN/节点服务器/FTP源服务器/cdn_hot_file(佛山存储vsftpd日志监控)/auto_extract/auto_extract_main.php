<?php
require_once('db.php');

global $global_databaseip, $global_databasename, $global_ftp_cdn_dir;

$dbobj = new DBObj;
if( ! $dbobj->conn() ) { return false; }
	
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);
	
$db_file_list = array();
$extract_file_list = array();

//1...................
///////////////////////////////////////////////////////////////////////
$query = "select * from file_list where filename like '%.zip.unzip' and `status` = 'finish_check';";
//print($query);
if( ! ($result = $dbobj->query($query)) ) { return false; }

if( mysql_num_rows($result) )
{
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$id = $row['id'];
		$filepathname = $row['filename'];
		$filesize = $row['filesize'];
		$lastmodify = $row['lastmodify'];
		$owner = $row['owner'];
		
		$db_file_list[$filepathname] = array(
														'id' => $id,
														'filesize' => $filesize,
														'lastmodify' => $lastmodify,
														'owner' => $owner);
	}
	mysql_free_result($result);
}

//2...................
///////////////////////////////////////////////////////////////////////
$query = "select * from auto_extract;";
//print($query);
if( ! ($result = $dbobj->query($query)) ) { return false; }

if( mysql_num_rows($result) )
{
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$id = $row['id'];
		$filepathname = $row['filename'];
		$filesize = $row['filesize'];
		$lastmodify = $row['lastmodify'];
		$owner = $row['owner'];
		
		$extract_file_list[$filepathname] = array(
														'id' => $id,
														'filesize' => $filesize,
														'lastmodify' => $lastmodify,
														'owner' => $owner);
	}
	mysql_free_result($result);
}
print("db_file_list\n");
print_r($db_file_list);
print("extract_file_list\n");
print_r($extract_file_list);

// add new extract file
$new_file_list = array_diff_key($db_file_list, $extract_file_list);
print("new_file_list\n");
print_r($new_file_list);
foreach( $new_file_list as $filepathname => $info )
{
	$filesize = $info['filesize'];
	$lastmodify = $info['lastmodify'];
	$owner = $info['owner'];
	
	$query = "insert into auto_extract(`filename`, `filesize`, `owner`, `lastmodify`, `timestamp`, `status`) 
						values('$filepathname', '$filesize', '$owner', '$lastmodify', now(), 'wait2extract');";
							
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		break;
	}	
}

// update ?
$update_file_list = array_intersect_key($db_file_list, $extract_file_list);
print("update_file_list\n");
print_r($update_file_list);
foreach( $update_file_list as $filepathname => $info )
{
	$filesize = $info['filesize'];
	$lastmodify = $info['lastmodify'];
	
	$old_lastmodify = $extract_file_list[$filepathname]['lastmodify'];
	$id = $extract_file_list[$filepathname]['id'];
	//print("$filepathname [$lastmodify] [$id][$old_lastmodify] \n");
	
	//no update
	if( $lastmodify == $old_lastmodify ) { continue; }
	
	$query = "update auto_extract set 
					`filesize` = '$filesize',  
					`lastmodify` = '$lastmodify',
					`timestamp` = now(),
					`status` = 'wait2extract'
					where id = '$id';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		break;
	}
}

// delete not exist file
$del_file_list = array_diff_key($extract_file_list, $db_file_list);
print("del_file_list\n");
print_r($del_file_list);
foreach( $del_file_list as $filepathname )
{
	$query = "delete from auto_extract where filename = '$filepathname';";
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		break;
	}			
}

//ok , extract file now
$extract_file_list = array();
$query = "select * from auto_extract where `status` = 'wait2extract';";
//print($query);
if( ! ($result = $dbobj->query($query)) ) { return false; }

if( mysql_num_rows($result) )
{
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$id = $row['id'];
		$filepathname = $row['filename'];
		$filesize = $row['filesize'];
		$lastmodify = $row['lastmodify'];
		$owner = $row['owner'];
		
		$extract_file_list[$filepathname] = array(
														'id' => $id,
														'filesize' => $filesize,
														'lastmodify' => $lastmodify,
														'owner' => $owner);
	}
	mysql_free_result($result);
}
print("now_extract_file_list");
print_r($extract_file_list);

foreach( $extract_file_list as $filepathname => $info )
{
	$id = $info['id'];
	$owner = $info['owner'];
	
	$file_path_name = get_file_path_name($filepathname);
	$filepath = $file_path_name['path'];
	$filename = $file_path_name['name'];
	
	$cmd = "/opt/cdn_hot_file/auto_extract/extract.sh $filepath $filename $owner";
	print($cmd."\n");
	this_server_run($cmd);
	
	//finish
	$query = "update auto_extract set `status` = 'finish_extract', `timestamp` = now() where `id` = '$id';";
	print("$query\n");
								
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		break;
	}		
}

//function
///////////////////////////////////////

function this_server_run($cmd)
{
	return system($cmd);
}

function get_file_path_name($filepathname)
{
	global $global_ftp_cdn_dir;
	
	$ret = array();
	
	$pos = strrpos($filepathname, '/');
	$ret['path'] = substr($filepathname, 0, $pos);
	$ret['name'] = substr($filepathname, $pos + 1, strlen($filepathname) - $pos - 1);
	
	return $ret;
}

?>

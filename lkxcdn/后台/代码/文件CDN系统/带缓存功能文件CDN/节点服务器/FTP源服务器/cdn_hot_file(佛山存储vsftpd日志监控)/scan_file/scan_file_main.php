<?php
require_once('db.php');

global $global_databaseip, $global_databasename, $global_ftp_cdn_dir;

$dbobj = new DBObj;
if( ! $dbobj->conn() ) { return false; }
	
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);
	
$query = "select * from file_list;";
//print($query);
if( ! ($result = $dbobj->query($query)) ) { return false; }

$db_file_list = array();

if( mysql_num_rows($result) )
{
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$id = $row['id'];
		$filepathname = $row['filename'];
		$filesize = $row['filesize'];
		$owner = $row['owner'];
		$lastmodify = $row['lastmodify'];
		$lastcheck = $row['lastcheck'];
		$extract = $row['extract'];
		$status = $row['status'];
		
		$db_file_list[$filepathname] = array(
														'id' => $id,
														'filesize' => $filesize,
														'owner' => $owner,
														'lastmodify' => $lastmodify,
														'lastcheck' => $lastcheck,
														'extract' => $extract,
														'status' => $status);
	}
	mysql_free_result($result);
}
//print_r($db_file_list);

$ftp_file_list = scan_ftp_file($global_ftp_cdn_dir);
//print_r($ftp_file_list);

$new_file_list = array_diff_key($ftp_file_list, $db_file_list);
//print_r($new_file_list);

//add new file
foreach( $new_file_list as $filepathname => $info )
{
	$filesize = $info['filesize'];
	$lastmodify = $info['lastmodify'];
	$lastmodify_str = @date("Y-m-d H:i:s", $lastmodify);
	$owner = $info['owner'];
	
	$query = "insert into file_list(`filename`, `filesize`, `owner`, `lastmodify`, `lastcheck`, `status`) 
						values('$filepathname', '$filesize', '$owner', '$lastmodify_str', now(), 'first_check');";
							
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		break;
	}
}

//check all file
foreach( $db_file_list as $filepathname => $dbinfo )
{
	$id = $dbinfo['id'];
	$filesize = $dbinfo['filesize'];
	$lastmodify_int = @strtotime($dbinfo['lastmodify']);
	$lastcheck = $dbinfo['lastcheck'];
	$status = $dbinfo['status'];
	
	if( array_key_exists($filepathname, $ftp_file_list) )
	{
		$info = $ftp_file_list[$filepathname];
		$now_lastmodify = $info['lastmodify'];
		$now_lastmodify_str = @date("Y-m-d H:i:s", $now_lastmodify);
		$now_filesize = $info['filesize'];
		//print("$filepathname $lastmodify_int $now_lastmodify \n");

		//first_check => finish_check		
		if( $lastmodify_int == $now_lastmodify && $status == 'first_check' )
		{
			$query = "update file_list set 
								`filesize` = '$now_filesize',  
								`lastcheck` = now(),
								`status` = 'finish_check'
								where id = '$id';";
			if( ! ($result = $dbobj->query($query)) ) 
			{
				print($dbobj->error());
				break;
			}					
		}
		
		//file modify => first_check
		if( $lastmodify_int != $now_lastmodify && $status == 'finish_check' )
		{
			$query = "update file_list set 
								`filesize` = '$now_filesize',  
								`lastmodify` = '$now_lastmodify_str',
								`lastcheck` = now(),
								`status` = 'first_check'
								where id = '$id';";
			if( ! ($result = $dbobj->query($query)) ) 
			{
				print($dbobj->error());
				break;
			}								
		}
		
	}
	else
	{
		//file not exist => delete db info		
		$query = "delete from file_list where `filename` = '$id';";
		if( ! ($result = $dbobj->query($query)) ) 
		{
			print($dbobj->error());
			break;
		}		
	}
}

//function
/////////////////////////////////////////////////////////////////////////

function scan_ftp_file($dir)
{
	$file_list = array();
	
	scan_dir_file($dir, $file_list);
	
	return $file_list;
}

function scan_dir_file($dir, &$file_list)
{
	if( ($handle = opendir($dir)) ) 
	{
    while( false !== ($file = readdir($handle)) ) 
    {
    	if( $file == '.' || $file == '..' ) { continue; }
    	if( $file[0] == '.' ) { continue; }
    	
    	$temp = "$dir/$file";
    	if( is_dir($temp) ) 
    	{
    		scan_dir_file($temp, $file_list);
    	} 
    	else 
    	{
    		$filepathname = $temp;
    		$filesize = sprintf("%u", filesize($filepathname));
    		$lastmodify = filemtime($filepathname);
    		$owner = filepathname_get_owner($filepathname);
    		
				$file_list[$filepathname] = array(
																		'filesize' => $filesize,
																		'lastmodify' => $lastmodify,
																		'owner' => $owner);
    	}
    }
    closedir($handle);
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

function filepathname_get_owner($filepathname)
{
	global $global_ftp_cdn_dir;
	
	$temp = str_replace("$global_ftp_cdn_dir/", '', $filepathname);
	
	$pos = strpos($temp, '/');
	if( $pos > 0 ) {
		$temp = substr($temp, 0, $pos);
	} else {
		$temp = 'unknow';
	}
	
	return $temp;
}

function this_server_run($cmd)
{
	system($cmd);
}



?>

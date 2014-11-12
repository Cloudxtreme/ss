<?php
require_once('db.php');

global $global_databaseip, $global_databasename, $global_ftp_cdn_dir;

{
	global $cdninfo_ip;
        global $cdninfo_user;
        global $cdninfo_pass;
        global $cdninfo_database;

        global $cdnfile_ip;
        global $cdnfile_user;
        global $cdnfile_pass;
        global $cdnfile_database;

        $cdninfo_dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_database);
        $cdnfile_dbobj = db_gethandle($cdnfile_ip, $cdnfile_user, $cdnfile_pass, $cdnfile_database);

        $nodes = nodes_get($cdninfo_dbobj);
}

$dbobj = new DBObj;
if( ! $dbobj->conn() ) { return false; }
	
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

$extract_file_path = $argv[1];
$extract_file_name = $argv[2];
$extract_file_list = $argv[3];

$extract_filepathname = "$extract_file_path/$extract_file_name";
print("[$extract_file_path][$extract_file_name] $extract_file_list \n");

$handle = fopen($extract_file_list, "r");
if( ! $handle ) { exit; }

while( ! feof($handle) )
{
	$line = fgets($handle, 1024);
	$filename = trim($line);
	if( strlen($filename) <= 0 ) { continue; }
	
	$filepathname = "$extract_file_path/$filename";
	
	if( ! @stat($filepathname) ) { continue; }
	if( is_dir($filepathname) ) { continue; }
	
	$filesize = sprintf("%u", filesize($filepathname));
	$lastmodify = filemtime($filepathname);
	$lastmodify_str = @date("Y-m-d H:i:s", $lastmodify);
	$owner = filepathname_get_owner($filepathname);
		
	print("[$owner] $filepathname\n");
	
	// set db 
	$query = "insert into file_list(`filename`, `filesize`, `owner`, `lastmodify`, `lastcheck`, `extract`, `status`) 
						values('$filepathname', '$filesize', '$owner', '$lastmodify_str', now(), '$extract_filepathname', 'finish_check')
						on duplicate key update 
						`filesize` = '$filesize', 
						`lastmodify` = '$lastmodify_str', 
						`lastcheck` = now(), 
						`extract` = '$extract_filepathname',
						`status` = 'finish_check';";
							
	if( ! ($result = $dbobj->query($query)) ) 
	{
		print($dbobj->error());
		break;
	}
	update_file_push($cdnfile_dbobj, $filepathname, $filesize, $owner, $lastmodify_str, $nodes);	
}
fclose($handle);


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

	function db_gethandle($db_ip, $db_user, $db_pass, $databasename)
        {
                $dbobj = new DBObj;
                while( ! $dbobj->conn2($db_ip, $db_user, $db_pass) )
                {
                        printf("conn error!\n");
                        print($dbobj->error());
                        sleep(3);
                }
                $dbobj->query("set names utf8;");
                $dbobj->select_db($databasename);
                return $dbobj;
        }

        function db_query($dbobj, $query)
        {
                return $dbobj->query($query);
        }

        function nodes_get($dbobj)
        {
                $hosts = array();
                $query = "select distinct ip from node_list;";
                if( ! ($result = db_query($dbobj, $query)) )
                {
                        print($dbobj->error());
                         return false;
                }

                while( ($row = mysql_fetch_array($result)) )
                {
                        $ip = $row['ip'];
                        $hosts[] = $ip;
                }
                mysql_free_result($result);
                return $hosts;
        }

	function update_file_push($dbobj, $file, $filesize, $owner, $lastmodify, $nodes)
        {
                $sql = "";

                foreach ($nodes as $id => $node)
                {
                        if(strlen($sql) == 0)
                                $sql = "insert into file_push(filename, filesize, owner, lastmodify, serverip, status) 
					values ('$file', $filesize, '$owner', '$lastmodify', '$node', 'ready')";
                        else
                                $sql .= ",('$file', $filesize, '$owner', '$lastmodify', '$node', 'ready')";
                }
                $sql .= ";";
                if(strstr($sql, "Array"))
			printf("%s\n\n\n", $sql);
                db_query($dbobj, $sql);
        }
?>

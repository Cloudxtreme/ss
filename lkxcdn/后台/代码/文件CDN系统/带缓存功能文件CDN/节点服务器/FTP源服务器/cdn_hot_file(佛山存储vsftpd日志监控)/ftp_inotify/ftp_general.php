<?php
	require_once('db.php');

	ini_set('date.timezone', 'Asia/Shanghai');

	function general_ftp_syslog($sysfile)
	{
		global $cdnmgr_ip;
        	global $cdnmgr_user;
        	global $cdnmgr_pass;
        	global $cdnmgr_file_database;

        	$dbobj = db_gethandle($cdnmgr_ip, $cdnmgr_user, $cdnmgr_pass, $cdnmgr_file_database);
		
		$ftp_home = "/var/ftp/pub/";
		$handle = fopen($sysfile, "r");
		if(!$handle)
			return -1;

		fseek($handle,sprintf("%u", filesize($sysfile)));
		while(1)
		{
			while(!feof($handle))
			{
				$sql = "";
				$record = fgets($handle);
				if(!strlen($record))
					continue;
				$arr = explode(" ", $record);
				
				$status = $arr[6];
				$object = $arr[4];
				$opera = $arr[7];

				if(($opera != "UPLOAD:") && ($opera != "DELETE:") && ($opera != "RENAME:"))
					continue;
				
				$owner = $arr[5];
				$owner = substr($owner, 1, -1);
				$file = $arr[10];
				if($opera == "UPLOAD:")
				{
					$file = $ftp_home . $owner . substr($file, 1, -2);
					if(file_exists($file))
					{
						$size = filesize($file);
						$md5 = md5_file($file);
						$time = date("Y-m-d H:i:s", filemtime($file));
						$sql = "insert into file_list(filename,filesize,md5,md5_source,owner,lastmodify,type,percent,status) values 
							('$file', $size, '$md5', '$md5', '$owner', '$time', 'push', 0, 'ready') on duplicate key update 
							filename='$file',filesize=$size,md5='$md5',md5_source='$md5',lastmodify='$time',opera_time=opera_time+1,type='push',percent=0,status='ready',extract='';";
						if(strstr($file, ".zip.unzip"))
							system("/opt/cdn_hot_file/ftp_inotify/ftp_zip_handle.sh $owner $file > /dev/null &");
					}
				}
				else if($opera == "RENAME:")
				{
					$file = $ftp_home . $owner . substr($file, 1);
					$nfile = $arr[11];
					$nfile = $ftp_home . $owner . substr($nfile, 0, -2);
					$time = "";
					if(file_exists($nfile))
						$time = date("Y-m-d H:i:s", filemtime($nfile));
					$sql = "update file_list set filename='$nfile',lastmodify='$time',percent=0,status='ready' where filename='$file';";
				}
				else
				{
					$file = $ftp_home . $owner . substr($file, 1, -1);
					$sql = "update file_list set percent=0,type='delete',status='ready' where filename='$file';";
				}

				//echo "$sql\n\n";
				while(!db_query($dbobj, $sql))
				{
					if(!dbobj)
						$dbobj = db_gethandle($cdnmgr_ip, $cdnmgr_user, $cdnmgr_pass, $cdnmgr_file_database);
				}
					
			}
			fseek($handle, ftell($handle));
			usleep(100000);
		}
		
	}

	$ftp_syslog_file = "/var/log/vsftpd.log";
	general_ftp_syslog($ftp_syslog_file);

?>

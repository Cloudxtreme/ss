<?php

	require_once('db.php');

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

	function check_md5_status($dbobj, $fileid)
	{
		$ret = "";
		$sql = "select id from md5_file where fileid='$fileid' and status!='finish';";
		
		if( ! ($result = db_query($dbobj, $sql)) )
			return "md5fail";
		if(mysql_num_rows($result))
		{
			mysql_free_result($result);
			return "md5ing";
		}
		mysql_free_result($result);

		$sql = "select m2.serverip from md5_file m1,md5_file m2 where m1.fileid='$fileid' and m2.fileid=m1.fileid and m1.type='source' and m2.filemd5!=m1.filemd5;";
		
		if( ! ($result = db_query($dbobj, $sql)) )
                        return "md5fail";
                if(mysql_num_rows($result))
                {
			$ret = "md5fail";
			while( ($row = mysql_fetch_array($result)) )
			{
				$ip = $row['serverip'];
				$ret .= "_$ip";
			}
		}
		else
			$ret = "md5ok";
		
                mysql_free_result($result);
		return $ret;
	}

	function md5_check($dbobj)
	{
		$md5_ok = "";
		$md5_fail = "";
		$sql = "select id,fileid from source_file where status='md5ing';";
		
		if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                         return false;
                }

                while( ($row = mysql_fetch_array($result)) )
                {
                        $id = $row['id'];
                        $fileid = $row['fileid'];

                        $md5_status = check_md5_status($dbobj, $fileid);
			if($md5_status != "md5ing")
			{
				$sql = "update source_file set status='$md5_status' where id=$id;";
				echo "$sql\n";
				db_query($dbobj, $sql);
			}
			else
				echo "$fileid--md5sum doing\n";
                }
		mysql_free_result($result);
	}

        global $cdninfo_ip;
        global $cdninfo_user;
        global $cdninfo_pass;
        global $cdninfo_database;

        $cdninfo_dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_database);

	md5_check($cdninfo_dbobj);

?>


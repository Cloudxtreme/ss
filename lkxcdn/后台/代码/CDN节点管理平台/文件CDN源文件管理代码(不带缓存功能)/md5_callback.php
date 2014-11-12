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


        global $cdninfo_ip;
        global $cdninfo_user;
        global $cdninfo_pass;
        global $cdninfo_database;

        $cdninfo_dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_database);


	if( ! isset($_GET['id']) ) { exit; }

	$id = $_GET['id'];
	$md5_num = $_GET['md5'];

	$sql = "update md5_file set status='finish',filemd5='$md5_num' where id=$id;";

	db_query($cdninfo_dbobj, $sql);

?>


<?php
require_once('db.php');

//print_r($_SERVER);

$serverip = $_SERVER['REMOTE_ADDR'];

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error()."\n");
	exit;
}

$dbobj->query("set names utf8;");
$dbobj->select_db('cdn_server_admin');

$query = "select * from server_list where `ip` = '$serverip'";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error()."\n");
	return;
}

if( ! mysql_num_rows($result) ) 
{
	print($dbobj->error()."\n");
	return;
}
	
$row = mysql_fetch_array($result);
if( ! $row )
{
	print("not found this serverip $serverip \n");
	return;
}

$nettype = $row['nettype'];

mysql_free_result($result);	

echo "*/5 * * * * /opt/cachemgr/portrate_ex.py webstats.cdn.efly.cc /opt/cachemgr/portinfo_ex32 lo 127.0.0.1 3000 4000 > /dev/null 2>&1\n";	
echo "*/5 * * * * /opt/cachemgr/web_node_cmd.py \"http://s.efly.cc/webadmin/server/web_node_cmd.php\" /dev/null 2>&1";

?>

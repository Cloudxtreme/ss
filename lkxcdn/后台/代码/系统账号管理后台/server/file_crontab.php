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
$ifdesc = $row['ifdesc'];
mysql_free_result($result);	

echo "0 */1 * * * /opt/nginx_tools/nginx_log.py $serverip /opt/nginx_tools/config > /dev/null 2>&1\n";
echo "0 02 * * * /opt/nginx_tools/nginx_log_update.sh /opt/nginx_tools/config filestats.cdn.efly.cc > /dev/null 2>&1\n";
echo "*/5 * * * * /opt/cachemgr/portrate_ex.py filestats.cdn.efly.cc /opt/cachemgr/portinfo_ex32 $ifdesc $serverip 8000 9000 > /dev/null 2>&1\n";

?>

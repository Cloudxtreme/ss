<?php
//中文UTF-8

require_once('cdn_db.php');

$hostname_list = array();

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");

//get source file list
//////////////////////////////////////////////////
$query = "select * from $global_databasename.user_hostname;";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( mysql_num_rows($result) )
{
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$domainname = $row['domainname'];
		$hostname_list[$domainname] = $domainname;
	}
}
mysql_free_result($result);

#print_r($hostname_list);
foreach( $hostname_list as $domainname ) {
	print("$domainname\n");
}

?>

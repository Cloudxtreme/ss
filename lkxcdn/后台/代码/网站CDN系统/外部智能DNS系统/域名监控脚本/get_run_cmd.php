<?php
//中文UTF-8
require_once('db.php');

global $global_cdn_domain;

$dbobj = new DBObj;
if( ! $dbobj->conn() ) 
{
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

$query = "select * from zone_table;";
if( ! ($result = $dbobj->query($query)) ) 
{
	print($dbobj->error());
	exit;
}

if( ! mysql_num_rows($result) ) {
	continue;
}

$tables = array();

while( ($row = mysql_fetch_array($result)) ) 
{
	$table = $row['tablename'];
	$tables[$table] = $table;
}	
mysql_free_result($result);

//print_r($tables);

foreach( $tables as $table )
{
	$filename = "$table.sh";
	$handle = fopen($filename, 'w');
	fwrite($handle, "#!/bin/sh\n");
	fwrite($handle, "while [ 1 ]\n");
	fwrite($handle, "do\n");
	fwrite($handle, "\tphp dns_node_check.php $table >> log\n");
	fwrite($handle, "\tsleep 60\n");
	fwrite($handle, "done\n");
	fclose($handle);
	
	print("./$table.sh &\n");
}


?>

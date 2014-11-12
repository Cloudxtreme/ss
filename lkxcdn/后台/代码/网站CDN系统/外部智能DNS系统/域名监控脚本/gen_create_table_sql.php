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

$query = "show tables;";
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
	$table = $row[0];
	if( $table == 'dns_list' ) { continue; }
	if( $table == 'ip_list' ) { continue; }
	if( $table == 'zone_table' ) { continue; }
	
	if( strstr($table, 'mobile_') ) { continue; }
	$tables[$table] = $table;
}	
mysql_free_result($result);

foreach( $tables as $table ) {
	
	print("drop table $table;\n");
	/*
	print("CREATE TABLE `$table` (
`id` int(11) NOT NULL auto_increment, `name` varchar(255) default NULL,
`ttl` int(11) default NULL, `rdtype` varchar(255) default NULL,
`rdata` varchar(255) default NULL, `status` char(50) NOT NULL default 'true',
`desc` char(50) default NULL, PRIMARY KEY  (`id`) ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;\n");

	if( strstr($table, 'ct_') ) {
	print("INSERT INTO `$table` (`name`, `ttl`, `rdtype`, `rdata`, `status`, `desc`) VALUES
('cdn.rightgo.net', 300, 'SOA', 'ns.cdn.rightgo.net. root.cdn.rightgo.net. 2013031900 3600 900 3600 300', 'true', NULL),
('cdn.rightgo.net', 300, 'NS', 'ns.cdn.rightgo.net.', 'true', NULL),
('ns.cdn.rightgo.net', 300, 'A', '116.28.64.163', 'true', NULL), ('ns.cdn.rightgo.net', 300, 'A', '116.28.64.164', 'true', NULL);\n");
	} else if( strstr($table, 'cnc_') ) {
	print("INSERT INTO `$table` (`name`, `ttl`, `rdtype`, `rdata`, `status`, `desc`) VALUES
('cdn.rightgo.net', 300, 'SOA', 'ns.cdn.rightgo.net. root.cdn.rightgo.net. 2013031900 3600 900 3600 300', 'true', NULL),
('cdn.rightgo.net', 300, 'NS', 'ns.cdn.rightgo.net.', 'true', NULL),
('ns.cdn.rightgo.net', 300, 'A', '112.90.177.67', 'true', NULL), ('ns.cdn.rightgo.net', 300, 'A', '112.90.177.68', 'true', NULL);\n");
	} else {
	}
	*/
}

?>

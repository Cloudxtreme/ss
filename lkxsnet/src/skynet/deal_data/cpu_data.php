<?php
require_once('db.php');

global $global_databaseip, $global_databasename, $global_databaseuser, $global_databasepwd;
global $dev_info;

$db = new DBObj;
if( ! $db->conn2($global_databaseip, $global_databaseuser, $global_databasepwd) ) 
{
	print($ipratedb->error()."\n");
	return;
}

$db->query("set names utf8;");
$db->select_db($global_databasename);

$query = "select * from `node_cpu_data`;";
$result = $db->query($query);
if( ! $result ) {
	return;
}	
	
$rows = array();
$days = array();
while( ($row = mysql_fetch_array($result)) ) {
	$rows[] = $row;
	$day = $row['timestamp'];
	$day = explode(' ', $day);
	$days[$day[0]] = $day[0];
}
mysql_free_result($result);
//print_r($days);

$db->query('TRUNCATE TABLE `node_cpu_data`');

foreach( $days as $day ) {
	$query = "CREATE TABLE IF NOT EXISTS `node_cpu_data_$day` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`sid` char(50) NOT NULL,
			`uper` smallint(11) NOT NULL,
			`timestamp` timestamp NOT NULL,
			PRIMARY KEY (`id`),
			KEY `timestamp` (`timestamp`),
			KEY `sid` (`sid`)
			) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
	//print("$query\n");
	$db->query($query);
}

foreach( $rows as $row ) {
	//print_r($row);
	$sid =  $row['sid'];
	$uper = $row['uper'];
	$time = $row['timestamp'];
	$time = explode(' ', $time);
	$day = $time[0];
	$time = deal_time($time[1]);
	$timestamp = "$day $time";
	$query = "insert `node_cpu_data_$day` (`sid`, `uper`, `timestamp`) 
			values('$sid', '$uper', '$timestamp');";
	//print("$query\n");
	$db->query($query);
}

function deal_time($time)
{
	$hour = $min = $sec = 0;
	sscanf($time, "%02d:%02d:%02d", $hour, $min, $sec);
	$min = (int)($min / 5);
	$min = $min * 5;
	$ret = sprintf("%02d:%02d:%02d", $hour, $min, 0);
	//print("$time $ret\n");
	return $ret;
}

?>

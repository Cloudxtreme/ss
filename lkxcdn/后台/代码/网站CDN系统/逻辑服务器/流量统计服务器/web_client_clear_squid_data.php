<?php
require_once('cdn_db.php');

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	exit;
}

$day=date("Y-m-d", time());
$date = "$day 00:00:00";
print("$date \n");

$dbname = 'cdn_squid';
$dbobj->select_db($dbname);

$query = "delete from client_hit_ex where timestamp < '$date';";
print_r("$query \n");
$dbobj->query($query);

$query = "delete FROM `client_hit_ex` where reqhit = '0' and senthit = '0';";
print_r("$query \n");
$dbobj->query($query);

$day=date("Y-m-d", time() - 86400 * 30);
$date = "$day";
print("$date \n");

$query = "delete from client_traffic where `time` < '$date';";
print_r("$query \n");
$dbobj->query($query);

?>

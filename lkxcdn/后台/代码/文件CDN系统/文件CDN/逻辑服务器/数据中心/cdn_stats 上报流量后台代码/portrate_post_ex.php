<?php
require_once('cdn_db.php');

$ratepost = $_POST['ratepost'];
$ratepost = str_replace('[', '', $ratepost);
$ratepost = str_replace(']', '', $ratepost);
$ratepost = str_replace(' ', '', $ratepost);
$ratepost = str_replace(',', '', $ratepost);
$ratepost = str_replace("'", '', $ratepost);
$ratepost = explode('|', $ratepost);
print_r($ratepost);

$serverip = ($_SERVER['REMOTE_ADDR']);

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	exit;
}

$dbname = 'cdn_portrate_stats';
//print_r($dbname);
$dbobj->select_db($dbname);

$tablename = date('Y-m-d');
//print_r($tablename);

$query = "CREATE TABLE IF NOT EXISTS `$tablename` (
 				`id` int(11) NOT NULL AUTO_INCREMENT,
 				`ip` char(20) NOT NULL,
 				`port` int(11) NOT NULL,
 				`outrate` int(11) NOT NULL,
 				`inrate` int(11) NOT NULL,
 				`time` time NOT NULL,
 				PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8";

if( ! ($result = $dbobj->query($query)) ) {
	exit;
}

foreach( $ratepost as $info )
{
	$info = explode(':', $info);
	if( count($info) != 3 ) {
		continue;
	}
	print_r($info);
	$port = $info[0];
	$outrate = $info[1];
	$inrate = $info[2];

	if( $outrate == '0' && $inrate == '0' ) {
		continue;
	}

	$query = "insert into `$tablename` (`ip`, `port`, `outrate`, `inrate`, `time`) 
					values('$serverip', '$port', '$outrate', '$inrate', current_time);";
	print_r($query);					

	$result = $dbobj->query($query);
}

?>

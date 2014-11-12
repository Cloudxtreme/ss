<?php
require_once('cdn_db.php');

$synctimeout = 300;

$ratepost = $_POST['rateinfo'];
//print_r($ratepost);
$ratepost = json_decode($ratepost, true);
//print_r($ratepost);
if( ! $ratepost || ! count($ratepost) ) { exit; }

$serverip = ($_SERVER['REMOTE_ADDR']);
//print("$serverip\n");

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	exit;
}

$dbname = 'cdn_portrate_stats';
//print_r($dbname);
//$dbobj->select_db($dbname);

$tablename = date('Y-m-d');
//print_r($tablename);

$query = "CREATE TABLE IF NOT EXISTS cdn_portrate_stats.`$tablename` (
 				`id` int(11) NOT NULL AUTO_INCREMENT,
 				`ip` char(20) NOT NULL,
 				`hostname` CHAR( 100 ) NOT NULL,
 				`port` int(11) NOT NULL,
 				`outrate` bigint(20) NOT NULL,
 				`inrate` bigint(20) NOT NULL,
 				`time` time NOT NULL,
 				PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8";
if( ! ($result = $dbobj->query($query)) ) {
	//exit;
}

$query = "CREATE TABLE IF NOT EXISTS cdn_client_traffic.`$tablename` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`ip` char(20) NOT NULL,
				`hostname` CHAR( 100 ) NOT NULL,
				`port` int(11) NOT NULL,
				`traffic` bigint(20) NOT NULL,
				`time` time NOT NULL,
				PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$result = $dbobj->query($query);
if( ! ($result = $dbobj->query($query)) ) {
	//exit;
}

$query = "CREATE TABLE IF NOT EXISTS cdn_client_hit.`$tablename` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`ip` char(20) NOT NULL,
				`hostname` char(100) NOT NULL,
				`cnt` int(11) NOT NULL,
				`sent` bigint(20) NOT NULL,
				`hit_cnt` int(11) NOT NULL,
				`hit_sent` bigint(20) NOT NULL,
				`timestamp` time NOT NULL,
				PRIMARY KEY  (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$result = $dbobj->query($query);
if( ! ($result = $dbobj->query($query)) ) {
	//exit;
}

foreach( $ratepost as $hostname => $info ) 
{
	$cnt = $info['cnt'];
	$sent = $info['sent'];
	$hit_cnt = $info['hit_cnt'];
	$hit_sent = $info['hit_sent'];
	
	$rate = round($sent/$synctimeout, 0);
	if( $cnt == 0 ) {
		$reqhit = 0;
	} else {
		$reqhit = round($hit_cnt/$cnt*100, 0);
	}
	if( $sent == 0 ) {
		$senthit = 0;
	} else {
		$senthit = round($hit_sent/$sent*100, 0);
	}

    strtolower($hostname);
    if( ! check_domain($hostname) ) { continue; }

	//rate
	$query = "insert into cdn_portrate_stats.`$tablename` (`ip`, `hostname`, `outrate`, `inrate`, `time`) 
					values('$serverip', '$hostname', '$rate', '$sent', current_time);";
	//print("$query $result\n");
	$result = $dbobj->query($query);
	
	//sent
	$query = "insert into cdn_client_traffic.`$tablename`(`ip`, `hostname`, `traffic`, `time`) 
					values('$serverip', '$hostname', '$sent', now());";
	//print("$query $result\n");
	$result = $dbobj->query($query);
	
	//hit
	$query = "insert into cdn_squid.client_hit_ex(`ip`, `hostname`, `reqhit`, `senthit`, `timestamp`)
					values('$serverip', '$hostname', '$reqhit', '$senthit', now()) ON DUPLICATE KEY UPDATE
      	  `reqhit` = '$reqhit', `senthit` = '$senthit', `timestamp` = now();";
	//print("$query $result\n");
	$result = $dbobj->query($query);	
	
	//hit
	$query = "insert into cdn_client_hit.`$tablename`(`ip`, `hostname`, `cnt`, `sent`, `hit_cnt`, `hit_sent`, `timestamp`)
					values('$serverip', '$hostname', '$cnt', '$sent', '$hit_cnt', '$hit_sent', now());";
	//print("$query $result\n");
	$result = $dbobj->query($query);		
}

function check_domain($hostname)
{
    for( $i = 0; $i < strlen($hostname); $i++ ) {
        if( $hostname[$i] != '-' &&
            $hostname[$i] != '.' &&
            ! ( $hostname[$i] >= '0' && $hostname[$i] <= '9' ) &&
            ! ( $hostname[$i] >= 'a' && $hostname[$i] <= 'z' ) ) {
            return false;
        }
    }
    return true;
}

?>

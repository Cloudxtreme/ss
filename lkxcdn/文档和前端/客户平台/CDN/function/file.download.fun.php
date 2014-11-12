<?php
require_once('./file.com.fun.php');
require_once('./log.fun.php');
error_reporting(E_ALL^E_NOTICE);
//print_r($_POST);
if( ! isset($_POST['get_type']) ) { exit; }
$client = '';

switch( $_POST['get_type'] ) {
	
	case "_init":
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { 
			$client = $_POST['user']; 
		}
		//print_r($client);
		print_r(get_client_hostname_list($client));print('***');		
		//print_r(get_cdn_zone_list());print('***');
		//print_r(get_cdn_isp_list());
		exit;
		break;
		
	case "_download":
		//print_r($_POST);
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { 
			$client = $_POST['user']; 
		}
		break;    
		
	default :  
		exit; 
		break;
}
if( $client == '' ) { exit; }
$client_type = get_client_type($client);

$begin_day = $end_day = '';
$zones = array('中国大陆');
$isps = array('电信', '联通', '移动', '教育网', '其它');
$channels = array();

$post_time = json_decode($_POST['time'], true);
$post_channel = json_decode($_POST['channel'], true);
if( isset($_POST['isp']) ) { $isps = json_decode($_POST['isp'], true); }
if( count($post_time) != 2 ) { exit; }
$begin_day = $post_time[0];
$end_day = $post_time[1];
if( count($post_channel) <= 0 ) { exit; }
$channels = $post_channel;
//print_r($post_time); print_r($post_channel); print($client);
syslog_user_action($client,$_SERVER['SCRIPT_NAME'],$channels,$begin_day,$end_day);

$mysql_class = new MySQL('newcdnfile');

$mysql_class -> opendb("cdn_file_download_stats", "utf8");

$hostnames_flow = array(); //获取域名列表的总流量

$query = gen_select_hostname_ins_sql($begin_day, $end_day, $client, $channels);
$result = $mysql_class->query($query);
if( ! $result ) { exit; }

$res = array();
while( ($row = mysql_fetch_array($result)) ) {
	
	$file = $row['download_file'];
	$succ = $row['download_succ_count'];
	$fail = $row['download_fail_count'];
	if (isset($res[$file]))
	{
		$res[$file]['succ'] += $succ;
		$res[$file]['fail'] += $fail;
	}
	else
	{
		$res[$file] = array('succ'=>$succ, 'fail'=>$fail);
	}
}
mysql_free_result($result);

print_r(json_encode($res));

//function
///////////////////////////////////////////////////////////////////////////////

function gen_select_hostname_ins_sql($begin_day, $end_day, $client, $channels)
{
	$query = "select * from `file_download_stats` where `user` = '$client' and ";
	$query .= "`download_date` between '$begin_day' and '$end_day' and (";
	foreach( $channels as $hostname ) {
		$query .= " `download_file` like '/$hostname%' or";
	}
	$query = substr($query, 0, -2);
	$query .= ");";
	return $query;
}

?>
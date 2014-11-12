<?php
require_once('./web.com.fun.php');
require_once('../data/iplocation.class.php');

error_reporting(E_ALL^E_NOTICE);

switch( $_POST['get_type'] ) 
{
	case "_init":
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { 
			$client = $_POST['user']; 
		}
		print_r(get_client_hostname_list($client));print('***');		
		print_r(get_cdn_zone_list());print('***');
		exit;
		break;
		
	case "_visitor":
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { 
			$client = $_POST['user']; 
		}
		break;    
		
	default :  
		exit; 
		break;
		
}

if( $client == '' ) { exit; }

$begin_day = $end_day = '';
$zones = array('中国大陆');
$channels = array();

$post_time = json_decode($_POST['time'], true);
$post_channel = json_decode($_POST['channel'], true);
if( count($post_time) != 2 ) { exit; }
$begin_day = $post_time[0];
$end_day = $post_time[1];
if( count($post_channel) <= 0 ) { exit; }
$channels = $post_channel;
//print_r($post_time); print_r($post_channel); print($client);

$days_ip_ret = array();

//先根据要查询的开始结束日期获取以天为单位的天列表
$days = array(); //天列表
for( $bday = @strtotime($begin_day); $bday <= @strtotime($end_day); ) {
	$day = @date("Y-m-d", $bday);
	$bday += 86400;
	$days[] = $day;
	//顺便把$days_ip_ret都初始化好,24小时
	for( $i = 0; $i < 24; $i++ ) { $days_ip_ret[$day][$i] = 0; }
}
$months = days_2_months($days);
//print_r($months); print_r($days);

//独立ip按时间统计
//////////////////////////////////////////////////////////////
$mysql_class = new MySQL('newcdn');
$mysql_class->opendb("cdn_web_log_general", "utf8");

if( $begin_day != $end_day ) {
	//如果查询的是超过1天的，则独立IP按天统计
	$day_ipcnt_ret = array();
	foreach( $days as $day ) 
	{
		$query = gensql_hostname_ipday_cnt($day, $channels);
		//print($query);
		$result = $mysql_class->query($query);
		if($result)
		{
			while( ($row = mysql_fetch_array($result)) ) 
			{
				$cnt = $row[0];
				$day_ipcnt_ret[] = array('date' => $day, 'value' => $cnt);
			}
			mysql_free_result($result);
		}
		else
		{
			$day_ipcnt_ret[] = array('date' => $day, 'value' => '0');
		}
	}
	print_r(json_encode($day_ipcnt_ret));		
}
else {
	//如果查询的是一天，则独立IP按小时统计
	$hour_ipcnt_ret = array();
	for ($i = 0;$i < 24; $i ++)
	{
		$hour = sprintf("%02d", $i);
		$days_hour_ipcnt_ret[$i] = array('date' => $hour, 'value' => '0');
	}
	$query = gensql_hostname_iphour_cnt($begin_day, $channels);
	//print($query);
	$result = $mysql_class->query($query);
	if ($result)
	{
	while( ($row = mysql_fetch_array($result)) ) {
		$time = $row[0];
		$cnt = $row[1];
		$hour = get_time_hour($time);
		$hour = sprintf("%02d", $hour);
		$num = sprintf("%d",$hour);
		//$days_hour_ipcnt_ret[] = array('date' => $hour, 'value' => $cnt);
		$days_hour_ipcnt_ret[$num]['date'] = $hour;
		$days_hour_ipcnt_ret[$num]['value'] = $cnt;
	}
	mysql_free_result($result);
	}
	print_r(json_encode($days_hour_ipcnt_ret));
}
print('***');

//TOP10 访客按请求数排行
//////////////////////////////////////////////////////////////
$days_ipreq_ret = array();
$ip_req_ret = array();
foreach( $months as $month => $mdays ) {
	$query = gensql_hostname_ipreq_cnt($month, $mdays, $channels);
	//print($query);
	$result = $mysql_class->query($query);
	if ($result)
	{
		while( ($row = mysql_fetch_array($result)) ) {
		$ip = $row['ip'];
		$cnt = $row['cnt'];
		$sent = $row['send'];
		if( isset($days_ipreq_ret[$ip]) ) {
			$days_ipreq_ret[$ip]['cnt'] += $cnt;
			$days_ipreq_ret[$ip]['sent'] += $sent;
			$ip_req_ret[$ip] += $cnt;
		} else {
			$days_ipreq_ret[$ip] = array('cnt' => $cnt, 'sent' => $sent);
			$ip_req_ret[$ip] = $cnt;
		}
		}
		mysql_free_result($result);
	}
}
arsort($ip_req_ret);
$i = 0;
$ip_req_data = array();
$ipl = new ipLocation('../data/qqwry.dat'); //用纯真库获取ip归属信息
foreach( $ip_req_ret as $ip => $cnt ) {
	$sent = $days_ipreq_ret[$ip]['sent'];
	//print("[$ip $cnt $sent] ");
	$sent = round($sent / 1024, 2);
	
	$address = $ipl->getaddress($ip);
	$address["area1"] = iconv('GB2312', 'utf-8', $address["area1"]);
	$address["area2"] = iconv('GB2312', 'utf-8', $address["area2"]);
	//print_r($address);
	$ip_nettype = $address["area2"];
	$ip_zone = $address["area1"];
	$ipinfo = "$ip_zone $ip_nettype";
	
	$ip_req_data[] = array('ip' => $ip, 'ipinfo' => $ipinfo, 'cnt' => $cnt, 'flow' => $sent);
	if( ++$i >= 10 ) { break; }
}

if(count($ip_req_data) == 0)
{
	$ip_req_data[] = array('ip' => '#', 'ipinfo' => '-', 'cnt' => '-', 'flow' => '-');
}
print_r(json_encode($ip_req_data));

//function
//////////////////////////////////////////////////////////////////

function gensql_hostname_ipday_cnt($table, $channels)
{
	$query = "select sum(ip) from `$table".'_gen`'." where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';
	$query .= ';';
	return $query;
}

function gensql_hostname_iphour_cnt($table, $channels)
{
	$query = "select `time`, sum(ip) from `$table".'_gen`'." where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';
	$query .= ' group by `time`;';
	return $query;
}

function gensql_hostname_ipreq_cnt($table, $mdays, $channels)
{
	$query = "select * from `$table".'_ip`'." where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= ' and `date` in (';
	foreach( $mdays as $day ) {
		$query .= "'$day',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= ';';
	return $query;
}

function days_2_months($days)
{
	$months = array();
	foreach( $days as $day ) {
		$year = $month = $iday= 0;
		sscanf($day, "%d-%d-%d", $year, $month, $iday);
		$ym = sprintf("%d-%02d", $year, $month);
		$months[$ym][] = $day;
	}
	return $months;
}

function get_time_hour($time)
{
	$hour = $min = $sec = 0;
	sscanf($time, "%d:%d:%d", $hour, $min, $sec);
	return $hour;
}

?>
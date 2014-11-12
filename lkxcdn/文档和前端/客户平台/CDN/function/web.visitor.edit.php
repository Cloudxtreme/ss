<?php

ini_set("soap.wsdl_cache_enabled", "0"); 
date_default_timezone_set('Asia/Shanghai');
require_once('./web.com.fun.php');
require_once('../data/iplocation.class.php');
$GLOBALS['THRIFT_ROOT'] = './php/src';  

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


$req = new SoapClient("http://116.28.65.253:8080/cdn/services/url?wsdl");
if( $begin_day != $end_day ) {

	
	//如果查询的是超过1天的，则独立IP按天统计
	$day_ipcnt_ret = array();
	$day_print = array();
	
	$argumensts = array();
	$argumensts[0] = $channels;
	$argumensts[1] = $begin_day;
	$argumensts[2] = $end_day;
	$argumensts[3] = "ip";
	
	$arrResult = $req->__Call("get_gen_date",$argumensts);
	
	/*
	if (count($arrResult) == 0)
	{
		exit;
	}
	*/
	
	foreach( $days as $day ) 
	{
		$day_ipcnt_ret[$day] = 0;
	}
		
	if (count($arrResult) != 0)
	{
		foreach($arrResult as $values)
		{
			$values = explode("\t",$values);
			$date = $values[0];
			//if (strlen($url) == 0){continue;}
			$ip = $values[1];
		
			$day_ipcnt_ret[$date] = $ip;
		
		}
	}
	
	foreach($day_ipcnt_ret as $date => $ip)
	{
		$day_print[] = array('date' => $date, 'value' => $ip);
	}
	
	print_r(json_encode($day_print));		
}
else 
{
	/*
	//如果查询的是一天，则独立IP按小时统计
	$days_hour_ipcnt_ret = array();
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
	
	*/
	$argumensts = array();
	$argumensts[0] = $channels;
	$argumensts[1] = $begin_day;
	$argumensts[2] = "ip";
	
	
	$arrResult = $req->__Call("get_gen_time",$argumensts);
		
	$days_hour_ipcnt_ret = array();
	for ($i = 0;$i < 24; $i ++)
	{
		$hour = sprintf("%02d", $i);
		$days_hour_ipcnt_ret[$i] = array('date' => $hour, 'value' => '0');
	}
	

	if (count($arrResult) != 0)
	{
		foreach($arrResult as $values)
		{
			$values = explode("\t",$values);
			$time = $values[0];
			//if (strlen($url) == 0){continue;}
			$ip = $values[1];
			$hour = get_time_hour($time);
			$num = sprintf("%d",$hour);
			$hour = sprintf("%02d", $hour);
			$days_hour_ipcnt_ret[$num]['date'] = $hour;
			$days_hour_ipcnt_ret[$num]['value'] = $ip;
		
		}
	}
	print_r(json_encode($days_hour_ipcnt_ret));

}
print('***');

//TOP10 访客按请求数排行
//////////////////////////////////////////////////////////////

unset($argumensts);
$argumensts[0] = $channels;
$argumensts[1] = $begin_day;
$argumensts[2] = $end_day;

$arrResult = $req->__Call("get_gen_ip",$argumensts);

if (count($arrResult) == 0)
{
	$ip_req_data[] = array('ip' => '#', 'ipinfo' => '-', 'cnt' => '-', 'flow' => '-');
	print_r(json_encode($ip_req_data));
	exit;
}


$ip_req_data = array();

$ipl = new ipLocation('../data/qqwry.dat'); //用纯真库获取ip归属信息
foreach($arrResult as $values)
{
	$values = explode("\t",$values);

	$ip = $values[0];
	$cnt = $values[1];
	$sent = round( $values[2]/ 1024, 2);
	$address = $ipl->getaddress($ip);
	$address["area1"] = iconv('GB2312', 'utf-8', $address["area1"]);
	$address["area2"] = iconv('GB2312', 'utf-8', $address["area2"]);

	$ip_nettype = $address["area2"];
	$ip_zone = $address["area1"];
	$ipinfo = "$ip_zone $ip_nettype";
		
	$ip_req_data[] = array('ip' => $ip, 'ipinfo' => $ipinfo, 'cnt' => $cnt, 'flow' => $sent);
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
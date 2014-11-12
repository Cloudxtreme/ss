<?php
require_once('./auth.php');
require_once('./web.com.fun.php');

ini_set("soap.wsdl_cache_enabled", "0"); 
error_reporting(E_ALL^E_NOTICE);

if (user_auth($_POST['user'],$_POST['pass']))
{
	echo "<script language='javascript'>alert('用户名或密码错误，请联系管理员！');</script>".$_POST['user']."   ".$_POST['pass']; 
	exit;
}

if( ! isset($_POST['get_type']) ) { exit; }
$client = '';

switch( $_POST['get_type'] ) {
	
	case "_init":
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { 
			$client = $_POST['user']; 
		}
		//print_r($client);
		print_r(get_client_hostname_list($client));print('***');		
		print_r(get_cdn_zone_list());print('***');
		print_r(get_cdn_isp_list());
		exit;
		break;
		
	case "_bandwidth":
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

$begin_day = $end_day = '';
$zones = array('中国大陆');
$isps = array('电信', '网通', '移动');
$channels = array();
//print($_POST['time'] ."   ".$_POST['channel']."  ".$_POST['isp'] );
$post_time = json_decode(stripslashes($_POST['time']), true);
$post_channel = json_decode(stripslashes($_POST['channel']), true);
if( isset($_POST['isp']) ) { $isps = json_decode(stripslashes($_POST['isp']), true); }

if( count($post_time) != 2 ) { exit; } 	
$begin_day = $post_time[0];
$end_day = $post_time[1];
if( count($post_channel) <= 0 ) { exit; }
$channels = analyse_hostname_list($post_channel);
//print_r($post_time); print_r($post_channel); print($client);

//先根据要查询的开始结束日期获取以天为单位的天列表
$days = array(); //天列表
$days_ret =  array(); //每天5分钟数据的结果汇总
$days_prt = array();
for( $bday = @strtotime($begin_day); $bday <= @strtotime($end_day); ) {
	$day = @date("Y-m-d", $bday);
	$bday += 86400;
	$days[] = $day;
	for( $i = 0; $i < 288; $i++ ) {
		$days_ret[$day][$i] = 0;
	}
}

$soap = new SoapClient("http://116.28.65.253:8080/cdn/services/url?wsdl");
//print_r($days);

$top_time = array();

$argumensts = array();
$argumensts[0] = $channels;
$argumensts[1] = $isps;
$argumensts[2] = $begin_day;
$argumensts[3] = $end_day;

	
$arrResult = $soap->__Call("get_gen_bandwide",$argumensts);
//print_r($arrResult);


if (count($arrResult) != 0)
{
	foreach($arrResult as $values)
	{
		$data = explode("\t",$values);
		$date = $data[0];
		$time = $data[1];
		$bandwide = $data[2];
		$idx = time_2_idx($time, 5);
		$days_ret[$date][$idx] += $bandwide;
		$days_prt[$date][$time] += $bandwide;
		$top_time["$date $time"] += $bandwide;

	}
}

//print_r($days_prt);
//exit;

$daycnt = count($days);
$timedelta = 5; //默认5分钟时间间隔
if( $daycnt <= 2 ) {
	//小于等于2天的，我们的时间间隔是5分钟
	$timedelta = 5;
} else if( $daycnt > 2 && $daycnt <= 7 ) {
	//大于2天或者一个星期的，我们就用30分钟作为时间间隔
	$timedelta = 30;
} else {
	//大于1个星期或者月的，其它情况我们就用2个小时时间间隔
	$timedelta = 120;
}
$daytimecnt = 24 * 60 / $timedelta; //计算出最终结果一天有多少个时间点

//进行最终结果的整理
$days_data = array(); //流量图--保存最终结果
foreach( $days_ret as $day => $dayret ) {
	foreach( $dayret as $idx => $bandwidth ) {
		$time5min = idx_2_time($idx, 5);
		if( $timedelta == 5 ) {
			//如果最终结果是5分钟时间间隔的，则不需要做啥处理了，直接赋值最终结果即可
			$data_key = "$day $time5min";
			$days_data[$data_key] = $bandwidth;
		} else {
			//如果不是5分钟的话就要做一些转换和处理了
			//1.先把5分钟时间转换成对应的时间间隔
			$data_idx = time_2_idx($time5min, $timedelta);
			//print("$time5min $data_idx <br>");
			$data_time = idx_2_time($data_idx, $timedelta);
			
			//2.我们要保留最大峰值，所以这里就只用最大值即可
			$data_key = "$day $data_time";
			if( ! isset($days_data[$data_key]) ) {
				$days_data[$data_key] = $bandwidth;
			} else {
				if( $bandwidth > $days_data[$data_key] ) {
					$days_data[$data_key] = $bandwidth;
				}
			}
		}
	}
}

//print_r($days_data);exit;

//3次处理
$days_key_ret = $days_value_ret = array();
foreach( $days_data as $key => $value ) 
{
	$days_key_ret[] = $key;
	$days_value_ret[] = round($value * 8 / 1024 / 1024 * 1.2, 2);
}
print_r(json_encode($days_key_ret));
print('***');
print_r(json_encode($days_value_ret));

print('***');

unset($arrResult);

	
$arrResult = $soap->__Call("get_domain_max_bandwide",$argumensts);

$top_ret = array();

if (count($arrResult) != 0)
{
	foreach($arrResult as $values)
	{
		$data = explode("\t",$values);
		$channel = $data[0];
		$time = $data[1];
		$bandwide = round($data[2] * 8/ 1024 / 1024 * 1.2, 2);
		$flow = round($data[3]/1024/1024, 2);
		$top_ret[$channel] = array('bandwide'=>$bandwide, 'flow'=>$flow, 'time'=>$time);
	}
}
//print_r($arrResult);
//exit;

arsort($top_ret);

$top_data = array();
$sum_flow = 0;
$i = 0;
foreach( $top_ret as $hostname => $values ) {
	$timestamp = $values['time'];
	$bandwidth = $values['bandwide'];
	$total_flow = $values['flow'];
	$sum_flow += $total_flow;
	$top_data[] = array($hostname, $bandwidth, $timestamp, $total_flow);
	$i++;
	if ($i == 10)
		break;
}
if(count($top_data) == 0)
{
	$top_data[] = array('#', '--', '--', 0);
}
print_r(json_encode($top_data));
print('***');

$top_num = count($top_time);
$top_num = round($top_num * 0.05,0);
arsort($top_time);

$cnt = 0;
$max= 0;
$topth = 0;
foreach($top_time as $time => $value)
{   
    	$cnt ++;
	if ($cnt == $top_num)
	{
		$topth = $value;
	}
	if ($value > $max)
	{
		$max = $value;
		$max_time = $time;
	}
}

print_r($max_time);
print_r('***');
print_r(json_encode(round($max * 8/1024/1024*1.2,2)));
print_r('***');
print_r(json_encode(round($topth * 8/1024/1024*1.2,2)));
print_r('***');
print_r(json_encode(round($sum_flow/1024,2)));
print_r('***');

//function
///////////////////////////////////////////////////////////////////////////////

function gen_select_hostname_ins_sql($table, $channels, $isps)
{
	$query = "select * from `$table` where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= ' and `ip` in (';
	foreach( $isps as $isp ) {
		$query .= "'$isp',";
	}
	$query[strlen($query)-1] = ')';	
	$query .= ';';
	return $query;
}

//把时间戳转换成时间间隔的数组下标
function time_2_idx($time, $timedelta)
{
	$hour = $min = $sec = 0;
	sscanf($time, "%d:%d:%d", $hour, $min, $sec);
	//print("$hour $min $sec".'<br>');
	if( $timedelta < 60 ) {
		$idx = $hour * (60 / $timedelta) + (int)($min / $timedelta);
	} else {
		$idx = (int)($hour * 60 / $timedelta);
	}
	return $idx;
}

//把时间间隔的数组下标转换成时间戳
function idx_2_time($idx, $timedelta)
{
	if( $timedelta < 60 ) {
		$hour = (int)($idx * $timedelta / 60);
		$min = $idx * $timedelta - $hour * 60;
	} else {
		$hour = (int)($idx * $timedelta / 60);
		$min = 0;
	}
	$time = sprintf("%02d:%02d:%02d", $hour, $min, 0);
	return $time;
}

?>

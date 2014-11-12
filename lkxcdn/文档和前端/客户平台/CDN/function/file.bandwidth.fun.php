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
$client_type = get_client_type($client);

$begin_day = $end_day = '';
$zones = array('中国大陆');
$isps = array('电信', '联通', '移动', '教育网','长宽','其它');
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

//先根据要查询的开始结束日期获取以天为单位的天列表
$days = array(); //天列表
$days_ret =  array(); //每天5分钟数据的结果汇总
for( $bday = @strtotime($begin_day); $bday <= @strtotime($end_day); ) {
	$day = @date("Y-m-d", $bday);
	$bday += 86400;
	$days[] = $day;
	for( $i = 0; $i < 288; $i++ ) {
		$days_ret[$day][$i] = 0;
	}
}
//print_r($days);

$mysql_class = new MySQL('newcdnfile');

$mysql_class -> opendb("cdn_portrate_stats_new", "utf8");

$hostnames_flow = array(); //获取域名列表的总流量
$top_ret = array();  //top频道--保存最终结果
$top_time = array();

//然后一天天地查询出指定域名的数据，5分钟为时间间隔
foreach( $days as $day ) {
	if( $client_type == 'cdnfile_hot' ) {
		$query = gen_select_hostname_ins_sql($day, $client, $channels, $isps);
	} else {
		$query = gen_select_client_ins_sql($day, $client, $isps);
	}
	//print("$query <br>");
	$result = $mysql_class->query($query);
	if ($result)
	{
	while( ($row = mysql_fetch_array($result)) ) {
		$isp = $row['ip'];
		$hostname = $row['hostname'];
		//$bandwidth = $row['bandwidth']; //这个outrate保存的是带宽值
		$flow = $row['flow']; //历史原因，这个inrate保存的其实就是流量值
		$bandwidth = round($flow / 300, 2); //5分钟的总流量/300秒
		$time = $row['time'];
		$idx = time_2_idx($time, 5);
		$days_ret[$day][$idx] += $bandwidth;
		//$top_time["$day $time"] += $bandwidth;
		//print("$day $isp $time $idx $bandwidth <br>");
		
		//统计域名总流量
		if( isset($hostnames_flow[$hostname]) ) {
			$hostnames_flow[$hostname] += $flow;
		} else {
			$hostnames_flow[$hostname] = $flow;
		}
		
		//计算域名top
		$timestamp = "$day $time";
		if( ! isset($top_ret[$hostname]) || 
				! isset($top_ret[$hostname][$timestamp]) ) {
				$top_ret[$hostname][$timestamp] = $bandwidth;
		} else {
			$top_ret[$hostname][$timestamp] += $bandwidth;
		}
	}
	mysql_free_result($result);
	}
}
//print_r($days_ret);exit;

//计算域名top排名
$top_ret_ex = array();
$hostname_top = array();
foreach( $top_ret as $hostname => $datas ) {
	$tempsort = $datas;
	arsort($tempsort);
	$tempkeys = array_keys($tempsort);
	$timestamp = $tempkeys[0];
	$bandwidth = $top_ret[$hostname][$timestamp];
	$hostname_top[$hostname] = $bandwidth;
	$top_ret_ex[$hostname] = array('timestamp' => $timestamp, 'bandwidth' => $bandwidth);
}
arsort($hostname_top);

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
		$top_time["$day $time5min"] += $bandwidth;
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
//print_r($days_data); exit;

//3次处理
$days_key_ret = $days_value_ret = array();
foreach( $days_data as $key => $value ) {
	$days_key_ret[] = $key;
	$days_value_ret[] = round($value * 8 / 1024 / 1024 * 1.2, 2);
}
print_r(json_encode($days_key_ret));
print('***');
print_r(json_encode($days_value_ret));
print('***');
$top_data = array();
$sum_flow = 0;
foreach( $hostname_top as $hostname => $bandwidth ) {
	$timestamp = $top_ret_ex[$hostname]['timestamp'];
	$bandwidth = $top_ret_ex[$hostname]['bandwidth'];
	$bandwidth = round($bandwidth * 8 / 1024 / 1024, 2);
	$total_flow = $hostnames_flow[$hostname];
	$total_flow = round($total_flow / 1024 / 1024, 2);
	$sum_flow += $total_flow;
	$top_data[] = array($hostname, $bandwidth, $timestamp, $total_flow);
}
if(count($top_data) == 0)
{
	$top_data[] = array('#', '-', '-', 0);
}
print_r(json_encode($top_data));
print('***');

$top_num = count($top_time);
$top_num = round($top_num * 0.05,0);
arsort($top_time);

$cnt = 0;
$max = 0;
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

//这个是针对旧版客户的，没有域名统计带宽
function gen_select_client_ins_sql($table, $client, $isps)
{
	$query = "select * from `$table` where `user` = '$client'";
	$query .= ' and `ip` in (';
	foreach( $isps as $isp ) {
		$query .= "'$isp',";
	}
	$query[strlen($query)-1] = ')';	
	$query .= ';';
	return $query;
}

function gen_select_hostname_ins_sql($table, $client, $channels, $isps)
{
	$query = "select * from `$table` where `user` = '$client' and `hostname` in (";
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

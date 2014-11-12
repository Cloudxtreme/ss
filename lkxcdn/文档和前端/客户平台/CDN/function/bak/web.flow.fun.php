﻿<?php
require_once('./web.com.fun.php');

switch( $_POST['get_type'] ) {
	
	case "_init":
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { 
			$client = $_POST['user']; 
		}
		//print_r($client);
		print_r(get_client_hostname_list($client));print('***');		
		print_r(get_cdn_zone_list());print('***');
		exit;
		break;
		
	case "_flow":
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
$channels = array();

$post_time = json_decode($_POST['time'], true);
$post_channel = json_decode($_POST['channel'], true);
if( count($post_time) != 2 ) { exit; }
$begin_day = $post_time[0];
$end_day = $post_time[1];
if( count($post_channel) <= 0 ) { exit; }
$channels = analyse_hostname_list($post_channel);
//print_r($post_time); print_r($post_channel); print($client);

//流量天汇总，流量峰值汇总
$days_flow_ret = $days_bandwidth_ret = array();

//先根据要查询的开始结束日期获取以天为单位的天列表
$days = array(); //天列表
for( $bday = @strtotime($begin_day); $bday <= @strtotime($end_day); ) {
	$day = @date("Y-m-d", $bday);
	$bday += 86400;
	$days[] = $day;
	for( $i = 0; $i < 288; $i++ ) {
		$time = idx_2_time($i, 5);
		$days_bandwidth_ret[$day][$time] = 0;
	}
	//顺便初始化天数据
	$days_flow_ret[$day] = 0;
}
//print_r($days);

//流量类型比例
$mysql_class = new MySQL('webstats');
$mysql_class -> opendb("cdn_client_hit", "utf8");

$hit_ret = array();
foreach( $days as $day ) {
	$query = gensql_hostname_hit($day, $channels);
	//print("$query <br>");
	$result = $mysql_class->query($query);
	if($result)
	{
		while( ($row = mysql_fetch_array($result)) ) {
			$isp = $row[0];
			$sent = round($row[1] / 1024 / 1024, 2);
			$hit_sent = round($row[2] / 1024 / 1024, 2);
			if( isset($hit_ret[$isp]) ) {
				$hit_ret[$isp]['sent'] += $sent;
				$hit_ret[$isp]['hit_sent'] += $hit_sent;
			} else {
				$hit_ret[$isp] = array('sent' => $sent, 'hit_sent' => $hit_sent);
			}
		}
		mysql_free_result($result);
	}
}
//print_r($hit_ret);

//统计流量比例
//********************************************************************
$total_sent = $total_hit_sent = 0;
foreach( $hit_ret as $isp => $info ) {
	$total_sent += $info['sent'];
	$total_hit_sent += $info['hit_sent'];
}
//print_r("$total_sent $total_hit_sent");
if ($total_sent == 0)
{
	$node_src_per_ret[] = array('type' => 'Null', 'value' => 100);

}
else
{
	$node_per = round($total_hit_sent / $total_sent, 4) * 100;
	$src_per =  round( ($total_sent - $total_hit_sent) / $total_sent, 4) * 100;
	$node_src_per_ret[] = array('type' => 'node', 'value' => $node_per);
	$node_src_per_ret[] = array('type' => 'src', 'value' => $src_per);
}
print(json_encode($node_src_per_ret));
print('***');

//统计ISP比例
//********************************************************************
$isp_per_ret = array();
foreach( $hit_ret as $isp => &$info ) {
	$info['per'] = round($info['sent'] / $total_sent, 4) * 100;
	$isp_per_ret[] = array('type' => $isp, 'value' => $info['per']);
}
if(count($isp_per_ret)==0)
{
	$isp_per_ret[] = array('type' => 'Null', 'value' => 100);
}
print_r(json_encode($isp_per_ret));
print('***');

//流量按天统计
//********************************************************************
$mysql_class -> opendb("cdn_portrate_stats", "utf8");

//$days_flow_ret = $days_bandwidth_ret = array();

foreach( $days as $day ) {
	$query = gensql_hostname_bandwidth_flow($day, $channels);
	//print($query);
	$result = $mysql_class->query($query);
	if ($result)
	{
	while( ($row = mysql_fetch_array($result)) ) {
		//$isp = $row['ip'];
		$hostname = $row['hostname'];
		$bandwidth = $row['outrate']; //这个outrate保存的是带宽值
		$flow = $row['inrate']; //历史原因，这个inrate保存的其实就是流量值
		$time = $row['time'];
		$days_flow_ret[$day] += $flow;
		$days_bandwidth_ret[$day][$time] += $bandwidth;
	}
	mysql_free_result($result);
	}
}
//print_r($days_flow_ret);
//print_r($days_bandwidth_ret);

$days_bandwidth_data = array();
foreach( $days as $day ) {
	$day_flow = $days_flow_ret[$day]; //得到天的总流量
	//要获取这天的峰值出现时间及峰值
	$tempsort = $days_bandwidth_ret[$day];
	arsort($tempsort);
	//print_r($tempsort);
	$toptime = array_keys($tempsort);
	$toptime = $toptime[0];
	//print_r($tempsort[$toptime]);
	$bandwidth = round($tempsort[$toptime] * 8 / 1024 / 1024 * 1.2, 2);
	$datetime = "$day $toptime";
	$days_bandwidth_data[$day] = array('date' => $day, 'datetime' => $datetime, 'bandwidth' => $bandwidth);
}
arsort($days_flow_ret);
$days_flow_bandwidth_ret = array();
foreach( $days_flow_ret as $day => $flow ) {
	$mflow = round($flow / 1024 / 1024, 2);
	$datetime = $days_bandwidth_data[$day]['datetime'];
	$bandwidth = $days_bandwidth_data[$day]['bandwidth'];
	$days_flow_bandwidth_ret[] = array('day' => $day, 'flow' => $mflow, 'bandwidth' => $bandwidth, 'datetime' => $datetime);
}
print_r(json_encode($days_flow_bandwidth_ret));
print('***');

//可以释放了
unset($mysql_class);

//TOP10省份按流量排行
//********************************************************************
$mysql_classa = new MySQL('newcdn');
$mysql_classa -> opendb("cdn_web_log_general", "utf8");

//国家地区按流量排行
//********************************************************************
$days_province_flow_ret = array();
$months = days_2_months($days);
$total_flow = 0;
foreach( $months as $month => $mdays ) {
	$query = gensql_hostname_province_flow($month, $mdays, $channels);
	//print($query);
	$result = $mysql_classa->query($query);
	while( ($row = mysql_fetch_array($result)) ) {
		$province = $row[0];
		$flow = $row[1];
		$total_flow += round($flow / 1024 / 1024, 2);
		if( isset($days_province_flow_ret[$province]) ) {
			$days_province_flow_ret[$province] += round($flow / 1024 / 1024, 2);
		} else {
			$days_province_flow_ret[$province] = round($flow / 1024 / 1024, 2);
		}
	}
	mysql_free_result($result);
}
arsort($days_province_flow_ret);
//print_r($days_province_flow_ret);
$province_top_ret = array();
foreach( $days_province_flow_ret as $province => $flow ) {
	$per = round($flow / $total_flow, 4) * 100;
	if( $province == '中国' ) { $province = '国内其它'; }
	$province_top_ret[] = array('province' => $province, 'per' => $per);
}
if(count($province_top_ret) == 0)
{
	$province_top_ret[] = array('province' => '#', 'per' => '-');
}
print_r(json_encode($province_top_ret));

//可以释放了
unset($mysql_classa);

/*
mysql> select count(*) from
    -> (select * from `2013-04_gen` where hostname='image13-c.poco.cn'
    -> union all
    -> select * from `2013-05_gen` where hostname='image13-c.poco.cn'
    -> ) as total;
*/

//function
///////////////////////////////////////////////////////////////////////////////

function gensql_hostname_hit($table, $channels)
{
	$query = "select ip, sum(sent), sum(hit_sent) from `$table` where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';
	$query .= ' group by ip;';
	return $query;
}

function gensql_hostname_bandwidth_flow($table, $channels)
{
	$query = "select * from `$table` where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';
	$query .= ';';
	return $query;
}

function gensql_hostname_province_flow($table, $mdays, $channels)
{
	$query = "select province, sum(send) from `$table".'_gen`'." where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= ' and `date` in (';
	foreach( $mdays as $mday ) {
		$query .= "'$mday',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= ' group by province;';
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

?>
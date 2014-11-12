<?php
/*网页加速 - 流量统计*/

require_once('./auth.php');
require_once('./web.com.fun.php');

ini_set("soap.wsdl_cache_enabled", "0"); 
error_reporting(E_ALL^E_NOTICE);

if (user_auth($_POST['user'],$_POST['pass']))
{
	echo "<script language='javascript'>alert('用户名或密码错误，请联系管理员！');</script>"; 
	exit;
}

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

$post_time = json_decode(stripslashes($_POST['time']), true);
$post_channel = json_decode(stripslashes($_POST['channel']), true);
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

$soap = new SoapClient("http://116.28.65.253:8080/cdn/services/url?wsdl");

$argumensts = array();
$isps = array('电信', '网通', '移动');

$argumensts = array();
$argumensts[0] = $channels;
$argumensts[1] = $isps;
$argumensts[2] = $begin_day;
$argumensts[3] = $end_day;
$argumensts[4] = "send";

$arrResult = $soap->__Call("get_gen_hit",$argumensts);

//流量类型比例
if(count($arrResult) != 0)
{
	foreach($arrResult as $values)
	{
		$data = explode("\t",$values);
		$isp = $data[0];
		$sent = round($data[1] / 1024 / 1024, 2);
		$hit_sent = round($data[2] / 1024 / 1024, 2);
		if( isset($hit_ret[$isp]) ) 
		{
			$hit_ret[$isp]['sent'] += $sent;
			$hit_ret[$isp]['hit_sent'] += $hit_sent;
			} else {
			$hit_ret[$isp] = array('sent' => $sent, 'hit_sent' => $hit_sent);
		}
	}
}





//统计流量比例
//********************************************************************
$total_sent = $total_hit_sent = 0;
if (count($hit_ret) != 0)
{
	foreach( $hit_ret as $isp => $info ) 
	{
		$total_sent += $info['sent'];
		$total_hit_sent += $info['hit_sent'];
	}
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
if (count($hit_ret) != 0)
{
	foreach( $hit_ret as $isp => &$info ) 
	{
		$info['per'] = round($info['sent'] / $total_sent, 4) * 100;
		$isp_per_ret[] = array('type' => $isp, 'value' => $info['per']);
	}
}
if(count($isp_per_ret)==0)
{
	$isp_per_ret[] = array('type' => 'Null', 'value' => 100);
}
print_r(json_encode($isp_per_ret));
print('***');

//流量按天统计
//********************************************************************

$day_flow = array();
unset($argumensts);
unset($arrResult);
//$argumensts = array();
$argumensts[0] = $channels;
$argumensts[1] = $begin_day;
$argumensts[2] = $end_day;

foreach($days as $day)
{
	$day_flow[$day]['flow'] = "-";
	$day_flow[$day]['time'] = "-";
	$day_flow[$day]['bandwide'] = "-";
}
			//print_r($argumensts);
$arrResult = $soap->__Call("get_gen_flow",$argumensts);

if (count($arrResult) != 0)
{
	foreach($arrResult as $values)
	{
		$data = explode("\t",$values);
		$date = $data[0];
		$flow = $data[1];
		$day_flow[$date]['flow'] = $flow;
	}
}

unset($arrResult);

$arrResult = $soap->__Call("get_max_bandwide",$argumensts);

if (count($arrResult) != 0)
{
	foreach($arrResult as $values)
	{
		
		$data = explode("\t",$values);
		$date = $data[0];
		$time = $data[1];
		$bandwide = $data[2];
		$day_flow[$date]['time'] = "$date $time";
		$day_flow[$date]['bandwide'] = $bandwide;
	}
}

arsort($day_flow);

//print_r($day_flow);

$days_flow_bandwidth_ret = array();
foreach( $day_flow as $day => $data ) {
	$mflow = round($data['flow'] / 1024 / 1024, 2);
	$datetime = $data['time'];
	$bandwidth = round($data['bandwide'] * 8 / 1024 / 1024 *1.2, 2);
	$days_flow_bandwidth_ret[] = array('day' => $day, 'flow' => $mflow, 'bandwidth' => $bandwidth, 'datetime' => $datetime);
}

if (count($days_flow_bandwidth_ret) == 0)
{
	$days_flow_bandwidth_ret[] = array('day' => '-', 'flow' => '-', 'bandwidth' => '-', 'datetime' => '-');
}

print_r(json_encode($days_flow_bandwidth_ret));
print('***');


//TOP10省份按流量排行
//********************************************************************
unset($arrResult);
$argumensts[3] = "send";
			
$arrResult = $soap->__Call("get_gen_area",$argumensts);

$total = 0;
$days_province_flow_ret = array();

		if(count($arrResult) != 0)
		{
			foreach($arrResult as $key => $values)
			{
				$values = explode("\t",$values);
				$area = $values[0];
			//if (strlen($url) == 0){continue;}
				$cnt = round($values[1] / 1024 / 1024, 2);
				$area = explode("_",$area);
				$province = $area[1];
				
				//if ($province == "中国")
					//continue;
				if( $province == "中国" ) { $province = "国内其它"; }

				$days_province_flow_ret[$province] += $cnt;
				$total += $cnt;
				
			}
		}


arsort($days_province_flow_ret);

foreach( $days_province_flow_ret as $province => $flow ) {
	$per = round($flow / $total, 4) * 100;
	$province_top_ret[] = array('province' => $province, 'per' => $per);
}
if(count($province_top_ret) == 0)
{
	$province_top_ret[] = array('province' => '#', 'per' => '-');
}
print_r(json_encode($province_top_ret));


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
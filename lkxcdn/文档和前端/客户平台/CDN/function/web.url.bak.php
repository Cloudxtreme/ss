<?php
require_once('./web.com.fun.php');

switch( $_POST['get_type'] ) {
	
	case "_init":
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { 
			$client = $_POST['user']; 
		}
		//print_r($client);
		print_r(get_client_hostname_list($client));print('***');		
		print_r(get_cdn_zone_list());print('***');
		print_r(get_web_url_type_list());
		exit;
		break;
		
	case "_url":
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
$channels = $post_channel;
//print_r($post_time); print_r($post_channel); print($client);

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

$mysql_class = new MySQL('newcdn');
$mysql_class->opendb("cdn_web_log_general", "utf8");

//TOP10 URL按流量排行
////////////////////////////////////////////
//$days_urlreq_ret = array();
$days_url_flow = $days_url_cnt = array();
foreach( $months as $month => $mdays ) {
	$query = gensql_hostname_urlday_flow($month, $mdays, $channels);
	//print($query);
	$result = $mysql_class->query($query);
	if( $result == FALSE ) { continue; }
	while( ($row = mysql_fetch_array($result)) ) {
		$hostname = $row['hostname'];
		$url = $row['url'];
		$cnt = $row['sum(cnt)'];
		$sent = $row['sum(send)'];
		$sent = round($sent / 1024 / 1024, 2);
		$httpurl = "http://$hostname$url";
		//记录url的流量累加
		if( isset($days_url_flow[$httpurl]) ) {
			$days_url_flow[$httpurl] += $sent;
		} else {
			$days_url_flow[$httpurl] = $sent;
		}
		//记录url的访问次数累加
		if( isset($days_url_cnt[$httpurl]) ) {
			$days_url_cnt[$httpurl] += $cnt;
		} else {
			$days_url_cnt[$httpurl] = $cnt;
		}		
	}
	mysql_free_result($result);
}
arsort($days_url_flow);
$tempret =  array();
foreach( $days_url_flow as $url => $flow ) {
	$cnt = $days_url_cnt[$url];
	$tempret[] = array('url' => $url, 'flow' => $flow, 'cnt' => $cnt);
}
print_r(json_encode($tempret));
print('***');

//TOP10 URL按请求数排行
$days_url_flow = $days_url_cnt = array();
foreach( $months as $month => $mdays ) {
	$query = gensql_hostname_urlday_cnt($month, $mdays, $channels);
	//print($query);
	$result = $mysql_class->query($query);
	if( $result == FALSE ) { continue; }
	while( ($row = mysql_fetch_array($result)) ) {
		$hostname = $row['hostname'];
		$url = $row['url'];
		$cnt = $row['sum(cnt)'];
		$sent = $row['sum(send)'];
		$sent = round($sent / 1024 / 1024, 2);		
		$httpurl = "http://$hostname$url";
		//记录url的流量累加
		if( isset($days_url_flow[$httpurl]) ) {
			$days_url_flow[$httpurl] += $sent;
		} else {
			$days_url_flow[$httpurl] = $sent;
		}
		//记录url的访问次数累加
		if( isset($days_url_cnt[$httpurl]) ) {
			$days_url_cnt[$httpurl] += $cnt;
		} else {
			$days_url_cnt[$httpurl] = $cnt;
		}		
	}
	mysql_free_result($result);
}
arsort($days_url_cnt);
$tempret =  array();
foreach( $days_url_cnt as $url => $cnt ) {
	$flow = $days_url_flow[$url];
	$tempret[] = array('url' => $url, 'flow' => $flow, 'cnt' => $cnt);
}
print_r(json_encode($tempret));
print('***');

//function
//////////////////////////////////////////////////////////////////

function gensql_hostname_urlday_flow($table, $mdays, $channels)
{
	$query = "select hostname,url,sum(cnt),sum(send) from `$table".'_url`'." where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';

	$query .= ' and `date` in (';
	foreach( $mdays as $day ) {
		$query .= "'$day',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= " and `top_type` = 'topsend' group by hostname,url order by `sum(send)` desc LIMIT 10 OFFSET 0;";
	return $query;
}

function gensql_hostname_urlday_cnt($table, $mdays, $channels)
{
	$query = "select hostname,url,sum(cnt),sum(send) from `$table".'_url`'." where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';

	$query .= ' and `date` in (';
	foreach( $mdays as $day ) {
		$query .= "'$day',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= " and `top_type` = 'topcnt' group by hostname,url order by `sum(cnt)` desc LIMIT 10 OFFSET 0;";
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

?>
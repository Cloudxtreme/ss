<?php
require_once('./file.com.fun.php');
error_reporting(E_ALL^E_NOTICE);

/*一天对应秒数*/
define('ONE_DAY_SEC',86400);

switch( $_POST['get_type'] ) 
{
	case "_init":
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) 
		{ 
			$client = $_POST['user']; 
		}
		//print_r($client);
		print_r(get_client_hostname_list($client));print('***');		
		print_r(get_cdn_zone_list());print('***');
		//print_r(get_web_url_type_list());
		exit;
		break;
		
	case "_url":
		//print_r($_POST);
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) 
		{ 
			$client = $_POST['user']; 
		}
		break;    
		
	default :  
		exit; 
		break;
}


if( $client == '' ) 
{ 
	exit; 
}

$begin_day = $end_day = '';
$zones = array('中国大陆');
$channels = array();

$post_time = json_decode($_POST['time'], true);
$post_channel = json_decode($_POST['channel'], true);
$client_type = get_client_type($client);
//print_r($post_time);
check_days_limit($post_time);

if( count($post_time) != 2 || count($post_channel) <= 0 ) 
{ 
	exit; 
}

if ($client_type != 'cdnfile_hot')
{
	$channels = get_old_user_channels($post_time, $client);
}
else
{
	$channels = $post_channel;
}

$begin_day = $post_time[0];
$end_day = $post_time[1];
//print_r($post_time); print_r($begin_day); print_r($end_day); 
//print_r($post_channel); print($client);

//先根据要查询的开始结束日期获取以天为单位的天列表
$days = array(); //天列表
$days_ret =  array(); //每天5分钟数据的结果汇总
for( $bday = @strtotime($begin_day); $bday <= @strtotime($end_day); ) {
	$day = @date("Y-m-d", $bday);
	$bday += 86400;
	$days[] = $day;
}

$months = days_2_months($days);
//print_r($months); print_r($days);

$mysql_class = new MySQL('newcdnfile');
$mysql_class->opendb("cdn_file_log_general", "utf8");

//TOP10 URL按流量排行
////////////////////////////////////////////
$days_urlreq_ret = array();
$days_urlreq_print = array();
foreach( $months as $month => $mdays ) 
{
	$query = gensql_hostname_urlday_cnt($month, $mdays, $channels);
	//print($query);
	$result = $mysql_class->query($query);
	if( $result == FALSE ) { continue; }
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$hostname = $row['hostname'];
		$url = $row['url'];
		$cnt = $row['sum(cnt)'];
		$sent = $row['sum(send)'];
		$sent = round($sent / 1024 / 1024, 2);
		$httpurl = "http://$hostname$url";
		//$days_urlreq_ret[] = array('url' => $httpurl, 'flow' => $sent, 'cnt' => $cnt);
		$days_urlreq_ret[$httpurl]['flow'] += $sent;			// = array('url' => $httpurl, 'flow' => $sent, 'cnt' => $cnt);
		$days_urlreq_ret[$httpurl]['cnt'] += $cnt;
	}
	mysql_free_result($result);
}

foreach($days_urlreq_ret as $httpurl => $data)
{
	$days_urlreq_print[] = array('url' => $httpurl, 'flow' => $data['flow'], 'cnt' => $data['cnt']);
}

print_r(json_encode($days_urlreq_print));
print('***');


//TOP10 URL按请求数排行
$days_urlcnt_reqt = array();
$days_urlcnt_print = array();
foreach( $months as $month => $mdays ) 
{
	$query = gensql_hostname_urlday_req($month, $mdays, $channels);
	//print($query);
	$result = $mysql_class->query($query);
	if( $result == FALSE ) { continue; }
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$hostname = $row['hostname'];
		$url = $row['url'];
		$cnt = $row['sum(cnt)'];
		$sent = $row['sum(send)'];
		$sent = round($sent / 1024 / 1024, 2);		
		$httpurl = "http://$hostname$url";
		$days_urlcnt_reqt[$httpurl]['flow'] += $sent;			// = array('url' => $httpurl, 'flow' => $sent, 'cnt' => $cnt);
		$days_urlcnt_reqt[$httpurl]['cnt'] += $cnt;
	}
	mysql_free_result($result);
}
//print_r($days_urlcnt_reqt);
foreach($days_urlcnt_reqt as $httpurl => $data)
{
	$days_urlcnt_print[] = array('url' => $httpurl, 'flow' => $data['flow'], 'cnt' => $data['cnt']);
}
print_r(json_encode($days_urlcnt_print));




/*===================================================================================
									function										
====================================================================================*/
  
  
/**
* @brief  检查时间跨度是否符合标准（31天）
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function check_days_limit($time)
{
	$begin_day = $time[0];
	$end_day = $time[1];
	$bday = @strtotime($begin_day);
	$eday = @strtotime($end_day);
	if ($eday - $bday >= (ONE_DAY_SEC * 31))
	{
		print("时间跨度不能大于31天\n");
		exit;
	}
}

function gensql_hostname_urlday_req($table, $mdays, $channels)
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
	
	$query .= " and `top_type` = 'topcnt' and `url` like '%exe' group by hostname,url order by `sum(cnt)` desc LIMIT 10 OFFSET 0;";
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
	
	$query .= " and `top_type` = 'topsend' group by hostname,url order by `sum(send)` desc LIMIT 10 OFFSET 0;";
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

/**
* @brief  对旧客户的所有hostname进行提取
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function get_old_user_channels($query_time, $username)
{
	$query_begin = $query_time[0];
	$query_end = $query_time[1];
		
	$begin_month = substr($query_begin, 0, -3);
	$end_month = substr($query_end, 0, -3);

	if ($begin_month != $end_month)
	{
		$sql = "select hostname from (
			select hostname from `${begin_month}_gen`
			where `user` = '$username'
			union all 
			select hostname from `${end_month}_gen` 
			where `user` = '$username' 
			) as total group by `hostname`";
	}
	else
	{
		$sql = "select hostname from `$end_month".'_gen`'." where `user` = '$username' group by `hostname`";
	}
		
	//print($sql);
	$channels = array();
	$mysql_class = new MySQL('newcdnfile');
	$mysql_class -> opendb("cdn_file_log_general", "utf8");
	$result = $mysql_class->query($sql);
	/*modify in 2013/5/15*/
	if (!$result)
		return $channels;
	while( ($row = mysql_fetch_array($result)) ) 
	{
			//print_r($row);
		$channels[] = $row[0];
	}
	mysql_free_result($result);

	return $channels;
}
  
?>
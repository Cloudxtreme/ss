<?php

/*CDN下载加速 - 访客分析*/
/*author:hyb*/

require_once('./file.com.fun.php');
require_once('./log.fun.php');
require_once('../data/iplocation.class.php');

/*需要统计的top排名数量*/
define('TOP_LIMIT',10);
/*一天对应秒数*/
define('ONE_DAY_SEC',86400);

error_reporting(E_ALL^E_NOTICE);

		/*获取查询条件信息*/
		
$client = deal_client_option($_POST['get_type']);

if( $client == '' ) 
{ 
	exit; 
}

$begin_day = $end_day = '';
$zones = array('中国大陆');
$channels = array();

/*时间段*/
$post_time = json_decode($_POST['time'], true);
/*频道*/
$post_channel = json_decode($_POST['channel'], true);
$client_type = get_client_type($client);

//check_days_limit($post_time);
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

syslog_user_action($client,$_SERVER['SCRIPT_NAME'],$channels,$begin_day,$end_day);
//print_r($post_time); print_r($post_channel); print($client);
//print_r($channels);

/*先根据要查询的开始结束日期获取以天为单位的天列表*/
$days = make_days_list($begin_day, $end_day);

/*根据天列表生成月表*/
$months = days_2_months($days);
//print_r($months); print_r($days);



		/* 独立ip按时间统计功能 */
		
		
/*初始化数据库连接*/
$mysql_class = new MySQL('newcdnfile');
$mysql_class->opendb("cdn_file_log_general", "utf8");

if( $begin_day != $end_day ) 
{
	/*统计并显示多天内独立IP访问数据*/
	display_vis_mul_day($days, $channels);
}
else 
{
	/*统计并显示单天内独立IP访问数据*/
	display_vis_one_day($begin_day, $channels);
}
print('***');



		/* TOP10 访客按请求数排行功能 */
		

/*收集所有IP统计信息并降序排序*/
$ip_cnt_sort_arr = gen_all_vis_ip_data($months,$channels);

/*根据页面需要显示TOP N的IP信息*/
display_top_vis_ip_data($ip_cnt_sort_arr);

unset($mysql_class);

/*===================================================================================
									function										
====================================================================================*/
  
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
	/*note:当月份跨度过大造成空数据的原因在这里，因为没有该月份的gen表导致不能查询该旧客户的channels信息，新用户没影响*/
	$num_rows = mysql_num_rows($result);
	if ($num_rows == NULL){return $channels;}
	while( ($row = mysql_fetch_array($result)) ) 
	{
			//print_r($row);
		$channels[] = $row[0];
	}
	mysql_free_result($result);

	return $channels;
	
}
  
  
  
 /**
* @brief  显示用户选项并根据用户选项进行查询操作
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function deal_client_option($type)
{
	switch( $type ) 
	{
	
		case "_init":
			if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) 
			{ 
				$client = $_POST['user']; 
			}
			//print_r($client);
			print_r(get_client_hostname_list($client));print('***');
			print_r(get_cdn_zone_list());print('***');
			exit;
			break;
		
		case "_visitor":
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
	
	return $client;
}

 /**
* @brief  生成天列表
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function make_days_list($begin_day, $end_day)
{
	$days = array(); //天列表
	for( $bday = @strtotime($begin_day); $bday <= @strtotime($end_day); ) 
	{
		$day = @date("Y-m-d", $bday);
		$bday += ONE_DAY_SEC;
		$days[] = $day;

	}
	return $days;
}


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

 /**
* @brief  显示一天内独立IP统计信息
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function display_vis_one_day($begin_day, $channels)
{
	global $mysql_class;
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
	if($result)
	{
		while( ($row = mysql_fetch_array($result)) ) 
		{
			$time = $row[0];
			$cnt = $row[1];
			$hour = get_time_hour($time);
			$hour = sprintf("%02d", $hour);
			$num = sprintf("%d",$hour);
		//print_r($row);
			$days_hour_ipcnt_ret[$num]['date'] = $hour;
			$days_hour_ipcnt_ret[$num]['value'] = $cnt;
		}
		mysql_free_result($result);
	}
	print_r(json_encode($days_hour_ipcnt_ret));
}


 /**
* @brief  显示多天独立IP统计信息
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function display_vis_mul_day($days, $channels)
{
	global $mysql_class;
	//如果查询的是超过1天的，则独立IP按天统计
	$day_ipcnt_ret = array();
	$i = 0;
	foreach( $days as $day ) 
	{
		$query = gensql_hostname_ipday_cnt($day, $channels);
		$result = $mysql_class->query($query);
		//$num_rows = mysql_num_rows($result);
		if ($result)
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
	//print_r($day_ipcnt_ret);

	print_r(json_encode($day_ipcnt_ret));		
}



/**
* @brief  显示top n的ip信息
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function display_top_vis_ip_data($ip_data_arr)
{
	$i = 0;
	$ip_display_arr = array();
	$ipl = new ipLocation('../data/qqwry.dat'); //用纯真库获取ip归属信息

	foreach( $ip_data_arr as $ip => $ip_info_arr ) 
	{

		$sent = $ip_info_arr['sent'];
		//print("[$ip $cnt $sent] ");
		$sent = round($sent / 1024, 2);
	
		$address = $ipl->getaddress($ip);
		$address["area1"] = iconv('GB2312', 'utf-8', $address["area1"]);
		$address["area2"] = iconv('GB2312', 'utf-8', $address["area2"]);
		//print_r($address);
		$ip_nettype = $address["area2"];
		$ip_zone = $address["area1"];
		$ip_addr = "$ip_zone $ip_nettype";
	
		$ip_display_arr[] = array('ip' => $ip, 'ipinfo' => $ip_addr, 'cnt' => $ip_info_arr['cnt'], 'flow' => $sent);
		if( ++$i >= TOP_LIMIT ) 
		{  
			break; 
		}
	}

	if (count($ip_display_arr) == 0)
	{
		$ip_display_arr[] = array('ip' => '#', 'ipinfo' => '-', 'cnt' => '-', 'flow' => '-');
	}
	print_r(json_encode($ip_display_arr));
	
}

/**
* @brief  统计所有IP信息并按点击量降序排序
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function gen_all_vis_ip_data($months, $channels)
{
	global $mysql_class;
 	$ip_cnt_sort_arr = array();
	$ip_cnt_arr = array();
	$day_nums = 0;
	$last_day = '';
	foreach( $months as $month => $mdays )
	{
		foreach( $mdays as $day)
		{
			$day_nums++;
			$last_day = $day;
		}
	}
	foreach( $months as $month => $mdays ) 
	{
		//print_r($months);
		if($day_nums == 1)
			$query = gensql_hostname_ipreq_cnt($last_day, false, $channels);
		else
			$query = gensql_hostname_ipreq_cnt($month, $mdays, $channels);
		//print($query);
		$result = $mysql_class->query($query);
		/*modify in 2013/5/15*/
		//$num_rows = mysql_num_rows($result);
		if (!$result){continue;}
		while( ($row = mysql_fetch_array($result)) ) 
		{
			//print_r($row);
			$ip = $row['ip'];
			$cnt = $row['cnt'];
			$sent = $row['send'];
			$ip_cnt_sort_arr[$ip]['cnt'] += $cnt;
			$ip_cnt_sort_arr[$ip]['sent'] += $sent;
			//$ip_sent_arr[$ip] += $sent;
			$ip_cnt_arr[$ip] += $cnt;
		}
		mysql_free_result($result);
		
		
	}
	//print_r($ip_cnt_arr);
	//arsort($ip_cnt_arr);
	array_multisort($ip_cnt_arr, SORT_DESC, $ip_cnt_sort_arr);
	
	return $ip_cnt_sort_arr;
	//return $ip_cnt_arr;
}



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
	
	if($mdays != false)
	{
		$query .= ' and `date` in (';
		foreach( $mdays as $day ) {
			$query .= "'$day',";
		}
		$query[strlen($query)-1] = ')';
	}
	
	$query .= ';';
	//return "";
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

﻿<?php

/*CDN网页加速 - URL分析*/
/*author:hyb*/

require_once('./log.fun.php');
date_default_timezone_set('Asia/Shanghai');
require_once('./web.com.fun.php');
ini_set('display_errors', E_ALL);  
error_reporting(E_ALL^E_NOTICE);
$GLOBALS['THRIFT_ROOT'] = './php/src';  

//print_r($_POST);
//exit;
//$scanner = $client -> scannerOpenWithStop($tableName,$beginRow,$endRow,$columns);

$ret_cnt = array();
$ret_sent = array();
$bak = array();
$print_cnt = array();
$print_flow = array();
$sort_cnt = array();
$sort_flow = array();
$print_ret = array();

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
		
	case "_url_flow":
		//print_r($_POST);
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { 
			$client = $_POST['user']; 
		}
		break;    
		
	case "_url_cnt":
		//print_r($_POST);
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { 
			$client = $_POST['user']; 
			$do_cnt = 1;
		}
		break;   
		
	default :  
		exit; 
		break;
}

//print_r($_POST);

$key = array();
$key = json_decode($_POST['keyword']);

switch ($_POST['url_type'])
{
	
	case "all":
		//print("fuck");
		$keyword = "";
		break;
		
	case "webpage":
		$keyword = "htm$|html$|jsp$|asp$|php$|cgi$";
		break;
		
	case "pic":
		$keyword = "jpg$|icp$|bmp$|png$|gif$|tif$";
		break;
		
	case "flash":
		$keyword = "swf$";
		break;
		
	case "custom":

		switch($key[0])
		{
			case "in":
				$keyword = "$key[1]";
				break;
			
			case "not_in":
				$keyword = "^(?!.*($key[1])).*$";
				break;
				
			default:
				break;
			
		}

		break;
		
	default:
		print("error");
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


syslog_user_action($client,$_SERVER['SCRIPT_NAME'],$channels,$begin_day,$end_day);

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

/*
$end_time = @strtotime($end_day);
$end_time += 86400;
$end_day = @date("Y-m-d", $end_time);
$begin_day = '2013-05-10';
$end_day = '2013-05-16';
$chan = array();
$chan[] = '*.wjyx.com';
*/

//print("$hostname\n");
//$beginRow = "$hostname_2013-05-05";
//$endRow = "$hostname_2013-05-16";
//$beginRow = sprintf("%s_%s",$client,$begin_day);
//$endRow = sprintf("%s_%s",$client,$end_day);
//print("$beginRow - $endRow\n");
	
	
if ($do_cnt)
{
	$req = new SoapClient("http://116.28.65.253:8080/cdn/services/url?wsdl");
	
	/*
	$domains = array();
	$domains[] = "image13-c.poco.cn";
	$domains[] = "image14-c.poco.cn";
	$domains[] = "image142-c.poco.cn";
	*/
	$argumensts = array();
	$argumensts[0] = $channels;
	$argumensts[1] = $begin_day;
	$argumensts[2] = $end_day;
	//$argumensts[3] = "^(?!.*(qing)).*$";
	$argumensts[3] = $keyword;

	
	
	$arrResult = $req->__Call("get_url_cnt",$argumensts);//$client->qqCheckOnline($arrPara);
	//print_r($argumensts);
	//print_r($arrResult);
	if (count($arrResult) == 0)
	{	
		$print_ret[] = array('url' => "#", 'flow'=> "-", 'cnt' => "-");
		print_r(json_encode($print_ret));
		exit;
	}
	//if(!isset($arrResult)){exit;}
	foreach($arrResult as $values)
	{
		$values = explode("\t",$values);
		$url = $values[0];
		//if (strlen($url) == 0){continue;}
		$cnt = $values[1];
		$sent = $values[2];
		$httpurl = "http://$url";
		
		$print_ret[] = array('url' => $httpurl, 'flow'=>round($sent/1024/1024,2), 'cnt' => $cnt);
		
	}
	print_r(json_encode($print_ret));
	
}

else
{
	$req = new SoapClient("http://116.28.65.253:8080/cdn/services/url?wsdl");
	
	/*
	$domains = array();
	$domains[] = "image13-c.poco.cn";
	$domains[] = "image14-c.poco.cn";
	$domains[] = "image142-c.poco.cn";
	$argumensts = array();
	*/
	$argumensts = array();
	$argumensts[0] = $channels;
	$argumensts[1] = $begin_day;
	$argumensts[2] = $end_day;
	//$argumensts[3] = "^(?!.*(myphoto)).*$";
	$argumensts[3] = $keyword;
	
	
	$arrResult = $req->__Call("get_url_send",$argumensts);//$client->qqCheckOnline($arrPara);
	//print_r($argumensts);
	if (count($arrResult) == 0)
	{
		$print_ret[] = array('url' => "#", 'flow'=>"-", 'cnt' => "-");
		print_r(json_encode($print_ret));
		exit;
	}
	if(!isset($arrResult)){exit;}
	foreach($arrResult as $values)
	{
		$values = explode("\t",$values);
		$url = $values[0];
		//if (strlen($url) == 0){continue;}
		$cnt = $values[1];
		$sent = $values[2];
		$httpurl = "http://$url";
		
		$print_ret[] = array('url' => $httpurl, 'flow'=>round($sent/1024/1024,2), 'cnt' => $cnt);
		
	}
	print_r(json_encode($print_ret));
}
	
	
	

	
	/*
	
	
	
	
	
	

$columns[] = "cnt:";
$scanner = $hbase -> scannerOpenWithStop($tableName,$beginRow,$endRow,$columns);

while(1)
{
	$rows = $hbase->scannerGet( $scanner );
	if(!$rows)
	{
		break;
	}
	else
	{
			//print_r($rows);
			
		//$size = count($rows[0]->columns);
			//echo "{$rows[0]->row}:{$size}\n";
			//print_r($rows[0]->columns);
		
		foreach($rows[0]->columns as $key => $values)
		{
			$name = substr($key,4);
			//print("name:$name");
			$name = explode("\t",$name);
				
			$channel = $name[0];
			$url = $name[1];
			if (!in_array($channel, $channels)){continue;}
			$value = explode("\t",$values->value);
			$cnt = $value[0];
			$sent = $value[1];
			$httpurl = "http://$channel$url";
			$ret_cnt[$httpurl]['cnt'] += $cnt;
			$ret_cnt[$httpurl]['sent'] += $sent;
			$sort_cnt[$httpurl] += $cnt;
			//$sort_flow[$url] += $sent;
				
		}
		
	}
}

	array_multisort($sort_cnt, SORT_DESC, $ret_cnt);
	
	//print(date("h:i:s",time())."<p>");
	
	$i = 0;
	foreach($ret_cnt as $name => $data)
	{
	//for($i = 0; $i < 10;$i++)
	//
		//print("$name:");
		//print_r($ret[$name]);
		$print_cnt[] = array('url' => $name, 'flow'=>round($data['sent']/1024/1024,2), 'cnt' => $data['cnt']);
		//print($host);
		//exit;
		if (++$i >= 10)
		{break;}
	}
	print_r(json_encode($print_cnt));

}
else
{
//unset($columns);
//$columns = array();
$columns[] = "send:";
//print_r($columns);
$scanner = $hbase -> scannerOpenWithStop($tableName,$beginRow,$endRow,$columns);

while(1)
{
	$rows = $hbase->scannerGet( $scanner );
	if(!$rows)
	{
		break;
	}
	else
	{
			//print_r($rows);
			
		//$size = count($rows[0]->columns);
			//echo "{$rows[0]->row}:{$size}\n";
			//print_r($rows[0]->columns);
		
		foreach($rows[0]->columns as $key => $values)
		{
			$name = substr($key,5);
			//print("name:$name");
			$name = explode("\t",$name);
				
			$channel = $name[0];
			$url = $name[1];
			if (!in_array($channel, $channels)){continue;}
			$value = explode("\t",$values->value);
			$cnt = $value[0];
			$sent = $value[1];
			$httpurl = "http://$channel$url";
			$ret_sent[$httpurl]['cnt'] += $cnt;
			$ret_sent[$httpurl]['sent'] += $sent;
			//$sort_cnt[$url] += $cnt;
			$sort_flow[$httpurl] += $sent;
				
		}
	}
}


//print(date("h:i:s",time())."<p>");

	array_multisort($sort_flow, SORT_DESC, $ret_sent);
	
	//print(date("h:i:s",time())."<p>");
	
	$i = 0;
	foreach($ret_sent as $name => $data)
	{
	//for($i = 0; $i < 10;$i++)
	//
		//print("$name:");
		//print_r($ret[$name]);
		$print_flow[] = array('url' => $name, 'flow'=>round($data['sent']/1024/1024,2), 'cnt' => $data['cnt']);
		//print($host);
		//exit;
		if (++$i >= 10)
		{break;}
	}
	print_r(json_encode($print_flow));
	//print(date("h:i:s",time())."<p>");


}


$hbase->scannerClose( $scanner );
$transport->close();  



*/
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
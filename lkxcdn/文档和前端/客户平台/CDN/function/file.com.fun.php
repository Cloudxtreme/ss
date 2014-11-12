<?php
require_once('../common/mysql.com.php');

/*
获取文件加速用户的域名列表，也就是频道列表
*/
function get_client_hostname_list($client)
{
	$hostnames = array();
	$mysql_class = new MySQL('cdninfo');
	$mysql_class -> opendb("cdn_file", "utf8");
	$result = $mysql_class->query("select `hostname` from `user_hostname` where `owner` = '$client';");
	while( ($row = mysql_fetch_array($result)) ) {
		$hostnames[] = $row[0];
	}
	mysql_free_result($result);
	return json_encode($hostnames);
}

/*
获取文件加速用户的类型，主要是判断是旧版还是新版的客户
*/
function get_client_type($client)
{
	$hostnames = array();
	$dbobj = new MySQL('cdninfo');
	$dbobj->opendb("cdn_file", "utf8");
	$result = $dbobj->query("select `type` from `user` where `user` = '$client';");
  while( ($row = mysql_fetch_array($result)) ) {
		$client_type = $row[0];
	}
	mysql_free_result($result);
	return $client_type;
}

/*
获取文件加速当前的加速区域列表
*/
function get_cdn_zone_list()
{
	$zones = array('中国大陆');
	return json_encode($zones);
}

/*
获取文件加速当前的ISP列表
*/
function get_cdn_isp_list()
{
	//$isps = array('电信', '网通', '移动', '教育网', '其它');
	$isps = array('电信', '网通', '移动','长宽', '教育网');
	return json_encode($isps);
}


function get_web_url_type_list()
{
	$types = array('所有', '应用程序', '视频', '文本', '自定义');
	return json_encode($types);	
}
?>

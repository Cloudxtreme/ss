<?php
require_once('../common/mysql.com.php');

/*
获取网页加速用户的域名列表，也就是频道列表
*/

/*
function get_client_hostname_list($client)
{
	$name = "";
	$hostnames = array();
	$mysql_class = new MySQL('newcdn');
	$mysql_class -> opendb("cdn_web_log_general", "utf8");
	$result = $mysql_class->query("select `domain` from `web_domain` where `owner` = '$client' group by `domain`;");
	while( ($row = mysql_fetch_array($result)) ) {
		if ($name == "")
		{
			$name = strstr($row[0],".");
			$hostnames[] = "*$name";
		}
		$hostnames[] = $row[0];

	}
	mysql_free_result($result);
	return json_encode($hostnames);
}
*/
/*
function get_client_hostname_list($client)
{
	$hostnames = array();
	$mysql_class = new MySQL('cdninfo');
	$mysql_class -> opendb("cdn_web", "utf8");
	$result = $mysql_class->query("select `hostname` from `user_hostname` where `owner` = '$client' and `hostname` not like '%*%';");
	while( ($row = mysql_fetch_array($result)) ) {
		$hostnames[] = $row[0];
	}
	mysql_free_result($result);
	return json_encode($hostnames);
}
*/
function get_client_hostname_list($client)
{
	$hostnames = array();
	$mysql_class = new MySQL('newcdn');
	$mysql_class -> opendb("cdn_web_log_general", "utf8");
	$result = $mysql_class->query("select `domain` from `web_domain` where `owner` = '$client'  group by `domain`;");
	while( ($row = mysql_fetch_array($result)) ) {
		$hostnames[] = $row[0];
	}
	mysql_free_result($result);
	return json_encode($hostnames);
}


/*
泛解析网页加速用户的域名列表
*/
function analyse_hostname_list($channels)
{
	foreach($channels as $channel)
	{

		if (strstr($channel,'*') != false)
		{
			$name = strstr($channel,".");
			$name = substr($name,1);
			$new = array();
			$mysql_class = new MySQL('newcdn');
			$mysql_class -> opendb("cdn_web_log_general", "utf8");
			$result = $mysql_class->query("select `domain` from `web_domain` where `domain` like '%$name' group by `domain`;");
			while( ($row = mysql_fetch_array($result)) ) 
			{
			
				$new[] = $row[0];

			}
			//print_r($new);
			return $new;
		}
	}
	return $channels;
}


/*
获取网页加速当前的加速区域列表
*/
function get_cdn_zone_list()
{
	$zones = array('中国大陆');
	return json_encode($zones);
}

/*
获取网页加速当前的ISP列表
*/
function get_cdn_isp_list()
{
	$isps = array('电信', '网通', '移动', '长宽', '教育网');
	return json_encode($isps);
}

function get_web_url_type_list()
{
	$types = array('所有', '网页', '图片', 'FLASH', '自定义');
	return json_encode($types);	
}

?>

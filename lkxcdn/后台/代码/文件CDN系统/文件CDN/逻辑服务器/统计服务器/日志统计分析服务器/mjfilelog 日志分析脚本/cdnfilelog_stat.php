<?php
require_once('db.php');
require_once('iplocation.class.php');

$nettype_list = array("电信", "网通", "移动", "教育网", "铁通", "其它");
		
$zone_list = array("广东", "广西", "海南", "广州", "深圳", "湖南", "湖北", "河南", 
												"河北", "山东", "安徽", "江苏", "江西", "福建", "浙江", "上海", 
												"西藏", "四川", "云南", "重庆", "贵州", "新疆", "甘肃", "青海", 
												"宁夏", "陕西", "内蒙古", "山西", "北京", "天津", "黑龙江", 
												"吉林", "辽宁", "香港", "澳门", "台湾", "其它");

$ipcache = array();

if( $argc != 2 ) { exit; }
$logdir = $argv[1];
//print_r("$logdir\n"); 

$day = @date("Y-m-d",strtotime("-1 day"));
//print_r("$day\n");

$logfilelist = array();
$logs = scandir($logdir);
foreach( $logs as $logfile ) {
  if( $logfile[0] != '.' ) {
    $loglist[$logfile] = $logfile;
  }
}
//print_r($loglist);

foreach( $loglist as $logname )
{
    $logfile = "$logdir/$logname";
    $client = explode('.', $logname);
    $client = $client[0];
    
    deal_log_file($logfile, $client);
}
print("finish $logfile\n");

/*
log_format  mylog  '$remote_addr|$request|'
		   '$status|$body_bytes_sent|$http_range|$sent_http_content_range|$http_referer|'	
                   '$http_user_agent';
*/

function deal_log_file($logfile, $client)
{
  global $nettype_list, $zone_list;
  global $ipcache;
  
  $client_zone_nettype = array();
  $client_url = array();
  
  foreach( $zone_list as $zone ) {
  	foreach( $nettype_list as $nettype ) {
  		$client_zone_nettype[$zone][$nettype] = 0;
  	}
  }  

  //print("$logfile $client \n");

  $handle = @fopen($logfile, "r");
  if( ! $handle) { return; }
  
  $ip_l = new ipLocation();

  while (!feof($handle)) 
  {
    $line = fgets($handle, 4096);
    $temp = explode('|', $line);

    if(count($temp) < 5) { continue; }

    $userip = $temp[0];
    $gettemp = $temp[1];
    $get = explode(' ', $gettemp);
    if(count($get) < 2) { continue; }
    $url = $get[1];
    $status = $temp[2];
    $sent = $temp[3];

    //if( $status != '200' && $status != '206' )
    
    if( array_key_exists($userip, $ipcache) )
    {
    	$user_nettype = $ipcache[$userip]['nettype'];
    	$user_zone = $ipcache[$userip]['zone'];    	
    }
    else
    {
	    $address = $ip_l->getaddress($userip);
			$address["area1"] = iconv('GB2312', 'utf-8', $address["area1"]);
			$address["area2"] = iconv('GB2312', 'utf-8', $address["area2"]);
			//print_r($address);
			$user_nettype = $address["area2"];
			$user_zone = $address["area1"];

			//cache
    	$ipcache[$userip]['nettype'] = $user_nettype;
    	$ipcache[$userip]['zone'] = $user_zone;			
		}
		
		$usernettype = '其它';
		$userzone = '其它';
		foreach( $nettype_list as $nettype ) {
			if( strstr($user_nettype, $nettype) ) {
				$usernettype = $nettype;
			}
		}
		foreach( $zone_list as $zone ) {
			if( strstr($user_zone, $zone) ) {
				$userzone = $zone;
			}
		}

		$client_zone_nettype[$userzone][$usernettype] = $client_zone_nettype[$userzone][$usernettype] + 1;
		
		if( ! isset($client_url[$url]) ) {
			$client_url[$url]['cnt'] = 1;
			$client_url[$url]['sent'] = $sent;
		} else {
			$client_url[$url]['cnt'] = $client_url[$url]['cnt'] + 1;
			$client_url[$url]['sent'] = $client_url[$url]['sent'] + $sent;
		}

    //print("$userip $get $status $sent \n");

  }
  fclose($handle);
  
  //print_r($client_zone_nettype);
	//print_r($client_url);
	
	update_db($client, $client_zone_nettype, $client_url);
}

function update_db($client, $client_zone_nettype, $client_url)
{
	global $cdnfilelog_ip;
	global $cdnfilelog_user;
	global $cdnfilelog_pass;
	
	$day = @date("Y-m-d",strtotime("-1 day"));
	
	$dbobj = db_gethandle($cdnfilelog_ip, $cdnfilelog_user, $cdnfilelog_pass, 'cdn_file_url_stats');	
	
	$query = "CREATE TABLE `$client` (
	 `id` int(11) NOT NULL AUTO_INCREMENT,
	 `date` date NOT NULL,
	 `url` varchar(256) NOT NULL,
	 `cnt` int(32) unsigned NOT NULL,
	 `sent` bigint(64) NOT NULL,
	 PRIMARY KEY (`id`),
	 UNIQUE KEY `datezone` (`date`,`url`),
	 KEY `cnt` (`cnt`)
	) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
	db_query($dbobj, $query);
	
	foreach( $client_url as $url => $info )
	{
		$cnt = $info['cnt'];
		$sent = $info['sent'];
		$query = "INSERT INTO `$client` (`date`, `url`, `cnt`, `sent`) VALUES('$day', '$url', '$cnt', '$sent') ON DUPLICATE KEY UPDATE cnt=cnt+$cnt, sent=sent+$sent;";
		//print("$query\n");
		db_query($dbobj, $query);
	}
	
	$dbobj = db_gethandle($cdnfilelog_ip, $cdnfilelog_user, $cdnfilelog_pass, 'cdn_file_user_stats');	

	$query = "CREATE TABLE `$client` (
	 `id` int(11) NOT NULL AUTO_INCREMENT,
	 `date` date NOT NULL,
	 `zone` char(50) NOT NULL,
	 `nettype` varchar(50) NOT NULL,
	 `cnt` int(32) unsigned NOT NULL,
	 PRIMARY KEY (`id`),
	 UNIQUE KEY `datezone` (`date`,`zone`,`nettype`),
	 KEY `cnt` (`cnt`)
	) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
	db_query($dbobj, $query);
	
	foreach( $client_zone_nettype as $zone => $nettypes )
	{
		foreach( $nettypes as $nettype => $cnt )
		{
			$query = "INSERT INTO `$client` (`date`, `zone`, `nettype`, `cnt`) VALUES('$day', '$zone', '$nettype', '$cnt') ON DUPLICATE KEY UPDATE cnt=cnt+$cnt;";
			//print("$query\n");
			db_query($dbobj, $query);
		}
	}
}

?>
 

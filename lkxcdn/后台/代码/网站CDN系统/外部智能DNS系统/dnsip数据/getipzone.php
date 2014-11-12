<?php
require_once('iplocation.class.php');

/*
cat ../dns/ctlog ../dns/cnclog > log
awk '{print $1;}' log > ip.txt
*/

$dnsname = 'cache.rightgo.net';
$dnsdbname = 'cache_rightgo_net_ex';

$zones = array(
'广东' => 'hn_guangdong',
'广西' => 'hn_guangxi',
'海南' => 'hn_hainan',
'广州' => 'hn_guangzhou',
'深圳' => 'hn_shenzhen',

'河南' => 'hz_henan',
'湖北' => 'hz_hubei',
'湖南' => 'hz_hunan',

'山东' => 'hd_shandong',
'安徽' => 'hd_anhui',
'江苏' => 'hd_jiangsu',
'江西' => 'hd_jiangxi',
'福建' => 'hd_fujian',
'浙江' => 'hd_zhejiang',
'上海' => 'hd_shanghai',

'西藏' => 'xn_xizang',
'四川' => 'xn_sichuan',
//'成都' => 'xn_chengdu',
'云南' => 'xn_yunnan',
'重庆' => 'xn_chongqing',
'贵州' => 'xn_guizhou',

'新疆' => 'xb_xinjiang',
'甘肃' => 'xb_gansu',
'青海' => 'xb_qinghai',
'宁夏' => 'xb_ningxia',
'陕西' => 'xb_shanxi',

'内蒙古' => 'hb_neimenggu',
'山西' => 'hb_shanxi',
'河北' => 'hb_hebei',
'北京' => 'hb_beijing',
'天津' => 'hb_tianjin',

'黑龙江' => 'db_heilongjiang',
'吉林' => 'db_jilin',
'辽宁' => 'db_liaoning'
);

$ips = array();

$ip_l = new ipLocation();

$handle = @fopen("ip.txt", "r");
if ($handle) 
{
  while (!feof($handle)) 
	{
		$ip = fgets($handle, 1024);
		$ip = trim($ip);
		$address = $ip_l->getaddress($ip);
		//$address["area1"] = iconv('GB2312', 'utf-8', $address["area1"]);
		//$address["area2"] = iconv('GB2312', 'utf-8', $address["area2"]);
		$user_nettype = $address["area2"];
		$user_zone = $address["area1"];
		//print("$ip $user_zone $user_nettype \n");
		
		foreach( $zones as $zone => $temp )
		{
			if( ! strstr( $user_zone, $zone ) ) { continue; }
			
			$ip = explode('.', $ip);
			$ip = "$ip[0].$ip[1].$ip[2].0";

			if( strstr($user_nettype, '电信') )
			{
				$ips['电信'][$zone][$ip] = $ip;
			}
			else if( strstr($user_nettype, '网通') || strstr($user_nettype, '联通') )
			{
				$ips['网通'][$zone][$ip] = $ip;
			}
			else if( strstr($user_nettype, '移动') )
			{
				$ips['移动'][$zone][$ip] = $ip;
			}
			else if( strstr($user_nettype, '长城宽带') )
			{
				$ips['长城宽带'][$zone][$ip] = $ip;
			}
			else if( strstr($user_nettype, '大学') ) {
				$ips['教育'][$zone][$ip] = $ip;
			}					
			else
			{
				$ips['其它'][$zone][$ip] = $ip;
			}
		}
		
    }
    fclose($handle);
}

//print(count($ips['电信']));
//print(count($ips['网通']));
//print_r($ips);

// include "/opt/bind9db/etc/viewdata/ct/HuaNan.txt";
$bindinc = array();
//view "view_hb" { match-clients{ HuaBei_TEL; }; zone "cache.rightgo.net" IN { type master; database "mysqldb cache_rightgo_net cache_rightgo_net_ct_hb 127.0.0.1 root rjkj@rjkj"; }; };
$views = array();
$viewsfile = array();

foreach( $ips as $nettype => $zoneip )
{
	if( $nettype != '电信' && 
	$nettype != '网通' && 
	$nettype != '移动' &&
	$nettype != '长城宽带' &&
	$nettype != '教育' ) { continue; }
	
	foreach( $zoneip as $zone => $iplist )
	{
		$zonename = $zones[$zone];
		if( $nettype == '电信' ) {
			$tablezonename = 'ct_'.$zones[$zone];
		} else if( $nettype == '网通' ) {
			$tablezonename = 'cnc_'.$zones[$zone];
		} else if( $nettype == '移动' ) {
			$tablezonename = 'mobile_'.$zones[$zone];
		} else if( $nettype == '长城宽带' ) {
			$tablezonename = 'gwbn_'.$zones[$zone];
		} else if( $nettype == '教育' ) {
			$tablezonename = 'edu_'.$zones[$zone];
		} else {
			continue;
		}
		
		$filename = "dnsview/$tablezonename.txt";
		print("$filename\n");
				
		$inc = "include \"/opt/bind9db/etc/dnsview/$tablezonename.txt\";";
		$bindinc[] = $inc;

		$view = "view \"$tablezonename\" { match-clients{ $tablezonename; }; zone \"$dnsname\" IN { type master; database \"mysqldb $dnsdbname $tablezonename 127.0.0.1 root rjkj@rjkj\"; }; };";
		$views[] = $view;
		
		$handle = fopen($filename, 'w');
		if( ! $handle ) { print("open $filename error ! \n"); continue; }
		
		fwrite($handle, "acl \"$tablezonename\" {\n");
		foreach( $iplist as $ip )
		{
			fwrite($handle, "$ip/24;\n");
		}
		fwrite($handle, "};");
		fclose($handle);
	}
}

foreach( $bindinc as $inc ) { print("$inc\n"); }
foreach( $views as $view ) { print("$view\n"); }

?>



<?php
require_once('iplocation.class.php');

/*
cat ../dns/ctlog ../dns/cnclog > log
awk '{print $1;}' log > ip.txt
*/

$dnsname = 'cache.rightgo.net';
$dnsdbname = 'cache_rightgo_net_ex';

$zones = array(
'�㶫' => 'hn_guangdong',
'����' => 'hn_guangxi',
'����' => 'hn_hainan',
'����' => 'hn_guangzhou',
'����' => 'hn_shenzhen',

'����' => 'hz_henan',
'����' => 'hz_hubei',
'����' => 'hz_hunan',

'ɽ��' => 'hd_shandong',
'����' => 'hd_anhui',
'����' => 'hd_jiangsu',
'����' => 'hd_jiangxi',
'����' => 'hd_fujian',
'�㽭' => 'hd_zhejiang',
'�Ϻ�' => 'hd_shanghai',

'����' => 'xn_xizang',
'�Ĵ�' => 'xn_sichuan',
//'�ɶ�' => 'xn_chengdu',
'����' => 'xn_yunnan',
'����' => 'xn_chongqing',
'����' => 'xn_guizhou',

'�½�' => 'xb_xinjiang',
'����' => 'xb_gansu',
'�ຣ' => 'xb_qinghai',
'����' => 'xb_ningxia',
'����' => 'xb_shanxi',

'���ɹ�' => 'hb_neimenggu',
'ɽ��' => 'hb_shanxi',
'�ӱ�' => 'hb_hebei',
'����' => 'hb_beijing',
'���' => 'hb_tianjin',

'������' => 'db_heilongjiang',
'����' => 'db_jilin',
'����' => 'db_liaoning'
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

			if( strstr($user_nettype, '����') )
			{
				$ips['����'][$zone][$ip] = $ip;
			}
			else if( strstr($user_nettype, '��ͨ') || strstr($user_nettype, '��ͨ') )
			{
				$ips['��ͨ'][$zone][$ip] = $ip;
			}
			else if( strstr($user_nettype, '�ƶ�') )
			{
				$ips['�ƶ�'][$zone][$ip] = $ip;
			}
			else if( strstr($user_nettype, '���ǿ��') )
			{
				$ips['���ǿ��'][$zone][$ip] = $ip;
			}
			else if( strstr($user_nettype, '��ѧ') ) {
				$ips['����'][$zone][$ip] = $ip;
			}					
			else
			{
				$ips['����'][$zone][$ip] = $ip;
			}
		}
		
    }
    fclose($handle);
}

//print(count($ips['����']));
//print(count($ips['��ͨ']));
//print_r($ips);

// include "/opt/bind9db/etc/viewdata/ct/HuaNan.txt";
$bindinc = array();
//view "view_hb" { match-clients{ HuaBei_TEL; }; zone "cache.rightgo.net" IN { type master; database "mysqldb cache_rightgo_net cache_rightgo_net_ct_hb 127.0.0.1 root rjkj@rjkj"; }; };
$views = array();
$viewsfile = array();

foreach( $ips as $nettype => $zoneip )
{
	if( $nettype != '����' && 
	$nettype != '��ͨ' && 
	$nettype != '�ƶ�' &&
	$nettype != '���ǿ��' &&
	$nettype != '����' ) { continue; }
	
	foreach( $zoneip as $zone => $iplist )
	{
		$zonename = $zones[$zone];
		if( $nettype == '����' ) {
			$tablezonename = 'ct_'.$zones[$zone];
		} else if( $nettype == '��ͨ' ) {
			$tablezonename = 'cnc_'.$zones[$zone];
		} else if( $nettype == '�ƶ�' ) {
			$tablezonename = 'mobile_'.$zones[$zone];
		} else if( $nettype == '���ǿ��' ) {
			$tablezonename = 'gwbn_'.$zones[$zone];
		} else if( $nettype == '����' ) {
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



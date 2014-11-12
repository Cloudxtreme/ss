<?php
//中文UTF-8
/*
这个脚本程序负责检查所有移动节点是否正常工作，如果出现故障
则对节点的地区表的status设置为false，如果整个节点出现故障，
则由其它移动节点覆盖，如果没有其它移动节点覆盖则暂由电信节
点覆盖
*/

require_once('db.php');

global $global_cdn_domain;
global $global_cdn_web_ip;
global $global_cdn_web_db;
global $global_databasename;

$zone_table = array();
$zone_table_info = array(); //记录当前地区表的记录
$node_list = $nettype_iplist = array(); //记录所有web节点的ip列表

$error_node_list = array(); //记录有故障的移动ip列表

//记录地区表里面指定IP的状态，
//主要是当发现某个IP故障了，直接把这个地区表的IP批量地全部设置为false
//故障恢复后，也可以批量地把这个表的IP全部设置为true
$mysql_node_status = array(); 

$checktype = 'edu';

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

//get zone table
$query = "select * from zone_table where `nettype` = '长宽';";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error());
	exit;
}
if( ! mysql_num_rows($result) ) { exit; }

while( ($row = mysql_fetch_array($result)) ) {
	$tablename = $row['tablename'];
	$zone_table[$tablename] = $tablename;
	$mysql_node_status[$tablename] = array();
}
mysql_free_result($result);
//print_r($zone_table); exit;

//获取所有web节点ip列表
init_node_list();
//print_r($node_list); print_r($nettype_iplist); exit;

//首先检查指定网络类型的节点情况
foreach( $nettype_iplist[$checktype] as $ip )
{
	//check haproxy and squid
	$httpcode = 0;
	$checkname = "www.efly.cc.$global_cdn_domain";
	$ret = check_node($ip, '80', $checkname, $httpcode);
	//print("$ip $checkname $ret $httpcode \n");
	if( ! $ret ) {
		//故障节点
		$error_node_list[$ip] = $ip;
	}
}
//算了，把所有电信节点都检查，以防备用节点要用上电信节点
$ips = array_keys($nettype_iplist['ct']);
foreach( $ips as $ip )
{
	//check haproxy and squid
	$httpcode = 0;
	$checkname = "www.efly.cc.$global_cdn_domain";
	$ret = check_node($ip, '80', $checkname, $httpcode);
	//print("$ip $checkname $ret $httpcode \n");
	if( ! $ret ) {
		//故障节点 电信节点故障就很简单，删除节点列表对应ip项即可
		unset($nettype_iplist['ct'][$ip]);
	}
}
//print_r($error_node_list); print_r($error_ct_node_list);

//获取地区表的IP记录情况
foreach( $zone_table as $tablename ) {
	//print("check $tablename\n");
	$query = "select * from `$tablename` where `rdtype` = 'A' and name not like 'ns.%' and name != '$global_cdn_domain';";
	if( ! ($result = $dbobj->query($query)) ) { continue; }
	if( ! mysql_num_rows($result) ) { continue; }
	while( ($row = mysql_fetch_array($result)) ) {
		
		$id = $row['id'];
		$name = $row['name'];
		$ip = $row['rdata'];
		$now_status = 'true';
		$status = $row['status'];
		$desc = $row['desc'];
		
		//该IP是故障节点，更新now status状态
		//status 为数据库记录的状态
		//now_status是当前的状态
		if( array_key_exists($ip, $error_node_list) ) { $now_status = 'false'; }
		
		$zone_table_info[$tablename][$name][$ip] = array('id' => $id, 'status' => $status, 'now_status' => $now_status, 'desc' => $desc);
		
		if( $desc == 'backup' ) { continue; } //如果是临时切换的节点就直接过了
		
		if( $now_status == 'false' && $status == 'true' ) {
			//节点故障了
			
			//更新记录
			$zone_table_info[$tablename][$name][$ip]['status'] = 'false';
			$zone_table_info[$tablename][$name][$ip]['desc'] = 'error';
			
			if( isset($mysql_node_status[$tablename][$ip]) ) {
				$ip_status_info = $mysql_node_status[$tablename][$ip];
				if( $ip_status_info['status'] == 'false' && $ip_status_info['desc'] == 'error' ) {
					//如果该地区表的对应IP之前已经被批量设置过status和desc，则不需要再去执行sql设置了
					continue;
				}
			}
			//批量设置该地区表对应IP的status和desc(当前这个IP节点故障了)
			set_node_status_desc($tablename, 'false', 'error', $ip);
			//做一下记录
			$mysql_node_status[$tablename][$ip] = array('status' => 'false', 'desc' => 'error');


		}
		else if( $now_status == 'true' && $status == 'false' && $desc == 'error' ) {
			//之前为故障节点，现在恢复了，重新投入服务
			
			//更新记录
			$zone_table_info[$tablename][$name][$ip]['status'] = 'true';
			$zone_table_info[$tablename][$name][$ip]['desc'] = '';			
			
			if( isset($mysql_node_status[$tablename][$ip]) ) {
				$ip_status_info = $mysql_node_status[$tablename][$ip];
				if( $ip_status_info['status'] == 'true' && $ip_status_info['desc'] == '' ) {
					//如果该地区表的对应IP之前已经被批量设置过status和desc，则不需要再去执行sql设置了
					continue;
				}
			}			
			set_node_status_desc($tablename, 'true', '', $ip);
			//做一下记录
			$mysql_node_status[$tablename][$ip] = array('status' => 'true', 'desc' => '');
		
			
		}
		else {
		}
	}
}
//print_r($zone_table_info);exit;

//好了，到了最后一个流程了，检查地区表里面的域名，
//是否出现当前所有节点都故障了，需要动用到其它的临时节点覆盖
//如果之前故障的节点恢复了，把临时调度过来的节点删除

foreach( $zone_table as $tablename ) {
	
	foreach( $zone_table_info[$tablename] as $name => &$ipinfos ) {
		
		$allfalse = true;    //是否这个域名下面所有的A记录都故障了
		$is_all_iplist_false = true; //ip_list表配置给该域名的预设A记录是否都故障了
		$backup_ids = array(); //记录临时节点的ID
		foreach( $ipinfos as $ip => $ipinfo ) {
			$id = $ipinfo['id'];
			$desc = $ipinfo['desc'];
			if( $ipinfo['status'] == 'true' ) { $allfalse = false; }
			if( $ipinfo['status'] == 'true' && $desc == '' ) { $is_all_iplist_false = false; }
			if( $desc == 'backup' ) { $backup_ids[$id] = $ip; }
		}
		
		if( $allfalse == false ) { 
			//这个域名至少还有一个或者以上的工作节点
			//这个时候就要看一下，是否预设A记录节点恢复工作了
			if( $is_all_iplist_false == false && count($backup_ids) > 0 ) {
				//如果是就可以删除备用节点了
				foreach( $backup_ids as $id => $ip ) {
					print("delete backup $tablename $name $ip\n");
					del_table_ip($tablename, $id);
				}
			}
			continue; 
		} 
		
		//该域名对应的地区表格下所有的A记录节点都故障了，需要备用节点介入
		//开始备用节点介入流程
			
		$backup_ip = '';//备用节点ip列表
			
		//1.首先查找是否存在相同类型的正常节点（优先考虑用相同网络类型的节点介入）
		$edu_backup_ips = array();
		foreach( $nettype_iplist[$checktype] as $ip ) {
			if( ! array_key_exists($ip, $error_node_list) ) {
				$edu_backup_ips[$ip] = $ip;
			}
		}
		if( count($edu_backup_ips) > 0 ) {
			//随机取出一个移动节点
			$backup_ip = array_rand($edu_backup_ips);
		}
			
		//2.晕了，实在没有相同网络类型的节点，那就用电信节点吧
		//$nettype_iplist['ct'] 列表里面的所有节点都是可用的
		if( $backup_ip == '' && count($nettype_iplist['ct']) > 0 ) {
			//随机取出一个电信节点
			$backup_ip = array_rand($nettype_iplist['ct']);
		}
			
		if( $backup_ip != '' ) {
			//插入一个临时备用节点
			$query = "insert into `$tablename`(`name`, `ttl`, `rdtype`, `rdata`, `status`, `desc`) values('$name', '300', 'A', '$backup_ip', 'true', 'backup');";
			db_query($dbobj, $query);
			
			//更新地区表格记录
			$ipinfos[$backup_ip] = array('id' => 0, 'status' => 'true', 'desc' => 'backup');
		} 
		else {
			print("$tablename $name no backup ip\n");
		}
	}
}

///////////////////////////////////////////////////////////////////////////////////////

//获取所有节点ip记录
function init_node_list()
{
	global $global_cdn_web_ip, $global_databaseuser, $global_databasepwd;
	global $global_cdn_web_db;
	global $node_list, $nettype_iplist;
	
	$dbobj = new DBObj;
	if( ! $dbobj->conn2($global_cdn_web_ip, $global_databaseuser, $global_databasepwd) ) {
		print($dbobj->error()); myexit();
	}
	$dbobj->query("set names utf8;");
	$dbobj->select_db($global_cdn_web_db);
	
	$query = "select * from server_list where `type` = 'node' and `status` = 'true';";
	if( ! ($result = $dbobj->query($query)) ) {
		print($dbobj->error()); myexit();
	}
	
	while( ($row = mysql_fetch_array($result)) ) 
	{
		$ip = $row['ip'];
		$nettype = $row['nettype'];
		if( $nettype == '电信' ) {
			$nettype = 'ct';
		} else if( $nettype == '移动' ) {
			$nettype = 'mobile';
		} else if( $nettype == '长宽' ) {
			$nettype = 'gwbn';
		} else if( $nettype == '教育网' ) {
			$nettype = 'edu';
		} else {
			$nettype = 'cnc';
		}
			
		$node_list[$ip] = $ip;
		$nettype_iplist[$nettype][$ip] = $ip;
	}
	mysql_free_result($result);
}

function check_node($ip, $port, $domain, &$httpcode)
{
	global $global_cdn_domain;
	global $global_server_status_try;
	
	$ret = $http_ret = '';
	$i = 0;
	
	$domain = str_replace($global_cdn_domain, '', $domain);
	$domain = substr($domain, 0, strlen($domain) - 1);
	$domain = "www.qq.com";
	
	while( $i++ < $global_server_status_try )
	{
		$ret = $http_ret = '';
		$fp = fsockopen($ip, $port, $errno, $errstr, 5);
		if( ! $fp ) {
			continue;
		}
		$out = "HEAD / HTTP/1.1\r\n";
		$out .= "Host: $domain\r\n";
		$out .= "Connection: Close\r\n\r\n";

		$wret = fwrite($fp, $out);
		if( $wret === false) { fclose($fp); continue; }

		while( ! feof($fp) ) 
		{
			$temp = fgets($fp, 1024);
			if( $temp === false ) { break; }
			$ret .= $temp;
			if( $http_ret == '' ) { $http_ret = $ret; }
		}
		fclose($fp);
		if( stristr($ret, 'X-Cache') || stristr($ret, 'squid') ) { return true; }
		$http_ret = explode(' ', $http_ret);
		if( count($http_ret) < 2 ) { continue; }
		$httpcode = $http_ret[1];
		//if( $httpcode >= '200' && $httpcode <= '503' ) { return true; }
		if( $httpcode == '200' ) { return true; }
		return false;
		
	}
	return false;
}

function set_node_status_desc($tablename, $status, $desc, $ip)
{
	global $dbobj;	
	$query = "update `$tablename` set `status` = '$status', `desc` = '$desc' where `rdata` = '$ip';";
	db_query($dbobj, $query);
}

function del_table_ip($tablename, $id)
{
	global $dbobj;	
	print(@date('Y-m-d H:i:s').' ');
	$query = "delete from `$tablename` where `id` = '$id';";
	db_query($dbobj, $query);	
}

function db_query($dbobj, $query, $islog = true)
{
	print(@date('Y-m-d H:i:s').' ');
	if( $islog ) { print("$query\n"); }
	return $dbobj->query($query);
}

?>

<?php
//中文UTF-8
/*
这个脚本程序负责同步地区节点与ip_list记录之间的同步关系，网页
管理平台只操作ip_list表，由该程序负责跟进ip_list的记录关系对
各个地区表的记录进行操作
*/

require_once('db.php');

global $global_cdn_domain;
global $global_cdn_web_ip;
global $global_cdn_web_db;
global $global_databasename;

$zone_table = array();
$dns_list_idkey = $dns_list_namekey = array();
$ip_list = array();
$dup_name_rdata = array();
$zone_table_info = array(); //记录当前地区表的记录

$dbobj = new DBObj;
if( ! $dbobj->conn() ) {
	print($dbobj->error());
	exit;
}
$dbobj->query("set names utf8;");
$dbobj->select_db($global_databasename);

//get zone table
$query = "select * from zone_table where `nettype` = '电信';";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error());
	exit;
}
if( ! mysql_num_rows($result) ) { exit; }

while( ($row = mysql_fetch_array($result)) ) {
	$tablename = $row['tablename'];
	$zone_table[$tablename] = $tablename;
}
mysql_free_result($result);
//print_r($zone_table); exit;

//get dns list
$query = "select * from dns_list where `status` != 'false';";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error());
	exit;
}
if( ! mysql_num_rows($result) ) { exit; }

while( ($row = mysql_fetch_array($result)) ) {
	$id = $row['id'];
	$name = $row['name'];
	$dns_list_idkey[$id] = $name;
	$dns_list_namekey[$name] = $id;
}
mysql_free_result($result);
//print_r($dns_list_idkey); print_r($dns_list_namekey); exit;

//get ip list
$query = "select * from ip_list where `rdtype` = 'A' and `tablename` like 'ct_%';";
if( ! ($result = $dbobj->query($query)) ) {
	print($dbobj->error());
	exit;
}
if( ! mysql_num_rows($result) ) { exit; }

while( ($row = mysql_fetch_array($result)) ) {
	$id = $row['id'];
	$dnsid = $row['dnsid'];
	$ip = $row['rdata'];
	$tablename = $row['tablename'];
	$ip_list[$tablename][$dnsid][$ip] = $row;
}
mysql_free_result($result);
//print_r($ip_list); exit;

//首先检查各个地区表记录
foreach( $zone_table as $tablename ) {
	
	//print("check $tablename\n");
	$query = "select * from `$tablename` where `rdtype` = 'A' and name not like 'ns.%' and name != '$global_cdn_domain';";
	if( ! ($result = $dbobj->query($query)) ) { continue; }
	if( ! mysql_num_rows($result) ) { continue; }
	
	while( ($row = mysql_fetch_array($result)) ) {
		
		$id = $row['id'];
		$name = $row['name'];
		$ip = $row['rdata'];
		$status = $row['status'];
		$desc = $row['desc'];
		$dnsid = $dns_list_namekey[$name];
		
		//删除mobile_tablename里面name rdata重复的记录
		if( isset($dup_name_rdata[$tablename][$name][$ip]) ) {
			$query = "delete from `$tablename` where `id` = '$id';";
			db_query($dbobj, $query);
		} else {
			$dup_name_rdata[$tablename][$name][$ip] = $id;
			$zone_table_info[$tablename][$name][$ip] = $row;
		}
		
		if( ! array_key_exists($name, $dns_list_namekey) ) {
			//删除不存在域名记录
			$query = "delete from `$tablename` where `id` = '$id';";
			db_query($dbobj, $query);			
		}
		
		//备份节点在这里不会删除，由检点监控调度程序负责添加，修改，删除
		//desc有可能会描述backup临时替换节点，也有可能为error故障节点
		//这些情况我们都不要管了
		if( $desc != '' ) { continue; }
		
		if( ! array_key_exists($ip, $ip_list[$tablename][$dnsid]) ) {
			//删除不存在的A记录
			$query = "delete from `$tablename` where `id` = '$id';";
			db_query($dbobj, $query);			
		}
		else {
			//如果记录已经存在，则比较两个状态是否一致，
			//地区表的状态更新为ip_list的状态
			$now_status = $ip_list[$tablename][$dnsid][$ip]['status'];
			$now_type = $ip_list[$tablename][$dnsid][$ip]['type'];
			if( $status != $now_status ) {
				$query = "update `$tablename` set `status` = '$now_status' where `id` = '$id';";
				db_query($dbobj, $query);							
			}
		}
	}
}
//print_r($zone_table_info); exit;

//然后再检查ip_list与地区表的记录对比
foreach( $ip_list as $tablename => $tempinfo ) {
	foreach( $tempinfo as $dnsid => $iprows ) {
		foreach( $iprows as $ip => $rowinfo ) {
			$name = $dns_list_idkey[$dnsid];
			$status = $rowinfo['status'];
			if( ! array_key_exists($name, $zone_table_info[$tablename]) || 
					! array_key_exists($ip, $zone_table_info[$tablename][$name]) ) {
				//如果地区表不存在该域名记录则向地区表插入新记录
				//如果地区表存在该域名，但是没有该IP的A记录则向地区表插入新记录
				//print("add $tablename $dnsid $name $ip \n");
				$query = "insert into `$tablename`(`name`, `ttl`, `rdtype`, `rdata`, `status`) values('$name', '300', 'A', '$ip', '$status');";
				db_query($dbobj, $query);
				//以防万一暂时更新一下记录
				$zone_table_info[$name][$ip] = $ip;
			}
		}
	}
}

function db_query($dbobj, $query)
{
	print(date('Y-m-d H:i:s'));
	print(" $query\n ");
	return $dbobj->query($query);
}

?>

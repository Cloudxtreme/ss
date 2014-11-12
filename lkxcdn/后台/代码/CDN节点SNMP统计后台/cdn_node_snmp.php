<?php
require_once('db.php');

$timeout = 60 * 5;

$dev_info = array();

while( 1 )
{
	snmp_info_sync();
	
	//break;
	
	//print("snmp start 1 ******************************************************\n");
	//print @date("Y-m-d H:i:s\n");

	snmp_start();
	
	//print_r($dev_info);

	//print("snmp end 1 ******************************************************\n");
	//print @date("Y-m-d H:i:s\n");
	
	sleep($timeout);
	
	//print("snmp start 2 ******************************************************\n");
	//print @date("Y-m-d H:i:s\n");
	
	snmp_end();
	
	//print_r($dev_info);

	//print("snmp end 2 ******************************************************\n");
	//print @date("Y-m-d H:i:s\n");
	
	//break;
}

function snmp_info_sync()
{
	global $global_databaseip, $global_databasename, $global_databaseuser, $global_databasepwd;
	global $dev_info;
	
	$db = new DBObj;
	if( ! $db->conn2($global_databaseip, $global_databaseuser, $global_databasepwd) ) 
	{
		print($ipratedb->error()."\n");
		return;
	}

	$db->query("set names utf8;");
	$db->select_db($global_databasename);

	$query = "select * from `info` where `status` = 'true';";
	$result = $db->query($query);
	if( ! $result ) {
		return;
	}	
	
	$dev_info = array();
	while( ($row = mysql_fetch_array($result)) ) {
		$ip = $row['ip'];
		$community = $row['community'];
		$interface = $row['interface'];
		$oid = $row['oid'];
		$base = $row['base'];
		$dev_info[] = array('ip' => $ip,
												'community' => $community,
												'interface' => $interface,
												'oid' => $oid,
												'base' => $base,
												'ret' => array());
	}
	mysql_free_result($result);
	
	//print_r($dev_info);
}

function snmp_start()
{
	global $dev_info, $dev_ret;
	
	foreach( $dev_info as &$info )
	{
		$ip = $info['ip'];
		$community = $info['community'];
		$interface = $info['interface'];
		$oid = $info['oid'];
		$base = $info['base'];
		
		$in_oid = str_replace('ifDescr', 'ifHCInOctets', $oid);
		$out_oid = str_replace('ifDescr', 'ifHCOutOctets', $oid);
			
		//print("$info[community] $in_oid $out_oid \n");
		$ret = snmp2_get($ip, $community, $in_oid);	
		$value = get_snmp_value($ret);
		$timestamp = time();
		$info['ret']['in'] = array( 'timestamp' => $timestamp, 'value' => $value );
			
		$ret = snmp2_get($ip, $community, $out_oid);	
		$value = get_snmp_value($ret);
		$timestamp = time();
		$info['ret']['out'] = array( 'timestamp' => $timestamp, 'value' => $value );
	}
	//print_r($dev_ret);
}

function snmp_end()
{
	global $dev_info, $dev_ret;
	
	foreach( $dev_info as &$info )
	{
		$ip = $info['ip'];
		$community = $info['community'];
		$interface = $info['interface'];
		$oid = $info['oid'];
		$base = $info['base'];

		$in_oid = str_replace('ifDescr', 'ifHCInOctets', $oid);
		$out_oid = str_replace('ifDescr', 'ifHCOutOctets', $oid);
			
		//print("$info[community] $in_oid $out_oid \n");
		$ret = snmp2_get($ip, $community, $in_oid);	
		$value = get_snmp_value($ret);
		$timestamp = time();
			
		$last_in_timestamp = $info['ret']['in']['timestamp'];
		$last_in_value = $info['ret']['in']['value'];
		
		if( $value > $last_in_value && $last_in_value > 0 ) {
			$in_rate = bcsub($value, $last_in_value);
			$in_rate = bcdiv($in_rate, $timestamp - $last_in_timestamp);
		} else {
			$in_rate = 0;
		}
		//base
		$in_rate = bcmul($in_rate, $base);
		//$in_rate = cal_bandwidth($in_rate);
			
		$ret = snmp2_get($ip, $community, $out_oid);	
		$value = get_snmp_value($ret);
		$timestamp = time();
			
		$last_out_timestamp = $info['ret']['out']['timestamp'];
		$last_out_value = $info['ret']['out']['value'];
		
		if( $value > $last_out_value && $last_out_value > 0 ) {
			$out_rate = bcsub($value, $last_out_value);
			$out_rate = bcdiv($out_rate, $timestamp - $last_out_timestamp);
		} else {
			$out_rate = 0;
		}
		//base
		$out_rate = bcmul($out_rate, $base);
		//$out_rate = cal_bandwidth($out_rate);
			
		printf("%s %s in_rate = %d out_rate = %d \n", $ip, $interface, $in_rate, $out_rate);
		update_db($ip, $interface, $in_rate, $out_rate);
	}
	//print_r($dev_ret);
}

function update_db($ip, $interface, $in_rate, $out_rate)
{
	global $global_databaseip, $global_databasename, $global_databaseuser, $global_databasepwd;
	
	$ipratedb = new DBObj;
	if( ! $ipratedb->conn2($global_databaseip, $global_databaseuser, $global_databasepwd) ) 
	{
		print($ipratedb->error()."\n");
		return;
	}

	$ipratedb->query("set names utf8;");
	$ipratedb->select_db($global_databasename);

	$day = @date('Y-m-d');	
	$time = @date('H:i:s');
	$time = deal_time($time);
	$table = $day;
	$query = "CREATE TABLE IF NOT EXISTS `$table` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ip` char(20) NOT NULL,
                `interface` char(100) NOT NULL,
                `inrate` bigint(20) NOT NULL,
                `outrate` bigint(20) NOT NULL,
                `time` time NOT NULL,
                PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
 	$result = $ipratedb->query($query);
	if( ! $result )
	{
		print("create table $query error".$ipratedb->error()."\n");
		return;
	}
	
	$query = "insert into `$table`(`ip`, `interface`, `inrate`, `outrate`, `time`) 
						values('$ip', '$interface', '$in_rate', '$out_rate', '$time');";
							
	$result = $ipratedb->query($query);	
}

function get_snmp_value($ret)
{
	$temp = explode(' ', $ret);
	if( count($temp) == 2 ) { return $temp[1]; }
	return 0;
}

function cal_bandwidth($value)
{
	$value = bcmul($value, 8);
	$value = bcdiv($value, 1000 * 1000 * 1000, 4);
	return $value;
}

function deal_time($time)
{
	$hour = $min = $sec = 0;
	sscanf($time, "%02d:%02d:%02d", $hour, $min, $sec);
	$min = (int)($min / 5);
	$min = $min * 5;
	$ret = sprintf("%02d:%02d:%02d", $hour, $min, 0);
	//print("$time $ret\n");
	return $ret;
}

?>

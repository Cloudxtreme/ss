<?php

/*CDN基础功能 - 我的订单*/
/*author:hyb*/

require_once('../common/mysql.com.php');
require_once('./log.fun.php');

switch( $_POST['get_type'] ) 
{
		
	case "_order":
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) 
		{ 
			$client = $_POST['user']; 
		}
		break;
	default :  
		exit; 
		break;
}


$statues = $_POST['statues'][0];

if( $client == '' ) 
{ 
	exit; 
}


$print_ret = array();
$begin_day = $end_day = '';
$post_time = json_decode($_POST['time'], true);
$begin_day = $post_time[0];
$end_day = $post_time[1];

syslog_user_action($client,$_SERVER['SCRIPT_NAME'],null,$begin_day,$end_day);

$days = array(); //天列表
$days_ret = array();
for( $bday = @strtotime($begin_day); $bday <= @strtotime($end_day); ) 
{
	$day = @date("Y-m-d", $bday);
	$bday += 86400;
	$days[] = $day;

}

$months = days_2_months($days);
//print_r($months); print_r($days);

$mysql_class = new MySQL('ibss');
$mysql_class -> opendb("IBSS2013", "utf8");

$query = select_user_id($client);
//print($query);
$result = $mysql_class->query($query);
$num_rows = mysql_num_rows($result);
if ($num_rows == NULL){exit;}
while( ($row = mysql_fetch_array($result)) ) 
{
	//print_r($row);
	$id = $row[0];
	$alive = $row[1];
	if ($alive != 1 || $id == 0)
	{
		print("error!");
		exit;
	}
}
mysql_free_result($result);



unset($result);
$query = select_user_info($id,$begin_day,$end_day);
//print($query);

$result = $mysql_class->query($query);
$num_rows = mysql_num_rows($result);
if ($num_rows == NULL)
{
	$print_ret[] = array('type' => '-', 'orderType' => '-', 'paidType' => '-', 'date' => '-', 'statues' => '-');
	print_r(json_encode($print_ret));
	exit;
}
////处理订单
$print_type = array();

while( ($row = mysql_fetch_array($result)) ) 
{
	//print_r($row);
	$acctype = $row[2];
	$ordertype = $row[3];
	$paidtype = $row[4];
	$btime = $row[5];
	$etime = $row[6];
	$statue = $row[7];
	$dr = $row[11];
	if ($dr == 1)
	{
		continue;
	}
	
	$btime = substr($btime,0,10);
	$etime = substr($etime,0,10);
	$time = "$btime ~ $etime";
	
	unset($print_type);
switch ($acctype)
{
	case '0':
		$print_type[] = "网页加速";
		break;
		
	case '1':
		$print_type[] = "下载加速";
		break;
	
	case '2':
		$print_type[] = "网页加速";
		$print_type[] = "下载加速";
		break;
		
	case '3':
		$print_type[] = "图片加速";
		break;
		
	case '4':
		$print_type[] = "网页加速";
		$print_type[] = "图片加速";
		break;
		
	case '5':
		$print_type[] = "下载加速";
		$print_type[] = "图片加速";
		break;
			
	case '6':
		$print_type[] = "网页加速";
		$print_type[] = "下载加速";
		$print_type[] = "图片加速";
		break;
		
	default:
		$print_type[] = "错误";
		break;
}


switch ($ordertype)
{
	case '0':
		$print_order = "测试订单";
		break;
	case '1':
		$print_order = "正式订单";
		break;
		
	default:
		$print_order = "错误";
		break;

}

switch ($paidtype)
{
	case '0':
		$print_paid = "95计费";
		break;
	case '1':
		$print_paid = "第四峰值计费";
		break;
	case '2':
		$print_paid = "抛点128计费";
		break;
	case '3':
		$print_paid = "最大值计费";
		break;
	default:
		$print_paid = "错误计费";
		break;
		
	
}

switch ($statue)
{
	case '0':
		$print_sta = "正常";
		break;
	case '1':
		$print_sta = "终止";
		break;
	default:
		break;
}


foreach ($print_type as $type)
{

	switch($statues)
	{
		case '0':
			$print_ret[] = array('type' => $type, 'orderType' => $print_order, 'paidType' => $print_paid, 'date' => $time, 'statues' => $print_sta);
			break;
		case '1':
			if($statue == '0')
			{
				$print_ret[] = array('type' => $type, 'orderType' => $print_order, 'paidType' => $print_paid, 'date' => $time, 'statues' => $print_sta);
			}
			break;
			
		case '2':
			if($statue == '1')
			{
				$print_ret[] = array('type' => $type, 'orderType' => $print_order, 'paidType' => $print_paid, 'date' => $time, 'statues' => $print_sta);
			}
			break;
		case '3':
			if($ordertype == '0')
			{
				$print_ret[] = array('type' => $type, 'orderType' => $print_order, 'paidType' => $print_paid, 'date' => $time, 'statues' => $print_sta);
			}
			break;
		
		default:
			echo "error";
			exit;
	}

	
}
	
	

}
mysql_free_result($result);




if(count($print_ret) == 0)
{
	$print_ret[] = array('type' => '-', 'orderType' => '-', 'paidType' => '-', 'date' => '-', 'statues' => '-');
}

print_r(json_encode($print_ret));


/*===================================================================================
									function										
====================================================================================*/

function select_user_info($id, $bday, $eday)
{
	$query = "select * from `CDN_UserInfo` where `UserID` = '$id' and `BeginTime` between '$bday 00:00:00' and '$eday 00:00:00'";
	$query .= ';';
	return $query;
}

function select_user_id($username)
{
	/*限制默认一个用户只有一个对应id，符合业务逻辑*/
	$query = "select `ID`,`IsAction` from CDN_User where `User` = '$username' ";
	
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
<?php

require_once('../common/mysql.com.php');
require_once('./log.fun.php');

$usr = '';
$startDate = '';
$endDate = '';
$filename = '';

if( !isset($_POST['get_type']) ) { exit; }
if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { $usr = $_POST['user']; }
if( isset($_POST['startDate']) && strlen($_POST['startDate']) > 0 ) { $startDate = $_POST['startDate']; }
if( isset($_POST['endDate']) && strlen($_POST['endDate']) > 0 ) { $endDate = $_POST['endDate']; }
if( isset($_POST['filename']) && strlen($_POST['filename']) > 0 ) { $filename = $_POST['filename']; }

switch( $_POST['get_type'] ) {
	
	case "_file" :
		print_r( file_query( $usr,$startDate,$endDate,$filename ) );
		syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],null,$_POST['startDate'],$_POST['endDate']);
		break;  
		
	default :  
		exit; 
		break;
}

function file_query( $usr,$startDate,$endDate,$filename ){

$mysql_class = new MySQL('cdnmgr');
$mysql_class -> opendb("cdn_file", "utf8");
$res_arr = array();
$endDate=date("Y-m-d",(strtotime($endDate) + 3600*24));
$sql = "SELECT * FROM `file_list` where `owner` ='$usr' and filename like '%$filename%'  and (`time` between '$startDate' and '$endDate') order by id desc ";
$result = $mysql_class -> query( $sql );
$num_rows = mysql_num_rows($result);
if($num_rows){
	//if(count($result) == 0){ $res_arr[] = array('id' => '-', 'filename' => '-', 'percent' => '-', 'type' => '-', 'status' => '-', 'lastmodify' => '-', 'filesize' => '-'); }
	
	while( ($row = mysql_fetch_array($result)) ) {
		$id = $row['id'];
		$filename = $row['filename'];
		$filename = str_replace("/var/ftp/pub/$usr/", '', $filename);
		$percent = $row['percent'].'%';
		$type = $row['type'];
		$status = $row['status'];
		$lastmodify = $row['time'];
		$filesize = $row['filesize'];
		$md5= $row['md5'];

		switch ($type)
		{
			case 'push':
				$_type = "添加";
				break;
			case 'delete':
				$_type = "删除";
				break;
			default:
				$_type = "-";
				break;
		}
		
		switch ($status)
		{
			case 'ready':
				$_status = "操作准备中";
				break;
			case 'doing':
				$_status = "操作进行中";
				break;
			case 'finish':
				$_status = "操作完成";
				break;
			case 'fail':
				$_status = "操作失败";
				break;
			default:
				$_status = "-";
				break;
		}
		switch($md5)
		{
			case 'ready':
			$md5="准备计算md5";
			break;
			case 'doing':
			$md5="正在计算md5";
			break;
			case 'fail':
			$md5="md5错误";
			break;
		}
		$res_arr[] = array( 'id' => $id, 'filename' => $filename, 'percent' => $percent, 'type' => $_type, 'status' => $_status, 'lastmodify' => $lastmodify, 'filesize' => $filesize ,'md5'=>$md5);
  }

mysql_free_result($result);	

}

if (count($res_arr) == 0)
{
	$res_arr[] = array( 'id' => '-', 'filename' => '-', 'percent' => '-', 'type' => '-', 'status' => '-', 'lastmodify' => '-', 'filesize' => '-','md5'=>'-');
}

return json_encode($res_arr);

}

?>

<?php

/*CDN - 页面跳转*/
/*author:hyb*/

require_once('../common/mysql.com.php');
error_reporting(E_ALL^E_NOTICE);
date_default_timezone_set('Asia/Shanghai');

$mysql_class = new MySQL('ibss');
$mysql_class -> opendb("IBSS2014", "utf8");

$now = number_format(microtime(true),3,'','');
$timeout = base64_encode(time(0));
$time = base64_decode($_GET['time']);

//echo "time is :[".date($time)."]\n";


if ($now - $time > 10000)
{
	echo "<script language='javascript'>alert('登陆超时,请重新登陆');</script>"; 
	echo "<script language='javascript'>window.location.href='/cdn/login.php';</script>"; 
}

//$sql = "select count(*),`Name`,`User`,`Type` from CDN_User where `User`='".$_GET["user"]."' and `Pass` ='".$_GET["pwd"]."';";
$sql="select count(*),c.Name,a.CDN_User,a.CDN_Type from Ad_ContractCDN as a left join Ad_Contract as b on a.Contract_ID=b.ID left join Ad_Custom as c on b.Custom_ID=c.ID where a.CDN_User='".$_GET["user"]."' and a.CDN_Passwd='".$_GET["pwd"]."'";
//$sql = "select count(*),`Name`,`User`,`Type` from CDN_User where `User`='".$_POST["user"]."' and `Pass` ='".md5($_POST["pwd"])."'";
//print_r($sql);

$result = $mysql_class -> query($sql);
$row = mysql_fetch_row($result);

//print_r($row);
//print_r($_GET);
//print_r($_POST);
if( $row[0] == 1 )
{      
	session_start();
	$_SESSION["login_user"] = $row[1].' '.$row[2].', ';
	$_SESSION["login_type"] = $row[3];
	$_SESSION["timeout"] = time(0);
	$id = session_id(); 

	echo '正在跳转中，请稍后...';
	/*
	$fp = fopen("session.txt","w+");
	fwrite($fp,$id);
	fclose($fp);
	*/
	echo "<script>location.href='http://portal.cdn.efly.cc/cdn/index.php?id=$id&timeout=$timeout';</script>";
}
else
{
	echo "<script>alert('账号或密码错误！');</script>";
	exit;
}

?>


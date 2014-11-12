<?php

require_once('../common/mysql.com.php');

$user='';
$web=0;
$file=0;
$domain='';
$num1=0;
$num2=0;
$num3=0;
$num4=0;
$num5=0;
$num6=0;
$num1suggest='';
$num2suggest='';
$num3suggest='';
$num6suggest='';
$num5suggest='';
$linetype=0;
$username='';
if( !isset($_POST['get_type']) ) { exit; }
if( isset($_POST['user']) ) { $user=$_POST['user']; }
if( isset($_POST['web']) ) { $web=$_POST['web']; }
if( isset($_POST['file']) ) { $file=$_POST['file']; }
if( isset($_POST['domain']) ) { $domain=$_POST['domain']; }
if( isset($_POST['num1']) ) { $num1=$_POST['num1']; }
if( isset($_POST['num2']) ) { $num2=$_POST['num2']; }
if( isset($_POST['num3']) ) { $num3=$_POST['num3']; }
if( isset($_POST['num4']) ) { $num4=$_POST['num4']; }
if( isset($_POST['num5']) ) { $num5=$_POST['num5']; }
if( isset($_POST['num6']) ) { $num6=$_POST['num6']; }
if( isset($_POST['num1suggest']) ) { $num1suggest=$_POST['num1suggest']; }
if( isset($_POST['num2suggest']) ) { $num2suggest=$_POST['num2suggest']; }
if( isset($_POST['num3suggest']) ) { $num3suggest=$_POST['num3suggest']; }
if( isset($_POST['num5suggest']) ) { $num5suggest=$_POST['num5suggest']; }
if( isset($_POST['num6suggest']) ) { $num6suggest=$_POST['num6suggest']; }
if( isset($_POST['linetype']) ) { $linetype=$_POST['linetype']; }
if( isset($_POST['username']) ) { $username=$_POST['username']; }

header("content-type:text/html; charset=utf-8");
switch( $_POST['get_type'] ) {
	
	case "_selnum" :  
		echo selnum($user);
		break;  
	case "_formsubmit" :  	
	    echo formsubmit($user,$web,$file,$domain,$num1,$num2,$num3,$num4,$num5,$num6,$num1suggest,$num2suggest,$num3suggest,$num5suggest,$num6suggest,$linetype,$username);
		break;
	default :  
		exit; 
		break;
}

function selnum($user)
{   
    $str='0';
	$mysql=new MySQL('ibss');
	$mysql->opendb("IBSS2013", "utf8");
	$sql='';
	$timestr=@date("Y-m");
	$sql='select num from CDN_Infocont where ym=\''.$timestr.'\' and user=\''.$user.'\'';
	//return $sql;
	$result=$mysql->query($sql);
	if($row = mysql_fetch_array($result))
	{ 
	  if($row['num']<3||empty($row['num']))
	  {
	     $str='1';
		 $nums=$row['num']+1;
		 $tmpsql='update CDN_Infocont set num='.$nums.' where ym=\''.$timestr.'\' and user=\''.$user.'\'';
		 $mysql->query($tmpsql);
	  }
	 else
	 {
		$str='0';
	 }
	}
	else
   {
	$tmpsql='insert into CDN_Infocont(ym,num,user) values(\''.$timestr.'\',1,\''.$user.'\')'; 
	$mysql->query($tmpsql);
	$str='1';	
   }
   return $str;
}

function formsubmit($user,$web,$file,$domain,$num1,$num2,$num3,$num4,$num5,$num6,$num1suggest,$num2suggest,$num3suggest,$num5suggest,$num6suggest,$linetype,$username)
{
	$mysql=new MySQL('ibss');
	$mysql->opendb("IBSS2013", "utf8");
	$timestr=@date("Y-m-d H:i");
	$sql='insert into'.' CDN_Survey(user,web,file,`domain`,num1,num2,num3,num4,num5,num6,num1suggest,num2suggest,num3suggest,num5suggest,num6suggest,linetype,`time`,username)'.'  values(\''.$user.'\','.$web.','.$file.',\''.$domain.'\','.$num1.','.$num2.','.$num3.','.$num4.','.$num5.','.$num6.',\''.$num1suggest.'\',\''.$num2suggest.'\',\''.$num3suggest.'\',\''.$num5suggest.'\',\''.$num6suggest.'\','.$linetype.',\''.$timestr.'\',\''.$username.'\')';
	$result=$mysql->query($sql);
	//return $sql;
	if($result)
	{   
	     $tmptimestr=@date("Y-m");
	     $tmpsql='update CDN_Infocont set num=3 where ym=\''.$tmptimestr.'\' and user=\''.$user.'\'';
		 $mysql->query($tmpsql);
		 return '1';
	}
	else
	{	
	      return '0';
	} 
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"> 
<meta http-equiv="Pragma" content="no-cache"> 
<meta http-equiv="Expires" content="0">
<script>var ctx="",jsver='0',locale="zh_CN";</script>

<script type="text/javascript" src="./js/import.js"></script>

<link rel="stylesheet" href="./css/main.css" />
<script src="./js/jquery-1.7.2.js" type="text/javascript"></script>
<script src="./js/tree.js" type="text/javascript"></script>



<title>睿江Portal-首页</title>

</head>
<body>

<?php

	error_reporting(E_ALL^E_NOTICE);
	
	if(isset($_GET['timeout']))
	{
		$now = base64_decode($_GET['timeout']);
		if (time(0) - $now > 5)
		{
			echo "<script language='javascript'>alert('登陆超时,请重新登陆');</script>"; 
			echo "<script language='javascript'>window.location.href='/cdn/login.php';</script>"; 
		
			return;
		}
	}
	
	$send_id = $_GET['id'];
	/*
	session_start();
	$id = session_id($send_id); 
	
	print_r($_SESSION);
	echo "sessiong id:".$id."\n";
	exit;
	*/
	
	?>
<?php require_once('inc/head.inc.php'); ?>



<div id="main">
	<div class="wrapper">
         <?php require_once('inc/menu.inc.php'); ?>
         <!--
<script>jQuery(function(){flexMenu('box')})</script>-->
</div>
	<div class="main_right" >
	
		<div class="container">
        	<h1><div>首页</div></h1>
            <div class="box">
            	<img src="./images/myimg/cdn_pic.jpg" width="766" height="562" />
                <div class="boxbtm"></div>
            </div>
            
            
            <br />
        </div>

	</div>
	<div class="clear"></div>
</div>

<?php require_once('inc/foot.inc.php'); ?>

</body></html>
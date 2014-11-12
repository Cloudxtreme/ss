<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7"/>

<script type="text/javascript" src="../js/zh_CN.js"></script>
<script type="text/javascript" src="../js/import.js"></script>
<script type="text/javascript" src="../js/jquery-1.7.2.js"></script>
<link rel="stylesheet" href="../style/main.css">
<link rel="stylesheet" href="../css/main.css" />
<script src="../js/tree.js" type="text/javascript"></script
<script type="text/javascript" src="../js/common.js"></script>
<script type="text/javascript">
	$(function(){
		var Request = new Object();
	    Request = get_request();
	     div_id = Request['div'];
    	link_id = Request['link'];
   menu_style_set( div_id, link_id );
	})
   
</script>


<title>睿江Portal</title>
</head>
<body>
<?php require_once('../inc/head.inc.php'); ?> 

<script>function help(){window.open('/help/help.html','帮助','width=817, top=0, left=0, toolbar=no, menubar=no, resizable=no, scrollbars=yes, location=no, status=no');}</script>

<!-- 导航结束 -->
<!-- 内容开始 -->
<div id="main">
		<div class="main_left"><!--左导航开始--> 
		<?php require_once('../inc/menu.inc.php'); ?>
		
<script>jQuery(function(){flexMenu('box')})</script> <!--左导航结束--></div>
	
			<div class="main_right">
    	<div class="main_right_top">
			<div class="main_left_top_title">出现错误</div>
		</div>
		<div class="main_right_middle">
			<div class="error">
				<img src="/cdn/images/error.png" width="103" height="77" class="errimage"/>
				<h2>您没有该加速类型的频道信息，如有疑问，请联系客服人员!</h2>
				<h3><a href="javascript:history.go(-1)">点击返回重试</a> 或  <a href="#">查看帮助</a></h3>
			</div>
		</div>
		<div class="main_right_bottom"></div>
	</div>
	<div class="clear"></div>
</div>

<!-- 内容结束 -->
<?php require_once('../inc/foot.inc.php'); ?>
<br/>
<br/>

</body>
</html>
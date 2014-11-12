<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7">
<link rel="shortcut icon" href="http://myview.chinanetcenter.com/images/favicon.ico">
<link rel="bookmark" href="http://myview.chinanetcenter.com/images/favicon.ico">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"> 
<meta http-equiv="Pragma" content="no-cache"> 
<meta http-equiv="Expires" content="0">


<script>var conf={"context":""},jsver=3;</script>

<script type="text/javascript" src="../js/zh_CN.js"></script>
<script type="text/javascript" src="../js/import.js"></script>
<script type="text/javascript" src="../js/jquery-1.7.2.js"></script>
<link rel="stylesheet" href="../style/main.css">
<link rel="stylesheet" href="../css/main.css" />
<script src="../js/tree.js" type="text/javascript"></script>
<script type="text/javascript" src="../js/common.js"></script>
<script src="../js/file.log.js" type="text/javascript"></script>

<script>//<![CDATA[
$import("../style/jquery-ui-1.8.6.custom.css","css","theme");
$import("../style/jquery.multiselect.css","css");
$import("../js/common-all.js");
//]]></script>

<title>睿江Portal—日志下载</title>

</head>
<body>
<!-- 头部开始 -->
<?php require_once('../inc/head.inc.php'); ?> 
<!-- 导航结束 -->
<!-- 内容开始 -->
<div id="main">
		<div class="main_left">
			<!--左导航开始--> 
				<?php require_once('../inc/menu.inc.php'); ?>
				<script>jQuery(function(){flexMenu('box')})</script>
      <!--左导航结束-->
      </div>
		<div class="main_right">
			<div class="main_right_top">
				<div class="main_left_top_title">
					日志下载
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	
				<div class="content">
					<div class="subtitle">
						<h2>日志下载列表</h2> <span style="color:red;font-size:13px; padding-left:26px" >请注意：日志只保留15天</span>
					</div>
		 			<div class="table">
						   <table id="DownloadDT" cellspacing="0" cellpadding="0">
							    <tr id="DownloadUl">
							    
							    </tr>
							</table>
						   
					</div>
		<!--			<div style="margin-top:18px; color:Red;">* 温馨提示: 域名.txt.all.tar.gz 为全部访问日志， 域名.txt.not200.tar.gz 为访问http返回码不为200的日志</div>-->
					<br/>
					<br/>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
	</div>
	<div class="clear"></div>
</div>

<!-- 内容结束 -->
<br>
<br>
<?php require_once('../inc/foot.inc.php'); ?>

</body>
</html>
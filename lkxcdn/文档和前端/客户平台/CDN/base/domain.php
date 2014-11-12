<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7">
<link rel="shortcut icon" href="http://myview.chinanetcenter.com/images/favicon.ico">
<link rel="bookmark" href="http://myview.chinanetcenter.com/images/favicon.ico">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"> 
<meta http-equiv="Pragma" content="no-cache"> 
<meta http-equiv="Expires" content="0">

<script type="text/javascript">
String.prototype.endWith=function(s){if(s==null||s==""||this.length==0||s.length>this.length)
return false;if(this.substring(this.length-s.length)==s)
return true;else
return false;return true;}
String.prototype.startWith=function(s){if(s==null||s==""||this.length==0||s.length>this.length)
return false;if(this.substr(0,s.length)==s)
return true;else
return false;return true;}
</script>

<script>var conf={"context":""},jsver=3;</script>

<script type="text/javascript" src="../js/zh_CN.js"></script>
<script type="text/javascript" src="../js/import.js"></script>
<script type="text/javascript" src="../js/jquery-1.7.2.js"></script>
<link rel="stylesheet" href="../style/main.css">
<script type="text/javascript" src="../js/common.js"></script>
<script src="../js/base.domain.js" type="text/javascript"></script>

<link rel="stylesheet" href="../css/main.css" />
<script src="../js/tree.js" type="text/javascript"></script>


<title>睿江Portal—自定义域名</title>
</head>
<body>
<?php require_once('../inc/head.inc.php'); ?> 
<!-- 内容开始 -->
<div id="main">
	<div class="main_left"><!--左导航开始--> 
	<?php require_once('../inc/menu.inc.php'); ?>
<!--<script>jQuery(function(){flexMenu('box')})</script> 左导航结束--></div>
		<div class="main_right">
			<div class="main_right_top">
				<div class="main_left_top_title">
					自定义域名
				</div>
				<div class="main_right_top_title">
					
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
				<div class="search">
						<div class="search_left">
							<table>
								<tr>
									<td style="width:60px;">域名地址：</td>
									<td>
										<input name="domain" id="domain" type="text" style="width:240px;height:20px">
									</td>
										<td></td>
									<td>
										
									</td>
								</tr>

							</table>
							<div style="margin-top:18px; color:Red;">* 温馨提示： </div>
								<div style="margin-top:4px; color:Red;"> 1、 添加域名，如dw.test.com</div>
								  <div style="margin-top:4px; color:Red;"> 2、 在ftp根目录下，新建目录并将其重命名为与域名同名（例如dw.test.com），
								  	<br/>只有放在此目录里的文件，才可以提供下载，下载连接形式为域名/文件名
								  	<br/>（如：http://dw.test.com/a.txt，http://dw.test.com/subdir/b.txt）</div>   
								 <div style="margin-top:4px; color:Red;"> 3、 修改自己的域名，使其CNAME到cdnhotfile.efly.cc（如：dw.test.com CNAME cdnhotfile.efly.cc)</div>   

						</div>	
						<div class="search_right">
							<input type="button" value="添加" class="button" onclick="add()"/>					
						</div>				
						
				</div>
              		
				<div class="content">
					<div class="subtitle">
						<h2>我的域名</h2>
					</div>
					<div class="table1">
						<table id="domain_table">
							<tr>
								<th width="10%" align="center">序号</th>
								<th width="70%" align="center">域名</th>
							  <th width="20%" align="center">操作</th>
							</tr>
							
						</table>
					</div>
				</div>

	 			<!-- 查询结束  -->
	 		</div>	

		<div class="main_right_bottom"></div>
	</div>
	<div class="clear"></div>
</div>

<!-- 内容结束 -->
<br>
<br>
<?php require_once('../inc/foot.inc.php'); ?>
<br>
<br>


</body></html>
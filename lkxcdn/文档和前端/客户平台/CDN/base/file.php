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
<script src="../js/base.file.js" type="text/javascript"></script>

<link rel="stylesheet" href="../css/main.css" />
<script src="../js/tree.js" type="text/javascript"></script>

<style type="text/css">
.tab2{width:695px;height:22px;margin:0 10px 0px 10px;padding-left:5px}
.tab2 ul{height:23px;overflow:hidden;padding:0px;margin:0px}
.tab2 li{width:86px;height:22px;overflow:hidden;background:url(../images/skier1.png) no-repeat -337px -116px;float:left;cursor:pointer;line-height:22px;color:#007b90;font-weight:normal;text-align:center;margin-right:2px}
.tab2 li.YKTabsOn{background:url(../images/skier1.png) no-repeat -337px -145px;height:23px;font-weight:bold}
.tab2 a{text-decoration:none}
.tab2 li.YKTabsOn a{color:#000}
#file_table td{word-break: break-all; word-wrap:break-word;}
</style>

<script>//<![CDATA[
$import("../style/jquery-ui-1.8.6.custom.css","css","theme");
$import("../style/jquery.multiselect.css","css");
$import("../js/common-all.js");
//]]></script>

<script>//<![CDATA[
$import("../js/My97DatePicker/WdatePicker.js");
//]]></script>

<title>睿江Portal—文件列表</title>
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
					文件列表
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
									<td class="lable">查询时间：</td>
									<td>
										<input name="startDate" id="startDate" type="text" class="inp"  onfocus="WdatePicker({onpicked:function(){$dp.$('endDate').focus();},minDate:'2012-07-02',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,maxDate:'%y-%M-%d}'})">&nbsp;&nbsp;~
											&nbsp;&nbsp;<input name="endDate" id="endDate" type="text" class="inp"  onfocus="WdatePicker({startDate:'%y-%M-01',dateFmt:'yyyy-MM-dd',minDate:'#F{$dp.$D(\'startDate\');}',maxDate:'%y-%M-%d'})">
									</td>		

									<td class="lable">文件：</td>
									<td colspan="3">
								       <input type="text" id="filename"  style="width:180px;height:20px"/>
							    </td>
												
								</tr>
						
								<tr >
									<td colspan="5" align="center">
											<div >
												<input type="button" value="查&nbsp;询" class="button" onclick="query();"/> &nbsp;&nbsp;
												<input type="button" value="刷&nbsp;新" class="button" onclick="query();"/>
											</div>		
									</td>
								</tr>
							</table>
						</div>	
		
						
					
						
				</div>
              		
				<div class="content">
					<div class="subtitle">
						<h2>我的文件列表</h2>
					</div>
					<div class="table1">
						<table id="file_table" style="table-layout: fixed;">
							<tr>
								<th width="30px" >序号</th>
								<th width="110px" >文件名称</th>
								<th width="80px" >文件大小(字节)</th>
                                <th width="110px" >MD5</th>
								<th width="60px" >操作时间 </th>
								<th width="60px" >处理状态 </th>
							<th width="60px" >进度</th>
							<th width="60px" >类型</th>
							</tr>
							
						</table>
					</div>
				</div>

	 			<!-- 查询结束  -->
	 		</div>
	 			<div id="updWin" style="left: 35%; top: 35%; width: 275px; height: 240px; display:none; 
		        background-color: #f6f9fd; position: absolute; border: solid 1px blue;width: 280px; height: 240px; ">
		        <table>
		            <tr style="height: 20px; font-size:12px;font-family: 宋体, simsun;">
		                <td>&nbsp;
		                    
		                </td>
		            </tr>
		            <tr style="height: 30px; font-size:12px;font-family: 宋体, simsun;">
		                <td>
		                 &nbsp; &nbsp;&nbsp;主域名：&nbsp;
		                    <input name="upddomain" id="upddomain" readonly type="text">
		                    
		                </td>
		            </tr>

		        	<tr style="height: 30px; font-size:12px;font-family: 宋体, simsun;">
		                <td>
		                    &nbsp;&nbsp;&nbsp;&nbsp;子域名：&nbsp;
		                    <input type="text" class="textBoxShort" id="updsubdomain" >
		                </td>
		            </tr>
		            <tr style="height: 30px; font-size:12px;font-family: 宋体, simsun;">
		                <td>
		                   &nbsp; 网络类型：&nbsp;
			                   <select id="updnettype" style="width:80px;" disabled>
													<option value="ct">电信</option>
													<option value="cnc">网通</option>
										   	</select>
		                </td>
		            </tr>
		            <tr style="height: 30px; font-size:12px;font-family: 宋体, simsun;">
		                <td>
		                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;IP：&nbsp;
		                    <input type="text" class="textBoxShort" id="updip" >
		                </td>
		            </tr>
		            <tr style="height: 30px; font-size:12px;font-family: 宋体, simsun;">
		                <td>
		                   &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;TTL：&nbsp;
		                    <input type="text" class="textBoxShort" id="updttl" >
		                </td>
		            </tr>

		            <tr style="height: 30px; font-size:12px;" align="center"">
		                <td>
		                    &nbsp;&nbsp;<input type="button" value="保存" class="buttonShort" onclick='save();' /> &nbsp;&nbsp;
		                    <input type="button" value="关 闭" class="buttonShort" onclick="closeLoadWin()" /> 
		                </td>
		            </tr>
		            <tr style="height: 20px; font-size:12px;">
		                <td>&nbsp;
		                    
		                </td>
		            </tr>
		        </table>  
		        <input id="updtablename" type="hidden"/>
		        <input id="updid" type="hidden"/>
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
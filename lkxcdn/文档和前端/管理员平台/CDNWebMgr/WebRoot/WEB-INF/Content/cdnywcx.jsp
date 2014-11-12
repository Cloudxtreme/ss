<%@ page language="java" import="java.util.*" pageEncoding="UTF-8"%>
<%@ taglib prefix="s" uri="/struts-tags"%>
<%
String path = request.getContextPath();
String basePath = request.getScheme()+"://"+request.getServerName()+":"+request.getServerPort()+path+"/";
%>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
    <base href="<%=basePath%>"/>
    
    <title>宽带统计</title>
    
	<meta http-equiv="pragma" content="no-cache"/>
	<link rel="shortcut icon" href="image/favicon.ico"/>
	<meta http-equiv="cache-control" content="no-cache"/>
	<meta http-equiv="expires" content="0"/>    
	<!--
	<link rel="stylesheet" type="text/css" href="styles.css">
	-->
       <link rel="stylesheet" href="css/form.css" />
       <link rel="stylesheet" href="css/main.css" />
       <link rel="stylesheet" href="js/loading.css" />
       <link rel="stylesheet" href="css/jquery-ui-1.8.6.custom.css" />
       <link rel="stylesheet" href="css/jquery.multiselect.css" />
       <script type="text/javascript" src="js/jquery-1.7.2.js"></script>
       <script type="text/javascript" src="js/jquery.ui.core.js"></script>
       <script type="text/javascript" src="js/jquery.ui.widget.js"></script>
       <script type="text/javascript" src="js/My97DatePicker/WdatePicker.js"></script>
       <script type="text/javascript" src="js/zh_CN.js"></script>
       <script type="text/javascript" src="js/jquery.multiselect.js"></script>
       <script type="text/javascript" src="js/jquery.multiselect.filter.js"></script>  
       <script type="text/javascript" src="js/cdnywcx.js"></script>
       <script type="text/javascript" src="js/jquery.bgiframe.min.js" ></script>
       <script type="text/javascript" src="js/loading-min.js" ></script>
       <script type="text/javascript" src="amcharts/amstock.js" ></script>
       <script type="text/javascript" src="js/common.js" ></script>
        <script type="text/javascript" src="js/jquery.tools.tooltip.js" ></script>
        <script type="text/javascript" src="Highstock/highstock.js"></script>
  </head>
  <body>
  <s:hidden name="user"/>
  <s:hidden name="type"/>
  <s:hidden name="id"/>
    <div class="main_right_top">
				<div class="main_left_top_title">
				   <s:if test="type==0">
			                        网页加速&nbsp;&nbsp;|&nbsp;&nbsp;
					</s:if>
					<s:elseif test="type==1">
					下载加速&nbsp;&nbsp;|&nbsp;&nbsp;
					</s:elseif>
					宽带统计&nbsp;&nbsp;|&nbsp;&nbsp;<s:property value="name"/>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:history.back(-1)">点击返回</a>
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	           <div class="search">
	           <div class="search_left">
	           <table>
	           <tr>
	           <td style="text-align:left;" width="80px">查询时间：</td>
	           <td style="text-align:left;" width="300px"><input name="startDate" id="startDate" type="text" class="inp"  onfocus="WdatePicker({onpicked:function(){$dp.$('endDate').focus();},minDate:'2012-07-02',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,maxDate:'%y-%M-%d}'})"/>&nbsp;&nbsp;~&nbsp;&nbsp;
					<input name="endDate" id="endDate" type="text" class="inp"  onfocus="WdatePicker({startDate:'%y-%M-01',dateFmt:'yyyy-MM-dd',minDate:'#F{$dp.$D(\'startDate\');}',maxDate:'%y-%M-%d'})"/></td>
	           <td style="text-align:left;" width="80px">加速区域：</td>
	           <td style="text-align:left;">
	           <select id="regionCode" name="regionCode" size=1  disabled="disabled"></select>

			<img id="tt1" width="14" src="image/index.png"/><script>$(document).ready(function(){$("#tt1").tooltip({position:"bottom center",tip:"#tt_region"});});</script>
	           </td>
	           </tr>
	           <tr>
	           <td style="text-align:left;" width="80px">频　　道：</td>
	           <td style="text-align:left;"  width="300px">
	           <select id="channel" multiple="multiple" name="selectedChannels" size=1></select>

										<img id="chtip" src="image/tip.png"/>
										<script>$(document).ready(function(){$("#chtip").tooltip({position:"center right",tip:"#chtip-data"});});</script><br/>
	           </td>
	           <td style="text-align:left;" width="80px">ISP：</td>
	           <td style="text-align:left;">
	           <select id="selectedIsps" multiple="multiple" size="5" name="selectedIsps"></select>
                    					<img id="tt3" width="14" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAMAAAAolt3jAAAAilBMVEUUbYsSb5ARcJATcI8Ub4oTcY0UbY0QcIkRcI4ScY3+//8XbY4Rbo/+//3+/vwUbokRb4kTcYsQb4v9//wTbokVb4oRcYoWb439/v/9//4VcI8VbpAQb438//0TbosRcIz///0ScIoVbo4Tbo0VbowRbo0UbpD//v8Rb4sScIwUb4wUb44Sb47///93tgHtAAAAnklEQVQI1wXBBWICQRAEwDnDQhSIkdytj/X+/3tUETf1Zm7V3xqYosb9U1U3TAKjau24EfaHVFUzTcBWQuduGwHUyEZ+Dhy+Zyel9ZDtdBkSdxsxkKvv3sXDKSS/DiQGTfbT+7pUGHl+terMbvKrN2pfANa/mKWYg5Z8LFI6f0DuPtNaqqi+9OncPjGTGFLLWm3JvmvEaYy+FURXKf8PGT8YvZOuV3MAAAAASUVORK5CYII="/><script>$(document).ready(function(){$("#tt3").tooltip({position:"bottom center",tip:"#tt_isp"});});</script>
	           </td>
	           </tr>
	           </table>
	           </div>
	           	<div class="search_right_three">
							<input type="button" value="查&nbsp;询" class="button" OnClick="query()"/>
				</div>
	           </div>
				<div class="content">
					<div class="subtitle">
						<span class="tip">流量最大值: <span id="max_value" style="font-size:13px;padding-left:8px;color:red"></span></span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<span class="tip">时间：<span id="max_time" style="font-size:13px;padding-left:8px;color:red"></span></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;95th:<span id="th95" style="font-size:13px;padding-left:8px;color:red"></span>
					</div>
                   	<div class="chart">
					<div id="chartdiv" style="width:100%; height:500px;"></div></div>
				</div>
				<div class="content">
				
							<div class="subtitle">
								<h2>TOP10 频道按峰值排行</h2>
							</div>
							<div class="table">
								<table id="_top_table">
									<tbody>
										<tr>
											<th width="30px" class="index">序号</th>
											<th width="230px" style="padding-left:0px;text-align:right;">频道</th>
											<th width="150px" style="padding-left:0px;text-align:right;">峰值(Mbps)</th>
											<th width="150px" style="padding-left:0px;text-align:right;">峰值时间点</th>
											<th width="100px" style="padding-left:0px;text-align:right;">总流量 &nbsp;&nbsp;</th>
										</tr>

										<tr id="_top10" class="oddrow">

										</tr>
										
									
								</tbody>
							</table>
							</div>			
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

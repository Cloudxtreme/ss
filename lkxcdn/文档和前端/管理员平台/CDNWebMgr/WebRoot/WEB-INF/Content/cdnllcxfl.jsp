<%@ page language="java" import="java.util.*" pageEncoding="UTF-8"%>
<%@ taglib prefix="s" uri="/struts-tags"%>
<%
String path = request.getContextPath();
String basePath = request.getScheme()+"://"+request.getServerName()+":"+request.getServerPort()+path+"/";
%>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <base href="<%=basePath%>"/>
    
    <title>流量统计</title>
    
	<meta http-equiv="pragma" content="no-cache"/>
	<link rel="shortcut icon" href="image/favicon.ico"/>
	<meta http-equiv="cache-control" content="no-cache"/>
	<meta http-equiv="expires" content="0"/>    
	<meta http-equiv="keywords" content="keyword1,keyword2,keyword3"/>
	<meta http-equiv="description" content="This is my page"/>
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
       <script type="text/javascript" src="js/jquery.bgiframe.min.js" ></script>
       <script type="text/javascript" src="js/loading-min.js" ></script>
       <script type="text/javascript" src="amcharts/amstock.js" ></script>
       <script type="text/javascript" src="js/cdnllcxfl.js"></script>
        <script type="text/javascript" src="js/common.js" ></script>
         <script type="text/javascript" src="js/jquery.tools.tooltip.js" ></script>
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
					流量统计&nbsp;&nbsp;|&nbsp;&nbsp;<s:property value="name"/>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:history.back(-1)">点击返回</a>
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
	           <select id="regionSimpleCode" name="regionCode" size=1  disabled="disabled"></select>

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
	           <td style="text-align:left;" width="80px"></td>
	           <td style="text-align:left;">
	           
	           </td>
	           </tr>
	           </table>
	           </div>
	           	<div class="search_right_three">
							<input type="button" value="查&nbsp;询" class="button" OnClick="query()"/>
				</div>
	           </div>
   <div class="content">
						<div class="subtitle"><h2>各ISP比例</h2></div>
							<div id="flowIspPieChart_container" style="height: 320px;">  </div>

					</div>
						
					<div class="content">
						<div class="subtitle">流量按天统计</div>
						<div class="chart"><div id="flowColumnChart_container" style="height: 420px;">
  
						</div>
						</div>
						<div class="table">
							<table id="en_flow_all_table">
								<tr>
									<th class="index">日期</th>
									<th class="index">流量（单位MB）</th>
									<th class="index">带宽峰值（单位Mbps）</th>
									
									<th class="index">峰值时间</th>
								</tr>

								
							</table>
						</div>
					</div>
				<div class="content">
						<div class="subtitle">
							<h2>TOP10省份按流量排行</h2>
							<span class="more"></span>
						</div>
						<div class="table">
							<table id="province_flow">
								<tr>
									<th width="30px" class="index">序号</th>
									<th width="180px">省份名称</th>
								 
									<th width="220px">流量比例(%)</th>
									<th class="last">&nbsp;</th>
								</tr>
						
							</table>
						</div>
					</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

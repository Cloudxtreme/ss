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
    
    <title>查询总带宽</title>
   <link rel="shortcut icon" href="image/favicon.ico"/>
	<meta http-equiv="pragma" content="no-cache"/>
	<meta http-equiv="cache-control" content="no-cache"/>
	<meta http-equiv="expires" content="0"/>    
    <link rel="stylesheet" href="css/form.css" />
    <link rel="stylesheet" href="css/main.css" />
    <script type="text/javascript" src="js/jquery-1.7.2.js"></script>
    <script type="text/javascript" src="js/base64.js"></script>
    <script type="text/javascript" src="js/My97DatePicker/WdatePicker.js"></script>
    <script type="text/javascript" src="amcharts/amstock.js" ></script>
<script>
$(function(){
	var date = new Date();
	$("#startDate").val(date.format("yyyy-MM-dd")); 
	$("#endDate").val(date.format("yyyy-MM-dd")); 
	});
function chk(){
     var type=$("#type").find("option:selected").val(); 
	 var startDate=$("#startDate").val();
	 var endDate=$("#endDate").val();
	 if(type==""||startDate==""||endDate=="")
	 {
		 return false;
	 }
	 $.post("Top10sel!selsum",{"type":type,"stdate":startDate,"endate":endDate},function(data){
          if(data=="false")	 
		  {
			  return false;
		  }
	     var arr = data.split("****");
	     var chartData = [];
	     arr_time = eval("("+arr[0]+")");
		 arr_value = eval("("+arr[1]+")");
		 for( count=0;count<arr_time.length;count++ ){
				var mydate = parseDate(arr_time[count]);
						chartData.push({
				   		date : mydate,
				  		value: arr_value[count]
						});
			    		}
		 createStockChart(chartData);
		 });
    }
 function createStockChart(chartData) {
	var chart = new AmCharts.AmStockChart();
	chart.pathToImages = "amcharts/images/";
	
  var categoryAxesSettings = new AmCharts.CategoryAxesSettings();
//定义显示的最小时间段，前提是X轴是Date对象
categoryAxesSettings.parseDates = true;
categoryAxesSettings.minPeriod = "mm";
chart.categoryAxesSettings = categoryAxesSettings;

	// DATASETS //////////////////////////////////////////
	var dataSet = new AmCharts.DataSet();
	dataSet.color = "#b0de09";
	dataSet.fieldMappings = [{
		fromField: "value",
		toField: "value"
	}];
	
	dataSet.dataProvider = chartData;
	dataSet.categoryField = "date";

	chart.dataSets = [dataSet];

	// PANELS ///////////////////////////////////////////                                                  
	var stockPanel = new AmCharts.StockPanel();
	stockPanel.showCategoryAxis = true;
	stockPanel.title = "Value";
	stockPanel.eraseAll = false;
	stockPanel.addLabel(0, 100, "", "center", 16);
	
	var graph = new AmCharts.StockGraph();
	graph.type = "smoothedLine";
	graph.title = "频道带宽";
	graph.valueField = "value";
	graph.lineAlpha = 1;
	graph.lineColor = "#d1cf2a";
	graph.fillAlphas = 0.3; 
	stockPanel.addStockGraph(graph); 

	var stockLegend = new AmCharts.StockLegend();
	stockLegend.valueTextRegular = " ";
	stockLegend.markerType = "none";
	stockPanel.stockLegend = stockLegend;
	stockPanel.drawingIconsEnabled = true;

	chart.panels = [stockPanel];

	// OTHER SETTINGS ////////////////////////////////////
	var scrollbarSettings = new AmCharts.ChartScrollbarSettings();
	scrollbarSettings.graph = graph;
	scrollbarSettings.updateOnReleaseOnly = true;
	chart.chartScrollbarSettings = scrollbarSettings;

	var cursorSettings = new AmCharts.ChartCursorSettings();
	cursorSettings.valueBalloonsEnabled = true;
	chart.chartCursorSettings = cursorSettings;

	// PERIOD SELECTOR ///////////////////////////////////
	var periodSelector = new AmCharts.PeriodSelector();
	periodSelector.position = "bottom";
	periodSelector.periods = [{
		period: "DD",
		count: 10,
		label: "10 days"
	}, {
		period: "MM",
		count: 1,
		label: "1 month"
	}, {
		period: "YYYY",
		count: 1,
		label: "1 year"
	}, {
		period: "YTD",
		label: "YTD"
	}, {
		period: "MAX",
		label: "MAX"
	}];
	chart.periodSelector = periodSelector;

	var panelsSettings = new AmCharts.PanelsSettings();
	chart.panelsSettings = panelsSettings;

	chart.write('chartdiv');
}
function parseDate(str_date) {
		               arr = str_date.split(' ');
					    date_str = arr[0];
					    time_str = arr[1];
					    date_arr = date_str.split('-');
					    time_arr = time_str.split(':');
			     date_result = new Date( date_arr[0], date_arr[1] - 1, date_arr[2], time_arr[0], time_arr[1], time_arr[2] );
	     
			     return date_result;
      }
Date.prototype.format = function(format)
{
 var o = {
 "M+" : this.getMonth()+1, //month
 "d+" : this.getDate(),    //day
 "h+" : this.getHours(),   //hour
 "m+" : this.getMinutes(), //minute
 "s+" : this.getSeconds(), //second
 "q+" : Math.floor((this.getMonth()+3)/3),  //quarter
 "S" : this.getMilliseconds() //millisecond
 }
 if(/(y+)/.test(format)) format=format.replace(RegExp.$1,
 (this.getFullYear()+"").substr(4 - RegExp.$1.length));
 for(var k in o)if(new RegExp("("+ k +")").test(format))
 format = format.replace(RegExp.$1,
 RegExp.$1.length==1 ? o[k] :
 ("00"+ o[k]).substr((""+ o[k]).length));
 return format;
}   
</script>
  </head>
  
  <body>
     
			<div class="main_right_top">
				<div class="main_left_top_title">
					查询总带宽
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	           <div class="search">
						<div class="search_left">

							<table>
								<tr>
								<td style="width:60px;">类型：</td>
								<td style="width:70px;"><select id="type" name="type" style="width:90px;"><option value="all">所有</option><option value="web">网站</option><option value="file">文件</option></select></td>
								<td style="width:80px;">开始日期：</td>
								<td style="width:80px;"><input name="startDate" id="startDate" type="text" class="inp"  onfocus="WdatePicker({onpicked:function(){$dp.$('endDate').focus();},minDate:'2012-07-02',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,maxDate:'%y-%M-%d}'})"/></td>
	                            <td style="width:80px;">结束日期：</td>
								<td style="width:80px;"><input name="endDate" id="endDate" type="text" class="inp"  onfocus="WdatePicker({startDate:'%y-%M-01',dateFmt:'yyyy-MM-dd',minDate:'#F{$dp.$D(\'startDate\');}',maxDate:'%y-%M-%d'})"/></td>
	                            <td style="width:80px;"><input type="submit" value="查询" class="button" onclick="chk()"/></td>
	                            </tr>
	                          
								
								
	                          
	                         </table>

	           </div>
	           </div>
				<div class="content">
					<div class="subtitle">
					<h2>单位:Mbps<span id="zll" style="font-size:13px;margin-right:10px;color:red"></span></h2>
					</div>
		 		    <div class="chart">
					<div id="chartdiv" style="width:100%; height:500px;"></div>
					</div>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

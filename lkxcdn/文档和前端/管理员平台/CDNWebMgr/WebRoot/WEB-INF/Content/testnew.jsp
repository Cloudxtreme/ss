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
    <link rel="stylesheet" href="js/loading.css" />
    <script type="text/javascript" src="js/jquery-1.7.2.js"></script>
    <script type="text/javascript" src="js/base64.js"></script>
    <script type="text/javascript" src="js/My97DatePicker/WdatePicker.js"></script>
    <script type="text/javascript" src="Highstock/highstock.js"></script>
    <script type="text/javascript" src="js/loading-min.js" ></script>
    <script type="text/javascript" src="js/jquery.bgiframe.min.js" ></script>
    <script type="text/javascript" src="js/common.js"></script>
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
	 var loading = new ol.loading({id:"container"});
	 loading.show();	
	 $.post("Top10sel!selsum1",{"type":type,"stdate":startDate,"endate":endDate},function(data){
          if(data=="false")	 
		  {
			  return false;
		  }
			
		  var arr = data.split("****");
		  var data1=eval(arr[0]);
		   document.getElementById("max_value").innerHTML = arr[1] +  " Mbps";
		   document.getElementById("time").innerHTML = arr[2];
	    $('#container').highcharts('StockChart', {
			

			rangeSelector : {
				selected : 1
			},
			title : {
				text : '宽带流量'
			},

			series : [{
				name : '宽带流量',
				data : data1,
				type : 'areaspline',
				threshold : null,
				tooltip : {
					valueDecimals : 2
				},
				fillColor : {
					linearGradient : {
						x1: 0, 
						y1: 0, 
						x2: 0, 
						y2: 1
					},
					stops : [[0, Highcharts.getOptions().colors[0]], [1, 'rgba(0,0,0,0)']]
				}
			}]
		});
		
		});
		loading.hide();
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
     
			<div class="main_right_top" >
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
	                            <td style="width:80px;"><input type="submit" value="查询" class="button" onclick="chk()" id="hh"/></td>
	                            </tr>
	                          
								
								
	                          
	                         </table>

	           </div>
	           </div>
				<div class="content">
					<div class="subtitle">
					<h2>单位:Mbps</h2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;峰值：<span id="max_value" style="font-size:13px;margin-right:10px;color:red"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;时间：<span id="time" style="font-size:13px;margin-right:10px;color:red"></span>
					</div>
		 		    <div class="chart">
					<div id="container" style="height: 500px; min-width: 500px"></div>
					</div>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

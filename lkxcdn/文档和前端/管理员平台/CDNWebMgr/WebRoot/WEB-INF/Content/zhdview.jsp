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
    
    <title>指定时间段TOP10</title>
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
    <script type="text/javascript" src="js/highcharts.js"></script>
    <script type="text/javascript" src="js/themes/gray.js"></script>
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
	 $.post("Top10sel!zhdcx",{"type":type,"stdate":startDate,"endate":endDate},function(data){
          if(data=="false")	 
		  {
			  return false;
		  }
		   
		  var arr = data.split("****");
		  var data1=eval(arr[0]);
		   var data2=eval(arr[1]);
	    $('#container').highcharts({
            chart: {
                type: 'column',
                margin: [ 50, 50, 100, 80]
            },
            title: {
                text: '指定时间段流量TOP10'
            },
            xAxis: {
                categories: data1,
                labels: {
                    rotation: -45,
                    align: 'right',
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: '流量 '
                }
            },
            legend: {
                enabled: false
            },
            tooltip: {
                pointFormat: '流量: <b>{point.y:.1f} </b> (GB)',
            },
            series: [{
                name: 'Population',
                data: data2,
                dataLabels: {
                    enabled: true,
                    rotation: -90,
                    color: '#FFFFFF',
                    align: 'right',
                    x: 4,
                    y: 10,
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif',
                        textShadow: '0 0 3px black'
                    }
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
					指定时间段TOP10
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	           <div class="search">
						<div class="search_left">

							<table>
								<tr>
								<td style="width:60px;">类型：</td>
								<td style="width:70px;"><select id="type" name="type" style="width:90px;"><option value="web">网站</option><option value="file">文件</option></select></td>
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
					<h2>单位:GB</h2>
					</div>
		 		    <div class="chart">
					<div id="container" style="min-width: 500px; height: 400px; margin: 0 auto"></div>
					</div>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

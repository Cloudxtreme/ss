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
    <meta http-equiv=“X-UA-Compatible” content=“IE=9″/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="pragma" content="no-cache"/>
	<link rel="shortcut icon" href="image/favicon.ico"/>
	<meta http-equiv="cache-control" content="no-cache"/>
	<meta http-equiv="expires" content="0"/>    
    <title><s:property value="title" escape="false"/></title>
    <link rel="stylesheet" href="css/form.css" /> 
    <script type="text/javascript" src="js/jquery-1.7.2.js"></script>
    <script type="text/javascript" src="js/highcharts.js"></script>
    <script type="text/javascript" src="js/themes/gray.js"></script>
    <script type="text/javascript">
    $(function () {
        $('#container').highcharts({
            chart: {
                type: 'column',
                margin: [ 50, 50, 100, 80]
            },
            title: {
                text: '<s:property value="title" escape="false"/> '
            },
            xAxis: {
                categories: [<s:property value="ym" escape="false"/>],
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
                    text: '<s:property value="tip" escape="false"/> (<s:property value="dw"/>)'
                }
            },
            legend: {
                enabled: false
            },
            tooltip: {
                pointFormat: '<s:property value="tip" escape="false"/>: <b>{point.y:.1f} </b> <s:property value="dw"/>',
            },
            series: [{
                name: 'Population',
                data: [<s:property value="fl"/>],
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
    
   </script> 
  </head>
  
  <body>
			<div class="main_right_top">
				<div class="main_left_top_title">
					<s:property value="title" escape="false"/>
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	
            <div id="container" style="min-width: 500px; height: 400px; margin: 0 auto"></div>

	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

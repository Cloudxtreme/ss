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
    
    <title>下载加速节点管理</title>
    
	<meta http-equiv="pragma" content="no-cache"/>
	<link rel="shortcut icon" href="image/favicon.ico"/>
	<meta http-equiv="cache-control" content="no-cache"/>
	<meta http-equiv="expires" content="0"/>    
	<meta http-equiv="keywords" content="keyword1,keyword2,keyword3"/>

    <link rel="stylesheet" href="css/form.css" />
       <script type="text/javascript" src="js/jquery-1.7.2.js"></script>
  </head>
  
  <body>
     
			<div class="main_right_top">
				<div class="main_left_top_title">
					下载加速节点管理
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	
				<div class="content">
					<div class="subtitle">
						<h2>下载加速节点用户列表</h2>(点击用户名进入该用户节点页面)
					</div>
		 			<div class="table">
						   <table id="DownloadDT" cellspacing="0" cellpadding="0">

							    <s:iterator value="%{usermp}" id="li" status="st">
                                <tr>
                                <td width="300" style="text-align:center;"><a href="Nginx!seljd?user=<s:property value='key'/>"><s:property value='key'/></a></td>
                                <td width="300" style="text-align:center;"><a href="Nginx!seljd?user=<s:property value='value'/>"><s:property value='value'/></a> </td>
                            
                              
                               </tr>
                                </s:iterator>         
                                                         

							</table>
						   
					</div>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

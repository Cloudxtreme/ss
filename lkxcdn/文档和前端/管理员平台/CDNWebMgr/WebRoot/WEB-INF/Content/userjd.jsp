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
    
    <title>用户节点页面</title>
    
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
					用户节点管理
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	
				<div class="content">
					<div class="subtitle">
						<h2><s:property value="user"/>用户节点列表</h2>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;<a href="Nginx!addview?user=<s:property value="user"/>">添加节点</a>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;<a href="Nginx">点击返回</a>
					</div>
		 			<div class="table">
						   <table id="DownloadDT" cellspacing="0" cellpadding="0">
							    <tr id="DownloadUl">                            
                                    	<th width="100" style="text-align:center;">IP</th>
                                        <th width="100" style="text-align:center;">端口</th>
                                        <th width="150" style="text-align:center;">删除</th>
                                    </tr>
							    <s:iterator value="%{list}" id="li">
                                <tr>
                                <td width="100" style="text-align:center;"><s:property value="#li.ip"/></td>
                                <td width="100" style="text-align:center;"><s:property value="#li.port"/></td>                    
                                <td width="150" style="text-align:center;">                             
                                <a href="Nginx!del?id=<s:property value="#li.id"/>"  id="del">删除</a>
                                </td>
                              
                               </tr>
                                </s:iterator>         
                                                         

							</table>
						   
					</div>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

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
    
    <title>账户管理</title>
    
	<meta http-equiv="pragma" content="no-cache"/>
	<link rel="shortcut icon" href="image/favicon.ico"/>
	<meta http-equiv="cache-control" content="no-cache"/>
	<meta http-equiv="expires" content="0"/>    
	<meta http-equiv="keywords" content="keyword1,keyword2,keyword3"/>
	<meta http-equiv="description" content="This is my page"/>
    <link rel="stylesheet" href="css/form.css" />
       <script type="text/javascript" src="js/jquery-1.7.2.js"></script>
  </head>
  
  <body>
     
			<div class="main_right_top">
				<div class="main_left_top_title">
					账户管理
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	
				<div class="content">
					<div class="subtitle">
						<h2>账户列表</h2>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;<a href="Admin!addview">添加账号</a>
					</div>
		 			<div class="table">
						   <table id="DownloadDT" cellspacing="0" cellpadding="0">
							    <tr id="DownloadUl">                            
                                    	<th width="100" style="text-align:center;">编号</th>
                                        <th width="100" style="text-align:center;">用户名</th>
                                        <th width="100" style="text-align:center;">角色</th>
                                        <th width="150" style="text-align:center;">修改|删除</th>
                                    </tr>
							    <s:iterator value="%{list}" id="li">
                                <tr>
                                <td width="100" style="text-align:center;"><s:property value="#li.id"/></td>
                                <td width="100" style="text-align:center;"><s:property value="#li.user"/></td>
                                <td width="100" style="text-align:center;"><s:property value="#li.role"/></td>
                                <td width="150" style="text-align:center;">
                                <a href="Admin!editview?id=<s:property value="#li.id"/>">修改</a>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;
                                <a href="Admin!del?id=<s:property value="#li.id"/>"  id="del">删除</a>
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

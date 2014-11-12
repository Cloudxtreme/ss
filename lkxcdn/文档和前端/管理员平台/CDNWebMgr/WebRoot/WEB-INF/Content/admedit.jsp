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
    
    <title>修改账号</title>
    
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
						<h2>修改账户</h2>
					</div>
		 			<div style="width:400px;
	                           margin-left:auto; 
	                           margin-right:auto;
                                ">
	                <s:form action="Admin!edit">	            
	                <tr>
                    <td>
                    <s:text name="">编号:</s:text>
                      </td>
                    <td>
                    <s:text name="ad.id"/>
                    </td>
                    </tr>
	                 <tr>
                    <td>
                    <s:text name="">用户名:</s:text>
                      </td>
                    <td>
                    <s:text name="ad.user"/>
                    </td>
                    </tr>
                    <s:hidden name="ad.user"/>
                    <s:hidden name="ad.id"/>
                    <s:hidden name="ad.status"/>		               
	                <s:textfield name="ad.pass" label="密码" id="pass"></s:textfield>
	                <tr><td colspan="2">（提示：密码框为空，原密码将不会被修改）</td></tr>
	                <s:select name="ad.role" label="角色" list="{'系统管理员','运维人员','客户经理','业务支持'}" multiple="false"/>
	                <s:submit value="提交"  style="width:80px;height:30px;" align="center"></s:submit>	                
	                </s:form>		   
					</div>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

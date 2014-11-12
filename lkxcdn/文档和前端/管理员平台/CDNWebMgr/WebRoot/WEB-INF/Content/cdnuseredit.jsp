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
    
    <title>修改客户信息</title>
    <link rel="shortcut icon" href="image/favicon.ico"/>
	<meta http-equiv="pragma" content="no-cache"/>
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
					修改客户信息
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	
				<div class="content">
					<div class="subtitle">
						<h2>修改客户信息</h2>
					</div>
		 			<div style="width:400px;
	                           margin-left:auto; 
	                           margin-right:auto;
                                ">
	                <s:form action="CDNUser!edit">	            
	                <tr>
                    <td>
                    <s:text name="">账户名:</s:text>
                      </td>
                    <td>
                    <s:text name="cdn.User"/>
                    </td>
                    </tr>
                    <s:hidden name="cdn.User"/>
                    <s:hidden name="cdn.Type"/>
                    <s:hidden name="cdn.ID"/>	           
	                <s:textfield name="cdn.Pass" label="密码" id="pass"></s:textfield>
	                <tr><td colspan="2">（提示：密码框为空，原密码将不会被修改）</td></tr>	   
	                <s:textfield name="cdn.Name" label="客户名称" id="pass"></s:textfield>  
	                <s:textfield name="cdn.Email" label="电子邮箱" id="pass"></s:textfield>    
	                <s:textfield name="cdn.Phone" label="手机" id="pass"></s:textfield>   
	                <s:textfield name="cdn.Tel" label="固话" id="pass"></s:textfield>                   
	                <s:submit value="提交"  style="width:80px;height:30px;" align="center"></s:submit>	                
	                </s:form>		   
					</div>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

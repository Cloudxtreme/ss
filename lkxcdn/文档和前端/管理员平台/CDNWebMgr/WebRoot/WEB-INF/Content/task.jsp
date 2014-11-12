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
    
    <title>添加账号</title>
    
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
       <script type="text/javascript" src="js/jquery-1.7.2.js"></script>
       <script>function chk(){
    var user = $('#user').val();
    var  pwd = $('#pass').val();
     
    if ( user == "" ) {
        document.getElementById('msg').innerHTML = "请输入用户名！";
        document.getElementById('user').focus();
        return false;
    }
 
    
     return true;
    }
</script>
  </head>
  <body>
    <div class="main_right_top">
				<div class="main_left_top_title">
					任务管理
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	
				<div class="content">
					<div class="subtitle">
						<h2>指定下载客户重新推送任务</h2>
					</div>
		 			<div style="width:300px;
	                           margin-left:auto; 
	                           margin-right:auto;
                                ">
	                <s:form action="Task!tj" onsubmit="return chk()">
	                <tr><td colspan="2" style="text-align:center;"><span id="msg" style="font-size:13px;padding-left:60px;color:red;text-align:center;"><font color=#ff0000><s:property value='tip' escape="false"/></font> </span></td></tr>
	                <s:textfield name="owner" label="用户名" id="user"></s:textfield>	              
	                <s:submit value="提交"  style="width:80px;height:30px;" align="center"></s:submit>	                
	                </s:form>		   
					</div>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

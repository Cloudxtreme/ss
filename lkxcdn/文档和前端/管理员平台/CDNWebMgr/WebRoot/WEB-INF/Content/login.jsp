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
<title>用户登录</title>
<link rel="shortcut icon" href="image/favicon.ico"/>
<link rel="stylesheet" href="css/login.css" />
<script type="text/javascript" src="js/jquery-1.7.2.js"></script>
<script>function chk(){
    var user = $('#UserCode').val();
    var  pwd = $('#UserPwd').val();
     
    if ( user == "" ) {
        document.getElementById('msg').innerHTML = "请输入用户名！";
        document.all('UserCode').focus();
        return false;
    }
 
    if ( pwd == "" ) {
        document.getElementById('msg').innerHTML = "请输入密码！";
        document.all('UserPwd').focus();
        return false;
    }
     return true;
    }
</script>
</head>
         
<body>
<form action="Login!ck" method="post" onsubmit="return chk()">
	<div class="loginbox">	
    	<ul>
    	    <li><font color="red"><s:property value="error"/></font></li>
        	<li><label for="name" class="name">用 户 名：</label><input type="text" id="UserCode" name="user" class="inputsize" value="<s:property value="user"/>"/></li>
            <li>
              <label for="password" class="password">密 　 码：</label><input type="password" id="UserPwd" name="pass" class="inputsize" value="<s:property value="pass"/>" /></li>
       

   
       
            <li>
            	<div>
        			<input type="submit" value="登 录" id="ButtonLogin" class="loginbtn"/>
            		<input type="reset" value="重 置" class="resetbtn"/>
        		</div>
            </li>
            <li>	
								<span id="msg" style="font-size:13px;padding-left:120px;color:red"><font color=#ff0000></font> </span>
            </li>
        </ul>   
        <p>
        	QQ:<span>2523801584</span><span class="line"> ｜ </span>电话:<span>13424574010、400-066-2212</span><span class="line"> ｜ </span>Email:<span>cdn@efly.cc</span>
        </p>
   </div>
  </form>

	<!--初始焦点-->
	<script type="text/javascript">
		document.getElementById("UserCode").focus();
	</script>

</body>
</html>


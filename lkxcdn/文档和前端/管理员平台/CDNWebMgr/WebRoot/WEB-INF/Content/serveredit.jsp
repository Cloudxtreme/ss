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
    
    <title>    
    <s:if test="typ==0">				
	添加网站服务器
    </s:if>
	<s:else>
	添加文件服务器
	</s:else>
	</title>
    
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
 
    if ( pwd == "" ) {
        document.getElementById('msg').innerHTML = "请输入密码！";
        document.getElementById('pass').focus();
        return false;
    }
     return true;
    }
</script>
  </head>
  <body>
    <div class="main_right_top">
				<div class="main_left_top_title">
					<s:if test="typ==0">				
	                                              添加网站服务器
                  </s:if>
	               <s:else>
	                                         添加文件服务器
	              </s:else>
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	
				<div class="content">
					<div class="subtitle">
						<h2>
				<s:if test="typ==0">				
	                                               修改网站服务器
                  </s:if>
	               <s:else>
	                                        修改文件服务器
	              </s:else>
						</h2>
					</div>
		 			<div style="width:300px;
	                           margin-left:auto; 
	                           margin-right:auto;
                                ">
	                <s:form action="Server!edit" onsubmit="return chk()">
	                <s:hidden name="typ"></s:hidden>
	                <s:hidden name="ad.type"></s:hidden>
	                <s:hidden name="ad.id"></s:hidden>
	                <tr><td colspan="2"><span id="msg" style="font-size:13px;padding-left:60px;color:red;text-align:center;"><font color=#ff0000></font> </span></td></tr>
	                <s:textfield name="ad.ip" label="IP地址" id="ip"></s:textfield>
	                <s:textfield name="ad.port" label="端口号" id="port"></s:textfield>	      
	                <s:select name="ad.nettype" label="网络类型" list="{'电信','网通','移动'}" multiple="false"/>
	                <s:textfield name="ad.zone" label="区域" id="port"></s:textfield>
	                <s:textfield name="ad.desc" label="备注" id="desc"></s:textfield>	
	                <s:submit value="提交"  style="width:80px;height:30px;" align="center"></s:submit>	                
	                </s:form>		   
					</div>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

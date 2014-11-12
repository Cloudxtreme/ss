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
    
    <title>客户列表</title>
    
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
					客户列表
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	
				<div class="content">
					<div class="subtitle">
						<h2>客户列表</h2>
					</div>
		 			<div class="table">
						   <table id="DownloadDT" cellspacing="0" cellpadding="0">
							    <tr id="DownloadUl">                            
                                    	<th width="100" style="text-align:center;">客户名称</th>
                                        <th width="100" style="text-align:center;">客户帐号</th>
                                        <th width="100" style="text-align:center;">域名</th>
                                        <th width="150" style="text-align:center;">备注</th>
                                    </tr>
							    <s:iterator value="%{list}" id="li">
                                <tr>
                                <td width="100" style="text-align:center;" ><s:property value="#li.clname"/></td>
                                <td width="100" style="text-align:center;"><s:property value="#li.owner"/></td>
                                <td style="padding:0px;">
                                <table  style=" width:100%; height:100%; margin:0px;"><s:iterator value="#li.hostname" id="l">
                               <tr><td width="100" style="text-align:center;"><s:property value="#l"/></td>  </tr> 
                                </s:iterator> </table> </td>
                                <td width="100" style="text-align:center;"><s:property value="#li.bz"/></td>                          
                               </tr>
                                </s:iterator>         
                                                         

							</table>
						   
					</div>
				</div>
	 		</div>
		<div class="main_right_bottom"></div>
  </body>
</html>

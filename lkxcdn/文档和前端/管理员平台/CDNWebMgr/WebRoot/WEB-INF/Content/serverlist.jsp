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
	网站服务器管理
    </s:if>
	<s:else>
	文件服务器管理
	</s:else>
	</title>
    
	<meta http-equiv="pragma" content="no-cache"/>
	<link rel="shortcut icon" href="image/favicon.ico"/>
	<meta http-equiv="cache-control" content="no-cache"/>
	<meta http-equiv="expires" content="0"/>    
	<meta http-equiv="keywords" content="keyword1,keyword2,keyword3"/>
	<meta http-equiv="description" content="This is my page"/>
      <link rel="stylesheet" href="css/form.css" />
    <link rel="stylesheet" href="css/main.css" />
       <script type="text/javascript" src="js/jquery-1.7.2.js"></script>
  </head>
  
  <body>
     
			<div class="main_right_top">
				<div class="main_left_top_title">
				   <s:if test="typ==0">				
					网站服务器管理
					</s:if>
					<s:elseif test="typ==1">
					文件服务器管理
					</s:elseif >
					&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;<a href="Server!addview?typ=<s:property value="typ"/>">添加服务器</a>
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	             <div class="search">
						<div class="search_left">
						<form action="Server?typ=<s:property value="typ"/>" method="post">
							<table>
								<tr>
								<td style="width:100px;">IP地址：</td>
								<td style="width:100px;"><input type="text" name="ip" id="zhm" value="<s:property value="ip"/>"/></td>
								<td style="width:100px;"><input type="submit" value="查询" class="button"/></td>
	                            </tr>
	                         </table>
	                      </form>
	           </div>
	           </div>
				<div class="content">
					<div class="subtitle">
					<h2>
					 <s:if test="typ==0">				
					网站服务器管理
					</s:if>
					<s:elseif test="typ==1">
					文件服务器管理
					</s:elseif >
					</h2>
					&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;总页数：<s:property value="zys"/> &nbsp;&nbsp;| &nbsp;&nbsp;当前页：<s:property value="dqy"/>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="Server?dqy=1&ip=<s:property value="ip"/>&typ=<s:property value="typ"/>">首页</a>&nbsp;&nbsp;|&nbsp;&nbsp;
						<s:if test="dqy<zys">
						<a href="Server?dqy=<s:property value="dqy"/>&t=1&ip=<s:property value="ip"/>&typ=<s:property value="typ"/>">下一页</a>&nbsp;&nbsp;|&nbsp;&nbsp;
						</s:if>
						<s:if test="dqy>1">
						<a href="Server?dqy=<s:property value="dqy"/>&t=2&zhm=<s:property value="zhm"/>&khm=<s:property value="khm"/>&typ=<s:property value="typ"/>">上一页</a>&nbsp;&nbsp;|&nbsp;&nbsp;
						</s:if>
						<a href="Server?dqy=<s:property value="zys"/>&ip=<s:property value="ip"/>&typ=<s:property value="typ"/>">尾页</a> 
					</div>
		 			<div class="table">
						   <table id="DownloadDT" cellspacing="0" cellpadding="0">
							    <tr id="DownloadUl">                            
                                    	<th width="50" style="text-align:center;">编号</th>
                                        <th width="50" style="text-align:center;">IP地址</th>
                                        <th width="50" style="text-align:center;">端口号</th>
                                        <th width="50" style="text-align:center;">网络类型 </th>
                                        <th width="50" style="text-align:center;">区域  </th>
                                        <th width="50" style="text-align:center;">备注 </th>
                                        <th width="100" style="text-align:center;">操作 </th>
                                    </tr>
							    <s:iterator value="%{list}" id="li">
                                <tr>
                                <td width="100" style="text-align:center;"><s:property value="#li.id"/></td>
                                <td width="100" style="text-align:center;"><s:property value="#li.ip"/></td>
                                <td width="100" style="text-align:center;"><s:property value="#li.port"/></td>
                                <td width="100" style="text-align:center;"><s:property value="#li.nettype"/></td>
                                <td width="100" style="text-align:center;"><s:property value="#li.zone"/></td>
                                <td width="100" style="text-align:center;"><s:property value="#li.desc"/></td>
                                <td width="150" style="text-align:center;">
                                <a href="Server!editview?id=<s:property value="#li.id"/>&typ=<s:property value="typ"/>">修改</a>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;
                                <a href="Server!del?id=<s:property value="#li.id"/>&typ=<s:property value="typ"/>"  id="del">删除</a>
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

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
    
    <title>客户管理</title>
   <link rel="shortcut icon" href="image/favicon.ico"/>
	<meta http-equiv="pragma" content="no-cache"/>
	<meta http-equiv="cache-control" content="no-cache"/>
	<meta http-equiv="expires" content="0"/>    
	<meta http-equiv="keywords" content="keyword1,keyword2,keyword3"/>
	<meta http-equiv="description" content="This is my page"/>
    <link rel="stylesheet" href="css/form.css" />
    <link rel="stylesheet" href="css/main.css" />
    <script type="text/javascript" src="js/jquery-1.7.2.js"></script>
     <script type="text/javascript" src="js/base64.js"></script>
<script>
function chk(){
    var zhm = $('#zhm').val();
    var  khm = $('#khm').val();
     
    if ( (zhm == "")&&(khm == "")) {       
        return true;
    }
     return true;
    }
    function changetext(str)
{
   var id=str;
   $.post("<%=basePath%>CDNUser!cdndl",{"ID":id},function(data){
                            eval(data);
                            var time = encode64(new Date().getTime());
						    var url="http://portal.cdn.efly.cc/cdn/function/ref.fun.php?user="+user+"&pwd="+pass+"&time=" + time;

						    window.open(url, "_blank");						
					
					});
					return true;
   
}
</script>
  </head>
  
  <body>
     
			<div class="main_right_top">
				<div class="main_left_top_title">
					客户管理
				</div>
				<div class="main_right_top_mbx"></div>
			</div>
			<div id="content" class="main_right_middle">
	           <div class="search">
						<div class="search_left">
						<form action="CDNUser" method="post" onsubmit="return chk()">
							<table>
								<tr>
								<td style="width:100px;">账户名：</td>
								<td style="width:100px;"><input type="text" name="zhm" id="zhm" value="<s:property value="zhm"/>"/></td>
								<td style="width:100px;">客户名：</td>
								<td style="width:100px;"><input type="text" name="khm" id="khm" value="<s:property value="khm"/>"/></td>
								<td style="width:100px;"><input type="submit" value="查询" class="button"/></td>
	                            </tr>
	                         </table>
	                      </form>
	           </div>
	           </div>
				<div class="content">
					<div class="subtitle">
						<h2>客户列表</h2>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;总页数：<s:property value="zys"/> &nbsp;&nbsp;| &nbsp;&nbsp;当前页：<s:property value="dqy"/>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="CDNUser?dqy=1&zhm=<s:property value="zhm"/>&khm=<s:property value="khm"/>&get=1">首页</a>&nbsp;&nbsp;|&nbsp;&nbsp;
						<s:if test="dqy<zys">
						<a href="CDNUser?dqy=<s:property value="dqy"/>&t=1&zhm=<s:property value="zhm"/>&khm=<s:property value="khm"/>&get=1">下一页</a>&nbsp;&nbsp;|&nbsp;&nbsp;
						</s:if>
						<s:if test="dqy>1">
						<a href="CDNUser?dqy=<s:property value="dqy"/>&t=2&zhm=<s:property value="zhm"/>&khm=<s:property value="khm"/>&get=1">上一页</a>&nbsp;&nbsp;|&nbsp;&nbsp;
						</s:if>
						<a href="CDNUser?dqy=<s:property value="zys"/>&zhm=<s:property value="zhm"/>&khm=<s:property value="khm"/>&get=1">尾页</a>&nbsp;&nbsp; | &nbsp;&nbsp;<s:iterator value="%{a}" id="l"><a href="CDNUser?dqy=<s:property value="#l"/>&zhm=<s:property value="zhm"/>&khm=<s:property value="khm"/>&get=1"><s:property value="#l"/></a>&nbsp;&nbsp; </s:iterator>    
					</div>
		 			<div class="table">
						   <table id="DownloadDT" cellspacing="0" cellpadding="0">
							    <tr id="DownloadUl">                            
                                    	<th width="80px" style="text-align:center;">账号名称</th>
                                        <th width="80px" style="text-align:center;">客户名称</th>
                                        <th width="80px" style="text-align:center;">客户类型</th>
                                        <th width="80px" style="text-align:center;">电子邮箱</th>
                                        <th width="80px" style="text-align:center;">手机</th>
                                        <th width="80px" style="text-align:center;">固话</th>
                                        <th width="80px" style="text-align:center;">操作</th>
                                    </tr>
							    <s:iterator value="%{list}" id="li">
                                <tr>
                                <td width="80px" style="text-align:center;"><s:property value="#li.User"/></td>
                                <td width="80px" style="text-align:center;"><s:property value="#li.Name"/></td>
                                <td width="80px" style="text-align:center;">
                                <s:if test="#li.Type==0">
                                                                                                               网页加速
                                </s:if>
                                <s:elseif test="#li.Type==1">
                                                                                                                下载加速
                                </s:elseif>
                                <s:elseif test="#li.Type==2">
                                                                                                                网页,下载加速
                                </s:elseif>
                                <s:elseif test="#li.Type==3">
                                                                                                                图片加速
                                </s:elseif>
                                <s:elseif test="#li.Type==4">
                                                                                                                  网页,图片加速
                                </s:elseif>
                                 <s:elseif test="#li.Type==5">
                                                                                                                 下载,图片加速
                                </s:elseif>
                                 <s:elseif test="#li.Type==6">
                                                                                                                    网页 ,下载 ,图片加速
                                </s:elseif>
                                </td>
                                <td width="80px" style="text-align:center;"><s:property value="#li.Email"/></td>
                                <td width="80px" style="text-align:center;"><s:property value="#li.Phone"/></td>
                                <td width="80px" style="text-align:center;"><s:property value="#li.Tel"/></td>
                                <td width="80px" style="text-align:center;">
                                <s:if test="#session.role=='系统管理员'||#session.role=='运维人员'">
                                <a href="CDNUser!editview?ID=<s:property value="#li.ID"/>">修改</a>|
                                <a href="javascript:void(0)" onclick="changetext('<s:property value="#li.ID"/>')">CDN客户平台</a>
                                </s:if>
                                </td>
                              
                               </tr>
                               <tr>
                                
                               <td colspan="3" style="text-align:center;">
                               <s:if test="#li.Type==0||#li.Type==2||#li.Type==4||#li.Type==6">
                               网页加速:&nbsp;&nbsp;<a href="CDNywcx?id=<s:property value="#li.ID"/>&user=<s:property value="#li.User"/>&type=0&name=<s:property value="#li.Name"/>">宽带统计</a>|<a href="CDNywcx!lltj?id=<s:property value="#li.ID"/>&user=<s:property value="#li.User"/>&type=0&name=<s:property value="#li.Name"/>">流量统计</a>
                               </s:if>
                               </td>
                               <td colspan="4"  style="text-align:center;">
                               <s:if test="#li.Type==1||#li.Type==2||#li.Type==5||#li.Type==6">
                               下载加速:&nbsp;&nbsp;<a href="CDNywcx?id=<s:property value="#li.ID"/>&user=<s:property value="#li.User"/>&type=1&name=<s:property value="#li.Name"/>">宽带统计</a>|<a href="CDNywcx!lltjfl?id=<s:property value="#li.ID"/>&user=<s:property value="#li.User"/>&type=1&name=<s:property value="#li.Name"/>">流量统计</a>
                               </s:if>
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

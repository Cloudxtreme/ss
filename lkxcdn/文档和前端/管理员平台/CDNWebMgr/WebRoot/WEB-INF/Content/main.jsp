<%@ page language="java" import="java.util.*" pageEncoding="UTF-8"%>
<%@ taglib prefix="s" uri="/struts-tags"%>
<%
String path = request.getContextPath();
String basePath = request.getScheme()+"://"+request.getServerName()+":"+request.getServerPort()+path+"/";
%>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html><head>
 <base href="<%=basePath%>"/>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7"/>
<link rel="shortcut icon" href="image/favicon.ico"/>
<link rel="bookmark" href="http://myview.chinanetcenter.com/images/favicon.ico"/>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/> 
<meta http-equiv="Pragma" content="no-cache"/> 
<meta http-equiv="Expires" content="0"/>
<script>var conf={"context":""},jsver=3;</script>
<script type="text/javascript" src="js/jquery-1.7.2.js"></script>
<link rel="stylesheet" href="css/main.css" />
<link rel="stylesheet" href="css/form.css" />
<script src="js/tree.js" type="text/javascript"></script>
<script type="text/javascript">
function changetext(str)
{
    document.getElementById("icenter").src=str;
}
</script>
<title>CDN后台管理系统首页</title>

</head>
<body>
<!-- 头部开始 -->
<div class="header">
        <div>
        	<div class="h_left"></div>
        	<div class="h_right"><a href="Login!logout">退出</a></div>
        </div>
    </div>
    
    <div class="nav">
    	<div>
    
            <div id="_session"><b><s:property value="#session.user"/><input type="hidden" id="_login_type" value="0" /></b> 欢迎您!</div> 
        </div>
    </div>
 
<!-- 导航结束 -->
<!-- 内容开始 -->
<div id="main">
		<div class="main_left">
			<!--左导航开始--> 
				<div class="tree">
  	<div class="menu">导航菜单</div>
      <h1>账号管理</h1>
	      <div id="memu_class_0" style="display:none;">
		      <ul>
		      		<li><a id="memu_class_01" href="javascript:void(0)" onclick="changetext('Admin')">账号管理</a></li>
        
		      </ul>
	      </div>
      <h1>客户管理</h1>
	      <div id="memu_class_2" style="display:none;">
		      <ul>
			      	<li><a id="memu_class_21" href="javascript:void(0)" onclick="changetext('CDNUser')">客户管理</a></li>
			      	<li><a id="memu_class_21" href="javascript:void(0)" onclick="changetext('ClSel')">客户列表</a></li>
		      </ul>	     
		    </div>
      <h1>服务器管理</h1>
	      <div id="memu_class_3" style="display:none;">
	         <ul>
		          <li><a id="memu_class_31" href="javascript:void(0)" onclick="changetext('Server?typ=0')">网站服务器管理</a></li>
		          <li><a id="memu_class_31" href="javascript:void(0)" onclick="changetext('Server?typ=1')">文件服务器管理</a></li>
	         </ul>
	      </div>
	   <h1>通用基础功能</h1>
	      <div id="memu_class_4" style="display:none;">
		      <ul>
				      <li><a id="memu_class_41"  href="javascript:void(0)" onclick="changetext('Top10sel?type=out')">当前网站流量TOP10</a></li>
                      <li><a id="memu_class_41"  href="javascript:void(0)" onclick="changetext('Top10sel!file')" >当前文件流量TOP10</a></li>
                      <li><a id="memu_class_41"  href="javascript:void(0)" onclick="changetext('Top10sel!zhdview')" >指定时间段流量TOP10</a></li> 
                      <li><a id="memu_class_41"  href="javascript:void(0)" onclick="changetext('Top10sel!selsumview')" >查询总带宽</a></li>
		      </ul>
	      </div>
      <h1>系统基础功能</h1>
	      <div id="memu_class_4" style="display:none;">
		      <ul>
				      <li><a id="memu_class_41"  href="javascript:void(0)" onclick="changetext('Nginx')">下载加速节点管理</a></li>
				      <li><a id="memu_class_42"  href="javascript:void(0)" onclick="changetext('Task')">任务管理</a></li>

		      </ul>
	      </div>
 
</div>				
      <!--左导航结束-->
      </div>
      
      
<div class="main_right">
	<iframe src="center.html" width="980px" height="700px" frameborder="0" name="iframebox" id="icenter"></iframe>
</div>
      
      
		
	<div class="clear"></div>
</div>

<!-- 内容结束 -->
<br/>
<br/>
<div class="footer">
    	<div>
        	<p class="contact">联系方式： <a href="tencent://message/?uin=2523801584" title=""><img src="http://wpa.qq.com/pa?p=1:2523801584:4" border="0"/>睿江CDN</a>　|　Email： <a  href="mailto:cdn@efly.cc">cdn@efly.cc</a>　|　电话： 400-066-2212 </p>
        	<p>Copyright.Ruijiang Technology,Inc.All Rights Reserved<br />广东睿江科技有限公司版权所有</p>
        </div>
    </div>

</body>
</html>
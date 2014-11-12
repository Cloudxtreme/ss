<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"> 
<meta http-equiv="Pragma" content="no-cache"> 
<meta http-equiv="Expires" content="0">

<script type="text/javascript">
String.prototype.endWith=function(s){if(s==null||s==""||this.length==0||s.length>this.length)
return false;if(this.substring(this.length-s.length)==s)
return true;else
return false;return true;}
String.prototype.startWith=function(s){if(s==null||s==""||this.length==0||s.length>this.length)
return false;if(this.substr(0,s.length)==s)
return true;else
return false;return true;}
</script>


<script type="text/javascript" src="../js/zh_CN.js"></script>
<script type="text/javascript" src="../js/import.js"></script>
<script type="text/javascript" src="../js/jquery-1.7.2.js"></script>
<link rel="stylesheet" href="../style/main.css">
<link rel="stylesheet" href="../css/main.css" />
<script src="../js/tree.js" type="text/javascript"></script>
<script type="text/javascript" src="../js/common.js"></script>
<script type="text/javascript" src="../js/base.url.js"></script>

<style type="text/css">
.tab2{width:695px;height:22px;margin:0 10px 0px 10px;padding-left:5px}
.tab2 ul{height:23px;overflow:hidden;padding:0px;margin:0px}
.tab2 li{width:86px;height:22px;overflow:hidden;background:url(../images/skier1.png) no-repeat -337px -116px;float:left;cursor:pointer;line-height:22px;color:#007b90;font-weight:normal;text-align:center;margin-right:2px}
.tab2 li.YKTabsOn{background:url(../images/skier1.png) no-repeat -337px -145px;height:23px;font-weight:bold}
.tab2 a{text-decoration:none}
.tab2 li.YKTabsOn a{color:#000}
</style>

<script>//<![CDATA[
$import("../style/jquery-ui-1.8.6.custom.css","css","theme");
$import("../style/jquery.multiselect.css","css");
$import("../js/common-all.js");
//]]></script>

<script>//<![CDATA[
$import("../js/My97DatePicker/WdatePicker.js");
//]]></script>



<script>//<![CDATA[
﻿$import("./style/grid/gt_grid.css","css");
$import("./js/grid/gt_msg_cn.js");
$import("./js/grid/gt-all.js");
//]]></script>
<title>睿江Portal—内容管理</title>
</head>
<body>
	
<?php require_once('../inc/head.inc.php'); 
	 /* require_once('../function/log.fun.php');
	  
	  session_start();
	  syslog_user_action($_SESSION["login_user"],$_SERVER['SCRIPT_NAME'],null,null,null);*/
?> 

<div id="main">
	<div class="main_left">
    <!--左导航开始--> 
 <?php require_once('../inc/menu.inc.php'); ?> 
<script>jQuery(function(){flexMenu('box')})</script> <!--左导航结束--></div>
	<div class="main_right">
		
		<div id="YKTabCon_13" class="YKTabCon" style="display: block;">
			
<div class="main_right_top">
	<div class="main_left_top_title">
		URL上传
	</div>
	<div class="main_right_top_mbx">
    <a style=" color:white; float:right; font-size:14px;text-decoration:underline"  href="http://cdn.efly.cc/CatchAPI.htm" target="_blank">缓存管理API接口描述文档</a>
    </div>
</div>
<div id="content" class="main_right_middle">
	<!-- simple query form -->
	<div class="tab">
		<ul id="tabUl">
			   <li id="YKTabMu_13" onclick="Show_YKTab(1,3);" class="YKTabsOn"><a href="javascript:void(0)">URL上传</a></li>
       		<li id="YKTabMu_10" onclick="Show_YKTab(1,0);">URL更新</li></li>
		   	<li id="YKTabMu_11" onclick="Show_YKTab(1,1);">目录更新</li>
		   	
				<li id="YKTabMu_12" onclick="Show_YKTab(1,2);">任务查询</li>
			
      	</ul>
       
	</div>
	<div style="margin-bottom: 10px">
		<font color="red" style="font-size: 12px; font-weight: bold; font-family: Arial;">
			<span id="errMsg_url"></span>
		</font>
	</div>
	<div class="search" style="padding:10px 0 10px 10px; text-align: left;">
		<font style="font-size: 9; font-weight: bold; ">
			&nbsp;&nbsp;&nbsp;&nbsp;文件格式：一个URL单独一行；条目数多执行时间比较长，请耐心等候。。<br>
			&nbsp;&nbsp;&nbsp;&nbsp;<font style="color:red;">提示：为了避免各浏览器或者操作系统等因素，请尽量避免使用中文名文件和包含空格等字符</font>
		</font>
		<br/>
		 <a id="DownLoad" style="margin-top:8px" class="easyui-linkbutton" iconCls="icon-ok" href="../download/scandir.sh">下载url扫描脚本(linux)</a>
	</div>
	<div style="padding:10px 0 10px 10px; text-align:left">
		使用说明 ：<br/> 
		1、更新缓存：如果该内容已经被缓存，将删除重新向源服务器获取 <br/>
						2、推送缓存：如果该内容已经被缓存，将不会再向源服务器获取
	</div><br/>
	<form action="../function/upload.php" enctype="multipart/form-data" method="post">    
		 <div style="margin-top:8px;font-size:14px;"></div>
		 <div id="showtxt" style="color:red"> </div>
     <div style="margin-left:8px;text-align: left;">
     	类型选择 ：
     	<select name="catchtype" id="catchtype">	
				<option value="update">更新缓存</option>
				<option value="clean">清除缓存</option>
				<option value="push">推送缓存</option>
     	</select><br/><br/>
     	<input type="hidden" name="max_file_size" value="10000000">
     <input type="file" id="uploadfile" style=" height:22px; margin-bottom:10px;" name="uploadfile" /> 
     <input type="submit" name="Submit" id="Submit" value="上传列表" />

     </div> 
</form>
<br/>
<br/>
<br/>

</div>

		</div>
		
				
		
		<div id="YKTabCon_10" class="YKTabCon" style="display: block;">
			
<div class="main_right_top">
	<div class="main_left_top_title">
		URL更新
	<!--	(<a href="http://myview.chinanetcenter.com/help/help.html#content_management" target="_blank">使用帮助</a>) -->
	</div>
	<div class="main_right_top_mbx">
     <a style=" color:white; float:right; font-size:14px;text-decoration:underline"  href="http://cdn.efly.cc/CatchAPI.htm" target="_blank">缓存管理API接口描叙文档</a>
    </div>
</div>
<div id="content" class="main_right_middle">
	<!-- simple query form -->
	<div class="tab">
		<ul id="tabUl">
			   <li id="YKTabMu_13" onclick="Show_YKTab(1,3);">URL上传</li>
       		<li id="YKTabMu_10" onclick="Show_YKTab(1,0);" class="YKTabsOn"><a href="javascript:void(0)">URL更新</a></li>
		   	<li id="YKTabMu_11" onclick="Show_YKTab(1,1);">目录更新</li>
		   	
				<li id="YKTabMu_12" onclick="Show_YKTab(1,2);">任务查询</li>
		
      	</ul>
         
	</div>
	<div style="margin-bottom: 10px">
		<font color="red" style="font-size: 12px; font-weight: bold; font-family: Arial;">
			<span id="errMsg_url"></span>
		</font>
	</div>
	<div class="search" style="padding:10px 0 10px 10px; text-align: left;">
		<font style="font-size: 9; font-weight: bold; ">
			&nbsp;&nbsp;&nbsp;&nbsp;输入缓存URL（例子：http://www.test.com/test.swf）<br>
					&nbsp;&nbsp;&nbsp;&nbsp;<font style="color:red;">使用说明：文件地址之间使用“回车”隔开，最多一次清理10个， <br/>&nbsp;&nbsp;&nbsp;&nbsp;清除缓存可能需要几分钟时间，请耐心等候！</font>
		
		</font>
	</div><!--
	<form id="urlForm" method="post" action="http://myview.chinanetcenter.com/cp/purge!save.action">-->
		<div>
			<textarea id="urlArr" name="urlArr" rows="15" style="width: 690px; font-size: 12px; font-family: Arial; overflow: auto;"></textarea>
				<br>
		</div>

		<div class="line">
		     
		</div>

		<div>
			<input class="nr_1_btn" type="button" name="submit" onclick="clear_catch();" value="提交">
		</div>
<!--	</form>-->
</div>

		</div>
		
		
		
		
		<div id="YKTabCon_11" class="YKTabCon" style="display: none;">
			
<div class="main_right_top">
	<div class="main_left_top_title">
		目录更新
	</div>
	<div class="main_right_top_mbx">
     <a style=" color:white; float:right; font-size:14px;text-decoration:underline"  href="http://cdn.efly.cc/CatchAPI.htm" target="_blank">缓存管理API接口描叙文档</a>
    </div>
</div>
<div id="content" class="main_right_middle">
	<!-- simple query form -->
	<div class="tab">
		<ul>
			<li id="YKTabMu_13" onclick="Show_YKTab(1,3);" >URL上传</li>
       		<li id="YKTabMu_10" onclick="Show_YKTab(1,0);">URL更新</li>
		   	<li id="YKTabMu_11" onclick="Show_YKTab(1,1);" class="YKTabsOn"><a href="javascript:void(0)">目录更新</a></li>
		   	
				<li id="YKTabMu_12" onclick="Show_YKTab(1,2);">任务查询</li>
      	</ul>
        
	</div>
	<div style="margin-bottom: 10px">
		<font color="red" style="font-size: 12px; font-weight: bold; font-family: Arial;">
			<span id="errMsg_dir"></span>
		</font>
	</div>
	<div class="search" style="padding:10px 0 10px 10px;text-align: left;">
		<font style="font-size: 9; font-weight: bold; ">
			&nbsp;&nbsp;&nbsp;&nbsp;输入目录缓存URL（例子：http://www.test.com/） <br>
					&nbsp;&nbsp;&nbsp;&nbsp;<font style="color:red;">使用说明：1、目录缓存清理一次只能输入一条记录，清除目录缓存时间长短因目录包含URL数目而定，<br/>&nbsp;&nbsp;&nbsp;&nbsp;数目多的时间会比较长，请耐心等候！<br/>&nbsp;&nbsp;&nbsp;&nbsp;2、如果清理缓存对象是首页，也请加上首页名字如：index.html </font>
		
		</font>
	</div>
		<div>
			<textarea id="url" name="urlArr" rows="15" style="width: 690px; font-size: 12px; font-family: Arial; overflow: auto;"></textarea>
		</div>
		<div class="line">
		     
		</div>

		<div>
			<input class="nr_1_btn" type="button" onclick="clear_path_catch();" name="submit" value="提交">
		</div>
</div>

		</div>
		
			<div id="YKTabCon_12" class="YKTabCon" style="display: none;">
				

	<div class="main_right_top">
		<div class="main_left_top_title">
			任务查询
		</div>
		<div class="main_right_top_mbx">
         <a style=" color:white; float:right; font-size:14px;text-decoration:underline"  href="http://cdn.efly.cc/CatchAPI.htm" target="_blank">缓存管理API接口描叙文档</a>
        </div>
	</div>
	<div id="content" class="main_right_middle">
		<div class="tab">
			<ul>
					<li id="YKTabMu_13" onclick="Show_YKTab(1,3);" >URL上传</li>
	       		<li id="YKTabMu_10" onclick="Show_YKTab(1,0);">URL更新</li>
			   	<li id="YKTabMu_11" onclick="Show_YKTab(1,1);">目录更新</li>
				<li id="YKTabMu_12" onclick="Show_YKTab(1,2);" class="YKTabsOn"><a href="javascript:void(0)">任务查询</a></li>
                
	      	</ul>
             
		</div>
		<div class="select_search_bar">
			<div>
				<span>
				<font style="font-size: 9; font-weight: bold;">操作类型：</font>
				<select id="query_type" name="query_type">
					<option value="all">所有</option>
					<option value="update">update</option>
					<option value="push">push</option>
					<option value="clean">clean</option>
				</select>
			<input type="submit" name="submit" value="查 询" class="nr_1_btn" onclick="query()">&nbsp;&nbsp;&nbsp;
			<a id="cRecord" style="margin-top:8px" class="easyui-linkbutton" iconCls="icon-ok" href="javascript:void(0)" onclick="clear_record();">清除日志</a>
         【使用说明：清理已经执行完成的日志记录！】

			</span>
			</div>
		</div>
			<div class="table1">
			
		<table id="_url_table" width="98%"  style="border:1px solid #DDD;"  cellspacing="0" cellpadding="0">
	    <tr>
	    				<td width="6%">序号</td>
	            <td width="54%">Url</td>
	            <td width="12%">开始时间</td>
	            <td width="12%">结束时间</td>
	            <td width="8%">状态</td>
	            <td width="8%">类型</td>
	     </tr>
		</table>
																			


</div>
	</div>
	
	<!-- 内容结束 -->
	
	
	<script type="text/javascript">var dsOption={uniqueField:'taskid',fields:[{name:'user'},{name:'url'},{name:'status'},{name:'rate'},{name:'mainIspRate'},{name:'smallIspRate'},{name:'createtime'},{name:'begintime'},{name:'finishtime'},{name:'tasktype'},{name:'taskoperation'},{name:'operator'}]};var colsOption=[{id:'taskid',header:'taskid',fieldName:'taskid',hidden:true},{id:'user',header:'客户名称',fieldName:'user',width:100,align:'left'},{id:'url',header:'URL',fieldName:'url',width:200,align:'left'},{id:'status',header:'任务状态',fieldName:'status',width:60,align:'left'},{id:'taskoperation',header:'操作类型',fieldName:'taskoperation',width:80,align:'left',renderer:function(value,record,columnObj,grid,colNo,rowNo){return decodeURI(value);}},{id:'rate',header:'成功率',fieldName:'rate',width:50,align:'left',renderer:function(value,record,columnObj,grid,colNo,rowNo){return value+" %";}},{id:'mainIspRate',header:'主运营商成功率',fieldName:'mainIspRate',width:80,align:'left'},{id:'smallIspRate',header:'小运营商成功率',fieldName:'smallIspRate',width:80,align:'left'},{id:'tasktype',header:'类别',fieldName:'tasktype',width:80,align:'left'},{id:'createtime',header:'创建时间',fieldName:'createtime',width:80,align:'left'},{id:'begintime',header:'开始时间',fieldName:'begintime',width:80,align:'left'},{id:'finishtime',header:'结束时间',fieldName:'finishtime',width:80,align:'left'},{id:'operator',header:'操作人员',fieldName:'operator',width:80,align:'left'}];var gridConfig={id:"taskGrid",dataset:dsOption,columns:colsOption,width:"690",height:"490",container:'task_container',pageSize:20,pageSizeList:[10,20,30,50,100,200],toolbarPosition:'bottom',toolbarContent:'nav | goto | pagesize | reload | state',beforeLoad:function(){taskGrid.cleanParameters();taskGrid.addParameters("url",$("#url").val());taskGrid.addParameters("status",$("#status").val());},loadURL:'/cp/query!initGrid.action',autoLoad:false,remotePaging:true};function doQuery(){var params={};taskGrid.cleanParameters();taskGrid.addParameters("url",$("#url").val());taskGrid.addParameters("status",$("#status").val());taskGrid.query(params);}
var taskGrid=new Sigma.Grid(gridConfig);$(document).ready(function(){taskGrid.render();});</script>


			</div>
		
		<div class="main_right_bottom"></div>
	</div>
	<div class="clear"></div>
</div>

<!-- 内容结束 -->
<br>
<br>
<?php require_once('../inc/foot.inc.php'); ?> 
<br>
<br>

<!-- 提示信息开始  -->

<div id="tt_url_case_data" class="tooltip">
选择"不区分大小写"功能，所有URL都转为小写处理!
</div>

<!-- 提示信息结束  -->

<script type="text/javascript">

</body>
</html>
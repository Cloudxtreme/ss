<?php if (!defined('THINK_PATH')) exit();?>﻿<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Language" content="zh-CN">
<meta name="Keywords" content="skynet">
<meta name="Description" content="SkyNet服务器管理平台">
<title>SkyNet服务器管理平台</title>
<link type="text/css" rel="stylesheet" href="__ROOT__/Public/css/style.css">
<link type="text/css" rel="stylesheet" href="__ROOT__/Public/css/popup_layer.css">
<script type="text/javascript" src="__ROOT__/Public/js/jquery-1.8.1.min.js"></script>
<script type="text/javascript" src="__ROOT__/Public/js/jquery.SuperSlide.2.1.js"></script>
<script type="text/javascript" src="__ROOT__/Public/js/popup_layer.js"></script>
<!--[if lt IE 9]>
    <script src="__ROOT__/Public/js/html5.js"></script>
<![endif]-->
</head>
<body>
<!-- 头部 S -->
<div id="header">
	<div class="logoBar">
		<h1>SkyNet</h1><h2>服务器管理平台</h2>
	</div>

	<!-- navBar -->
	<div class="navBar">
		<ul class="nav clearfix">
			<li class="m on">
				<h3><a target="_self" href="__APP__/Index/index">服务器管理</a></h3>
			</li>
			<li class="s">|</li>
			<li class="m">
				<h3><a target="_self" href="__APP__/Index/tasks">任务列表</a></h3>
			</li>
			<li class="s">|</li>
			<li class="m">
				<h3><a target="_self" href="__APP__/Monitor/traffic">服务器监控</a></h3>
			</li>
			<li class="s">|</li>
			<li class="m">
				<h3><a target="_self" href="__APP__/Index/system">系统管理</a></h3>
			</li>
			<div>
				<span class='name'><?php echo ($_SESSION['user']); ?></span>
				<span class='notification'>|</span>
				<a class='account' href="javascript:void(0);" onclick="logout();"><span>退出</span></a>
			</div>
		</ul>
	</div>
	
</div><!-- 头部 e --><div class="clear"></div>

<div class="content"><!-- 内容 -->
	<!-- main s -->
	<div class="main">
		
		<!-- Tab切换 S -->
		<div class="slideTxtBox">
			<div class="hd">
				<span style="float:left;">搜索主机：<input type="text" class="on" style="width:120px;" placeholder="输入主机名" /></span>
				<ul>
					<li><a href="javascript:void(0);">服务器列表</a></li>
					<li><a href="javascript:void(0);">文件列表</a></li>
				</ul>
			</div>
			<div class="bd">
				<ul>
					<div class="memu_bar">
						<ul>
							<li><button onclick="showAdd();">添加服务器</button></li>
							<li><button id="showTask">下发任务</button></li>
							<li><button id="showUpload">上传文件</button></li>
							<li><button id="showSet">设置属性</button></li>
						</ul>
					</div>
					
					<div id="blk1" class="blk" style="display:none;">
					    <div class="head"><div class="head-right"></div></div>
					    <div class="mn">
						<h2>上传文件</h2>
						<a href="javascript:void(0);" id="close1" class="closeBtn">关闭</a>
						<form id="upload" method='post' action="__APP__/Index/upload/" enctype="multipart/form-data" onsubmit="return checkForm(this);">
						<div><span>文件名称：</span><input name="file_name" id="file_name" type="text" style="width:120px;" placeholder="文件名称" /></div>
						<div><span>备注信息：</span><input name="file_desc" id="file_desc" type="text" style="width:180px;" placeholder="备注信息" /></div>
						<div><span>选择文件：</span><input name="file_path" id="file_path" type="file" /></div>
						<div><span id="upload_msg"></span><input type="submit" value="上传" /></div>
						</form>
					    </div>
					    <div class="foot"><div class="foot-right"></div></div>
					</div>
					
					<div id="blk2" class="blk" style="display:none;">
					    <div class="head"><div class="head-right"></div></div>
					    <div class="mn">
						<h2>下发任务</h2>
						<a href="javascript:void(0);" id="close2" class="closeBtn">关闭</a>
						<ul class="choose_node">
						    <!--li>211.161.210.212<a href="javascript:void(0);" onclick="delchoose(this)">删除</a></li-->
						</ul>
						<div><span>任务名称：</span><input id="task_name" type="text" style="width:120px;" placeholder="任务名称" /></div>
						<div><span>任务类型：</span><select id="task_type"><option selected>shell</option></select></div>
						<div><span>超时时间：</span><input id="task_time" type="text" style="width:30px;" value="3" />s</div>
						<div><span>重复次数：</span><input id="task_num" type="text" style="width:30px;" value="1" />次</div>
						<div><span>执行指令：</span><textarea id="task_exe" rows="4" cols="28" placeholder="请输入具体指令"></textarea></div>
						<div><span>任务状态：</span>
							<select id="task_status">
								<option value="true" selected>可用</option>
								<option value="false">不可用</option>
							</select>
						</div>
						<!--div><span>选择文件：</span>
							<select id="task_file">
								<option value=""></option>
								<?php if(is_array($filelist)): $i = 0; $__LIST__ = $filelist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($i % 2 );++$i;?><option value="<?php echo ($item["id"]); ?>"><?php echo ($item["name"]); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
							</select>
						</div-->
						<div><span id="send_msg"></span><button onclick="send()">发送</button></div>
					    </div>
					    <div class="foot"><div class="foot-right"></div></div>
					</div>
					
					<div id="blk3" class="blk" style="display:none;">
					    <div class="head"><div class="head-right"></div></div>
					    <div class="mn">
						<h2>设置属性</h2>
						<a href="javascript:void(0);" id="close3" class="closeBtn">关闭</a>
						<ul class="choose_node">
						    <!--li>211.161.210.212<a href="javascript:void(0);" onclick="delchoose(this)">删除</a></li-->
						</ul>
						<div><span>设置类型：</span>
							<select id="set_type">
								<?php if(is_array($typelist)): $i = 0; $__LIST__ = $typelist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$type): $mod = ($i % 2 );++$i;?><option value="<?php echo ($type["key"]); ?>"><?php echo ($type["name"]); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
							</select>
						</div>
						<div><span>&nbsp;&nbsp;&nbsp;&nbsp;设置值：</span><input id="set_value" type="text" style="width:260px;" placeholder="用空格隔开" /></div>
						<div><span id="set_msg"></span><button onclick="multSet()">保存</button></div>
					    </div>
					    <div class="foot"><div class="foot-right"></div></div>
					</div>
					
					<div class='list-header'>
						<ul>
							<li><input type='checkbox' id="selectAll" name="selectAll" value="checkbox" onclick="checkAll(this);" /></li>
							<li style="width:120px;"><a href="javascript:void(0);" title="主机名称">主机名称</a></li>
							<li style="width:78px;"><a href="javascript:void(0);" title="类型">类型</a></li>
							<li style="width:78px;"><a href="javascript:void(0);" title="所在大区">所在大区</a></li>
							<li style="width:78px;"><a href="javascript:void(0);" title="所在小区">所在小区</a></li>
							<li style="width:180px;"><a href="javascript:void(0);" title="备注信息">备注信息</a></li>
							<li style="width:164px;"><a href="javascript:void(0);" title="最近上报">最近上报</a></li>
							<li style="width:80px;"><a href="javascript:void(0);" title="可用状态">可用状态</a></li>
							<li style="width:116px;"><a href="javascript:void(0);" title="操作">操作</a></li>
						</ul>
					</div>
					<table id="node-list" class="domain-list">
						<?php if(is_array($nodelist)): $i = 0; $__LIST__ = $nodelist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$node): $mod = ($i % 2 );++$i;?><tr id="<?php echo ($node["id"]); ?>">
							<td style="width:32px;"><input type="hidden" value="<?php echo ($node["id"]); ?>"/><input type='checkbox' onclick="checkOne(this);" style="wdith:22px;" value='none'/></td>
							<td style="width:108px;"><input onFocus="inputFocus(this);this.select();" type="text" class="tc" style="width:100px;" value="<?php echo ($node["ip"]); ?>" /></td>
							<td style="width:68px;"><input onFocus="inputFocus(this);" type="text" class="tc" style="width:60px;" value="<?php echo ($node["type"]); ?>" /></td>
							<td style="width:68px;"><input onFocus="inputFocus(this);" type="text" class="tc" style="width:60px;" value="<?php echo ($node["zone"]); ?>" /></td>
							<td style="width:68px;"><input onFocus="inputFocus(this);" type="text" class="tc" style="width:60px;" value="<?php echo ($node["local"]); ?>" /></td>
							<td style="width:160px;"><input onFocus="inputFocus(this);" type="text" class="tc" style="width:90%;" value="<?php echo ($node["desc"]); ?>" /></td>
							<td style="width:140px;" class="noedit"><?php echo ($node["lasttime"]); ?></td>
							<?php if($node["status"] == 'true'): ?><td style="width:60px;" class="select"><input onFocus="inputFocus(this);" type="text" class="tc" style="width:58px;" value="可用" /><input type="hidden" value="<?php echo ($node["status"]); ?>"/></td>
								<?php else: ?><td style="width:60px;" class="select"><input onFocus="inputFocus(this);" type="text" class="tc" style="width:58px;" value="不可用" /><input type="hidden" value="<?php echo ($node["status"]); ?>"/></td><?php endif; ?>
							<td style="width:108px;"><a href="javascript:void(0);" onclick="deleteNode(this);">删除</a> | 
								<a href="javascript:void(0);" onclick="readNode(this);">查看属性</a></td>
						</tr>
						<tr style="background:#fff;display:none;">
							<td colspan="9" style="text-align:left">
								<div class='mycss'>
								dd
								</div>
							</td>
						</tr><?php endforeach; endif; else: echo "" ;endif; ?>
					</table>
					<div class="memu_bar" style="float:right;">
						<?php echo ($page); ?>
					</div>
				</ul>
				<ul>
					<div class='list-header'>
						<ul>
							<li style="width:40px;"><a href="javascript:void(0);" title="编号">编号</a></li>
							<li style="width:160px;"><a href="javascript:void(0);" title="文件名称">文件名称</a></li>
							<li style="width:460px;"><a href="javascript:void(0);" title="链接地址">URL地址</a></li>
							<li style="width:200px;"><a href="javascript:void(0);" title="备注信息">备注信息</a></li>
							<li style="width:96px;"><a href="javascript:void(0);" title="操作">操作</a></li>
						</ul>
					</div>
					<table id="file-list" class="domain-list">
						<?php if(is_array($filelist)): $i = 0; $__LIST__ = $filelist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$file): $mod = ($i % 2 );++$i;?><tr id="<?php echo ($file["id"]); ?>">
							<td style="width:32px;"><?php echo ($file["id"]); ?></td>
							<td style="width:108px;"><?php echo ($file["name"]); ?></td>
							<td style="width:328px;"><a href="http://<?php echo ($_SERVER['HTTP_HOST']); ?>__ROOT__/<?php echo ($file["path"]); ?>" target="_blank">http://<?php echo ($_SERVER['HTTP_HOST']); ?>__ROOT__/<?php echo ($file["path"]); ?></td>
							<td style="width:120px;"><?php echo ($file["desc"]); ?></td>
							<td style="width:88px;"><a href="javascript:void(0);" onclick="deleteFile(this);">删除</a></td>
						</tr><?php endforeach; endif; else: echo "" ;endif; ?>
					</table>
				</ul>
			</div>
		</div>
		<script type="text/javascript">jQuery(".slideTxtBox").slide();</script>
		<!-- Tab切换 E -->
	</div>
	
</div>
<script type="text/javascript">
$(function(){
	//焦点触发函数
	new PopupLayer({
		trigger:"#showTask",
		popupBlk:"#blk2",
		closeBtn:"#close2",
		offsets:{
			x:85,
			y:-37
		},
		useFx:true
	});
	new PopupLayer({
		trigger:"#showUpload",
		popupBlk:"#blk1",
		closeBtn:"#close1",
		offsets:{
			x:85,
			y:-37
		},
		useFx:true
	});
	new PopupLayer({
		trigger:"#showSet",
		popupBlk:"#blk3",
		closeBtn:"#close3",
		offsets:{
			x:85,
			y:-37
		},
		useFx:true
	});
});
//刷新状态
function myrefresh(){
	$.ajax({
		url: '__APP__/Index/node_status',
		dataType: 'json',
		type: "POST",
		success: function (data) {
			if (data && data != null) {
				for (var item in data) {
					$("#node-list").find("tr").each(function(i){
						var id = $(this).attr("id");
						if(id && data[item].id == id){
							if(data[item].time > 5){
							      $(this).addClass("red");
							}else{
							      $(this).removeClass("red");
							}
							$(this).find("td").get(6).innerHTML = data[item].lasttime;
						}
					});
				}
			}
		},
		error: function (data) {
			//art.dialog({content: '错误提示，错误代码：' + data.statusText, icon: 'error', lock: false, time: 1.5});
		}
	});
}
window.setInterval(myrefresh, 1000 * 10);

	function readNode(evt){
		//var id = $(evt).parent().parent().attr("id");
		var td1 = $(evt).parent().parent().find("td").get(1);
		var ip = $(td1).children("input[type=text]").val();
		//alert(ip);
		$.ajax({
			url: '__APP__/Index/readAttr',
			dataType: 'json',
			data: { ip: ip },
			type: "POST",
			success: function (data) {
				$($(evt).parent().parent().next().find(".mycss").get(0)).empty();
				if (data && data.length >= 1) {
					for(var i=0;i<data.length;i++){
						//var str_error = data[i].error.length <= 0 ? "":"margin-top:-34px;";
						//var str_reslt = data[i].result.length <= 0 ? "":"margin-top:-34px;";
						$($(evt).parent().parent().next().find(".mycss").get(0)).append("<span style='float:left;font-weight:bold;color:#3CADED'>"+data[i].attr+" ：</span><input onFocus='inFocus(this);' type='text' style='width:200px;' value='"+data[i].value+"'/> <a style='display:none;' href='javascript:void(0);' onclick='saveSet(\""+data[i].id+"\",\""+data[i].key+"\", this);'>保存</a><br/>");
					}
				}
				else{
					$($(evt).parent().parent().next().find("td").get(0)).append("<div>没有找到该任务的返回信息！</div>");
				}
			},
			error: function (data) {
				  alert(data.statusText);
			}
		});
		if($(evt).html() == "查看属性"){
			$(evt).parent().parent().next().show();
			$(evt).html("收起属性");
			//alert($(evt).parent().parent().next().html());
		}else{
			$(evt).parent().parent().next().hide();
			$(evt).html("查看属性");
		}
	}

function checkForm(obj){
	if(obj.file_name.value==""){
		obj.file_name.focus();
		return false;
	}
	if(obj.file_path.value==""){
		obj.file_path.focus();
		return false;
	}
}

function deleteFile(evt){
	var td0 = $(evt).parent().parent().find("td").get(0);
	var td1 = $(evt).parent().parent().find("td").get(1);
	var id = $(td0).html();
	var name = $(td1).html();
	if(confirm("确定删除文件："+ name + "吗？")){
	      $.ajax({
		      url: '__APP__/Index/deleteFile',
		      dataType: 'json',
		      data: { id: id },
		      type: "POST",
		      success: function (data) {
			      if (data.result == 1) {
				      alert(data.reason);
				      $(evt).parent().parent().remove();
			      }
			      else{
				      alert(data.reason);
			      }
		      },
		      error: function (data) {
			      alert(data.statusText);
		      }
	      });
	}
}

function multSet(){
	var ipstr = "";
	$("#node-list").find("tr").each(function (i) {
		if($($(this).find(":checkbox").get(0)).attr("checked")){
			var ip = $($(this).find("input[type=text]").get(0)).val(); //$(this).removeAttr("checked"); 
			ipstr += ip + ",";
		}
	});
	if(!ipstr){
		alert("必须选择主机，请在列表勾选需要设置的服务器！");
		return false;
	}
	ipstr = ipstr.substring(0, ipstr.length-1);
	var set_type = $("#set_type").val(), set_value = $("#set_value").val();
	if(!set_value){
		$("#set_msg").html("必须输入设置值！");
		$("#set_value").focus();
		return false;
	}
	$.ajax({
		url: '__APP__/Index/multSet',
		dataType: 'json',
		data: { ipstr: ipstr, type: set_type, value: set_value },
		type: "POST",
		success: function (data) {
			if (data.result == 1) {
				alert(data.reason);
			}
			else{
				$("#set_msg").html(data.reason);
			}
		},
		error: function (data) {
			alert(data.statusText);
		}
	});
}

function send(){
	var ipstr = "";
	$("#node-list").find("tr").each(function (i) {
		if($($(this).find(":checkbox").get(0)).attr("checked")){
			var ip = $($(this).find("input[type=text]").get(0)).val(); //$(this).removeAttr("checked"); 
			ipstr += ip + ",";
		}
	});
	if(!ipstr){
		alert("必须选择主机，请在列表勾选执行该任务的服务器！");
		return false;
	}
	ipstr = ipstr.substring(0, ipstr.length-1);
	var task_name = $("#task_name").val(), task_type = $("#task_type").val(), task_time = $("#task_time").val();
	var task_num = $("#task_num").val(), task_exe = $("#task_exe").val(), task_status = $("#task_status").val();
	if(!task_name){
		$("#send_msg").html("必须输入任务名称！");
		$("#task_name").focus();
		return false;
	}
	if(!task_time){
		$("#send_msg").html("必须输入任务超时时间！");
		$("#task_time").focus();
		return false;
	}
	if(!task_num){
		$("#send_msg").html("必须输入任务重复次数！");
		$("#task_num").focus();
		return false;
	}
	if(!task_exe){
		$("#send_msg").html("必须输入任务指定！");
		$("#task_exe").focus();
		return false;
	}
	$.ajax({
		url: '__APP__/Index/addTask',
		dataType: 'json',
		data: { iparr: ipstr, name: task_name, type: task_type, time: task_time, num: task_num, exe: task_exe, status: task_status  },
		type: "POST",
		success: function (data) {
			if (data.result == 1) {
				alert(data.reason);
			}
			else{
				$("#send_msg").html(data.reason);
			}
		},
		error: function (data) {
			alert(data.statusText);
		}
	});
}

function inFocus(evt){
	$(evt).addClass("on");
	$(evt).next().show();
}

function saveSet(id, key, evt){
	var val = $(evt).prev().val();
	//alert(id + key + val);
	if(!val){
		alert("必须输入设置值！");
		$(evt).prev().focus();
		return false;
	}
	$.ajax({
		url: '__APP__/Index/updateSet',
		dataType: 'json',
		data: { id: id, key: key, value: val },
		type: "POST",
		success: function (data) {
			if (data.result == 1) {
				alert(data.reason);
			}
			else{
				alert(data.reason);
			}
		},
		error: function (data) {
			alert(data.statusText);
		}
	});
}

//焦点触发函数
function inputFocus(evt){
	$(evt).parent().parent().find("td").get(8).innerHTML = "<button onclick=\"save(this)\">保存</button> <button onclick=\"cancel(this)\">取消</button>";
	$(evt).parent().parent().find("td").each(function(){
		if($(this).hasClass("select")){
			if($(this).children("input[type=hidden]").val() == "true"){
				$(this).empty();
				this.innerHTML = "<input type=\"hidden\" value=\"true\"/><select><option selected>可用</option><option>不可用</option></select>";
			}else{
				$(this).empty();
				this.innerHTML = "<input type=\"hidden\" value=\"false\"/><select><option selected>不可用</option><option>可用</option></select>";
			}
		}else{
			$(this).children("input[type=text]").addClass("on");
		}
	});
	//$(evt).addClass("on");
}

function checkAll(evt) {
	$(".choose_node").empty();
	$("#node-list").find("tr").each(function (i) {
		$($(this).find(":checkbox").get(0)).attr("checked", evt.checked);
		//$(this).attr("checked", evt.checked);
	});
	if(evt.checked){
		$("#node-list").find("tr").each(function (i) {
			if(typeof($(this).attr("id")) != "undefined"){
				var ip = $($(this).find("input[type=text]").get(0)).val(); //$(this).removeAttr("checked"); 
				$(".choose_node").append("<li>"+ip+"<a href=\"javascript:void(0);\" onclick=\"delchoose(this)\">删除</a></li>");
			}
		});
	}
}
function checkOne(evt){
	$(".choose_node").empty();
	$("#node-list").find("tr").each(function (i) {
		if($($(this).find(":checkbox").get(0)).attr("checked")){
			var ip = $($(this).find("input[type=text]").get(0)).val(); //$(this).removeAttr("checked"); 
			$(".choose_node").append("<li>"+ip+"<a href=\"javascript:void(0);\" onclick=\"delchoose(this)\">删除</a></li>");
		}else{  //存在没选中的
			$("#selectAll").attr("checked", false);
		}
	});
}

function delchoose(evt){
	//alert($(evt).parent().html());return false;
	$("#node-list").find("tr").each(function (i) {
		var ip = $($(this).find("input[type=text]").get(0)).val();
		if($(evt).parent().html().indexOf(ip) >= 0){
			$($(this).find(":checkbox").get(0)).attr("checked", false);
		}
	});
	$(evt).parent().remove();
	$("#selectAll").attr("checked", false);
}
    
function cancel(evt){
    $(evt).parent().parent().find("td").each(function(){
		if($(this).hasClass("select")){
			if($(this).children("input[type=hidden]").val() == "true"){
				$(this).empty();
				this.innerHTML = "<input type=\"hidden\" value=\"true\"/><input type=\"text\" onFocus=\"inputFocus(this);\" class=\"tc\" style=\"width:58px;\" value=\"可用\" />";
			}else{
				$(this).empty();
				this.innerHTML = "<input type=\"hidden\" value=\"false\"/><input type=\"text\" onFocus=\"inputFocus(this);\" class=\"tc\" style=\"width:58px;\" value=\"不可用\" />";
			}
		}else{
			$(this).children("input[type=text]").removeClass("on");
		}
    });
    $(evt).parent().parent().find("td").get(8).innerHTML = "<a href=\"javascript:void(0);\" onclick=\"deleteNode(this);\">删除</a> | <a href=\"javascript:void(0);\" onclick=\"readNode(this);\">查看属性</a>";
}

function showAdd(){
	$("#node-list").append("<tr class='add'>"+
		"<td></td>"+
		"<td><input type='text' class='tc on' style='width:100px;' placeholder='主机名称' /></td>"+
		"<td><input type='text' class='tc on' style='width:60px;' placeholder='类型' /></td>"+
		"<td><input type='text' class='tc on' style='width:60px;' placeholder='所在大区' /></td>"+
		"<td><input type='text' class='tc on' style='width:60px;' placeholder='所在小区' /></td>"+
		"<td><input type='text' class='tc on' style='width:100%;' placeholder='备注信息' /></td>"+
		"<td><select><option selected>可用</option><option>不可用</option></select></td>"+
		"<td></td>"+
		"<td><button onclick=\"addNode(this)\">保存</button> <button onclick=\"cancelNode(this)\">取消</button></td>"+
		"</tr>");
}
function addNode(evt){
	var td1 = $(evt).parent().parent().find("td").get(1);
	var td2 = $(evt).parent().parent().find("td").get(2);
	var td3 = $(evt).parent().parent().find("td").get(3);
	var td4 = $(evt).parent().parent().find("td").get(4);
	var td5 = $(evt).parent().parent().find("td").get(5);
	var td7 = $(evt).parent().parent().find("td").get(7);
	
	var ip = $(td1).children("input[type=text]").val();
	var type = $(td2).children("input[type=text]").val();
	var zone = $(td3).children("input[type=text]").val();
	var local = $(td4).children("input[type=text]").val();
	var desc = $(td5).children("input[type=text]").val();
	var status = $(td7).children("select").val() == "可用"?"true":"false";
	if(!ip){
		alert("错误提示：主机名称不能为空！");
		return false;
	}
	$.ajax({
		url: '__APP__/Index/add',
		dataType: 'json',
		data: { ip: ip, type: type, zone: zone, local: local, desc: desc, status: status },
		type: "POST",
		success: function (data) {
			if (data.result == 1) {
			      alert(data.reason + data.id);
			      $(evt).parent().parent().remove();
			      $("#node-list").append("<tr>"+
				  "<td><input type='hidden' value='"+data.id+"'/><input type='checkbox' style='wdith:22px;' value='none'/></td>"+
				  "<td><input type='text' onFocus=\"inputFocus(this);\" class='tc' style='width:100px;' value='"+ip+"' /></td>"+
				  "<td><input type='text' onFocus=\"inputFocus(this);\" class='tc' style='width:60px;' value='"+type+"' /></td>"+
				  "<td><input type='text' onFocus=\"inputFocus(this);\" class='tc' style='width:60px;' value='"+zone+"' /></td>"+
				  "<td><input type='text' onFocus=\"inputFocus(this);\" class='tc' style='width:60px;' value='"+local+"' /></td>"+
				  "<td><input type='text' onFocus=\"inputFocus(this);\" class='tc' style='width:100%;' value='"+desc+"' /></td>"+
				  "<td class='select'><input onFocus='inputFocus(this);' type='text' class='tc' style='width:58px;' value='"+$(td7).children("select").val()+"'/><input type='hidden' value='"+status+"'/></td>"+
				  "<td><a href=\"javascript:void(0);\" onclick=\"deleteNode(this);\">删除</a> | <a href=\"javascript:void(0);\" onclick=\"readNode(this);\">查看属性</a></td>"+
				  "</tr>");
			}
			else{
			      alert(data.reason);
			}
		},
		error: function (data) {
		      alert(data.statusText);
		}
	});
}
function cancelNode(evt){
	$(evt).parent().parent().remove();
}

function deleteNode(evt){
	var td0 = $(evt).parent().parent().find("td").get(0);
	var td1 = $(evt).parent().parent().find("td").get(1);
	var id = $(td0).children("input[type=hidden]").val();
	var ip = $(td1).children("input[type=text]").val();
	if(confirm("确定删除主机："+ ip + "吗？")){
	      $.ajax({
		      url: '__APP__/Index/delete',
		      dataType: 'json',
		      data: { id: id },
		      type: "POST",
		      success: function (data) {
			      if (data.result == 1) {
				      alert(data.reason);
				      $(evt).parent().parent().remove();
			      }
			      else{
				      alert(data.reason);
			      }
		      },
		      error: function (data) {
			      alert(data.statusText);
		      }
	      });
	}
}

function save(evt){
	var td0 = $(evt).parent().parent().find("td").get(0);
	var td1 = $(evt).parent().parent().find("td").get(1);
	var td2 = $(evt).parent().parent().find("td").get(2);
	var td3 = $(evt).parent().parent().find("td").get(3);
	var td4 = $(evt).parent().parent().find("td").get(4);
	var td5 = $(evt).parent().parent().find("td").get(5);
	var td7 = $(evt).parent().parent().find("td").get(7);
	
	var id = $(td0).children("input[type=hidden]").val();
	var ip = $(td1).children("input[type=text]").val();
	var type = $(td2).children("input[type=text]").val();
	var zone = $(td3).children("input[type=text]").val();
	var local = $(td4).children("input[type=text]").val();
	var desc = $(td5).children("input[type=text]").val();
	var status = $(td7).children("select").val() == "可用"?"true":"false";
	//alert(status);return false;
	if(!ip){
		alert("错误提示：主机名称不能为空！");
		return false;
	}
	$.ajax({
		url: '__APP__/Index/edit',
		dataType: 'json',
		data: { id: id, ip: ip, type: type, zone: zone, local: local, desc: desc, status: status },
		type: "POST",
		success: function (data) {
			if (data.result == 1) {
				alert(data.reason);
				$(td7).children("input[type=hidden]").val(status);
				cancel(evt);
			}
			else{
				alert(data.reason);
			}
		},
		error: function (data) {
			alert(data.statusText);
		}
	});
}

	//这种导航效果相当于Tab切换，用titCell和mainCell 处理
	jQuery(".navBar").slide({ 
		titCell:".nav .m", // 鼠标触发对象
		mainCell:".subNav", // 切换对象包裹层
		delayTime:0, // 效果时间
		triggerTime:0, //鼠标延迟触发时间
		returnDefault:true  //返回默认状态
	});
</script> 
<script type="text/javascript">
	//退出系统
	function logout(){
		if(confirm("确定退出系统吗？")){
			$.ajax({
				  url: '__APP__/Index/logout',
				  dataType: 'json',
				  type: "POST",
				  success: function (data) {
					  if (data && data.result == 1) {
						  window.top.location = '__APP__/Index/login';
					  }
				  },
				  error: function (data) {
					  alert(data.statusText);
				  }
			});
		}
	}
</script>
</body>
</html>
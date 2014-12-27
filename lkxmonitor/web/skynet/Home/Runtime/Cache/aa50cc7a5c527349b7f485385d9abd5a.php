<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Language" content="zh-CN">
<meta name="Keywords" content="SkyNet">
<meta name="Description" content="SkyNet服务器管理平台">
<title>SkyNet服务器管理平台</title>
<link type="text/css" rel="stylesheet" href="__ROOT__/Public/css/style.css">
<script type="text/javascript" src="__ROOT__/Public/js/jquery-1.8.1.min.js"></script>
<!-- Le HTML5 shim, for IE6-8 support of HTML elements -->
<!--[if lt IE 9]>
    <script src="__ROOT__/Public/js/html5.js"></script>
<![endif]-->
<script type="text/javascript">
	//如果是内部页，父级页面刷新,登录
	if(window.top!=this){ 
		parent.location.reload();
	}
	
	function login(){
		var name = $("#u").val(), pwd = $("#p").val();
		if(!name){
			$("#msg").html('输入用户名！');
			$("#u").focus();
			return false;
		}
		if(!pwd){
			$("#msg").html('输入密码！');
			$("#p").focus();
			return false;
		}
		$("#msg").html('验证中...');
		$.ajax(
		{
			url: '__APP__/Index/login',
			dataType: 'json',
			data: { u: name, p: pwd },
			type: "POST",
			success: function (data) {
				if (data.result == 1) {
					window.top.location = "__APP__/Index/index";
				}
				else{
					$("#msg").html(data.reason);
				}
			},
			error: function (data) {
				alert(data.statusText);
			}
		});
	}
</script>
</head>
<body>
	<div class="loginbox">
		<h1 style="font:17px 黑体">SKYNET服务器管理平台</h1>
		<hr size="1" style="color:#3CADED;border-style:dotted;">
		<ul>
			<li><span>用户名：</span><input id="u" class="on" placeholder="输入用户名" type="text" style="width:130px;" /></li>
			<li><span>密&nbsp;&nbsp;&nbsp;码：</span><input id="p" placeholder="输入密码" class="on" type="password" style="width:130px;" /></li>
		</ul>
		<div class="memu_bar" style="text-align:right;margin-bottom:20px;">
			<ul>
				<li id="msg" style="width:120px;"></li>
				<li style="margin-left:0px;"><button id="submit_btn" onclick="login();">登&nbsp;&nbsp;&nbsp;陆</button></li>
			</ul>
		</div>
	</div>
</body>
<!--初始焦点-->
<script type="text/javascript">
		$("#u").focus();
		document.onkeydown=function mykeyDown(e){
		   e = e||event;
		   if(e.keyCode == 13) { $("#submit_btn").click(); } 
		}
</script>
</html>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7">
<link rel="shortcut icon" href="http://myview.chinanetcenter.com/images/favicon.ico">
<link rel="bookmark" href="http://myview.chinanetcenter.com/images/favicon.ico">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"> 
<meta http-equiv="Pragma" content="no-cache"> 
<meta http-equiv="Expires" content="0">


<script>var conf={"context":""},jsver=3;</script>

<script type="text/javascript" src="../js/zh_CN.js"></script>
<script type="text/javascript" src="../js/import.js"></script>
<script type="text/javascript" src="../js/jquery-1.7.2.js"></script>
<link rel="stylesheet" href="../style/main.css">
<link rel="stylesheet" href="../css/main.css" />
<script src="../js/tree.js" type="text/javascript"></script>
<script type="text/javascript" src="../js/common.js"></script>
<script src="../js/base.msgconf.js" type="text/javascript"></script>

<script>//<![CDATA[
$import("../style/jquery-ui-1.8.6.custom.css","css","theme");
$import("../style/jquery.multiselect.css","css");
$import("../js/common-all.js");
//]]></script>

<style>.dynamicform{margin:0 10px 5px;text-align:left}.dynamicform ul{margin:10px 0 20px 0}.dynamicform li{padding:5px;overflow:hidden;display:block;*display:inline-block}.dynamicform li label{float:left;width:110px;text-align:right;padding-right:5px}.dynamicform li span{float:left;width:35%}.dynamicform li span label.invalid{display:block;color:red;text-align:left;width:97%}.dynamicform div.required{float:left;width:10px;color:red}.dynamicform div.note{float:left;display:inline;padding-left:5px;width:40%}.dynamicform li span.textarea .note{width:500px}.dynamicform ul input,.dynamicform select{width:97%}.dynamicform textarea{width:500px}.dynamicform li span.choose input{width:20px}.dynamicform li span.choose label{float:none}.dynamicform li.active{background:#fff7c0}.dynamicform .btn{padding:5px;overflow:hidden}.dynamicform .submit{text-align:right;width:30%;float:left;padding-right:30px}.dynamicform input.button{cursor:pointer}.dynamicform .dyngroup{background:#d7f9fe;border:1px solid #a6c8ce;border-radius:3px;color:#00627b;font-size:12px;font-weight:bold;line-height:30px;padding:0 0 0 10px}label.invalid{color:red}</style>

<title>睿江Portal—信息维护</title>

</head>
<body>
<!-- 头部开始 -->
<?php require_once('../inc/head.inc.php'); ?> 
<!-- 导航结束 -->
<!-- 内容开始 -->
<div id="main">
		<div class="main_left">
			<!--左导航开始--> 
				<?php require_once('../inc/menu.inc.php'); ?>
				<script>jQuery(function(){flexMenu('box')})</script>
      <!--左导航结束-->
      </div>
		<div class="main_right">
		<div class="main_right_top">
			<div class="main_left_top_title" style="width:200px;">修改账号信息</div>
		</div>
		<div id="content" class="main_right_middle">
		<div class="tab">
			<ul id="tabUl">
				<li id="YKTabMu_10" class="YKTabMuLi" onclick="Show_YKTab(1,0);">修改信息</li>
				<li id="YKTabMu_11" class="YKTabMuLi" onclick="Show_YKTab(1,1);">修改登录密码</li>
			<!--	<li id="YKTabMu_12" class="YKTabMuLi" onclick="Show_YKTab(1,2);">修改推送密码</li>-->
				
			</ul>
		</div>
		<div id="YKTabCon_10">
		<form id="fieldform" class="dynamicform" >
			
				
			<div class="dyngroup">基础信息</div>
				<ul>
	    				
			    	<input type="hidden" name="account.accountId" value="57656">
   				
				<li>
							<label>登录名</label>
							<span>
								<input type="text" name="user" id="user" class="user_input {required:true,letterdigit:true}" disabled=true>
							</span>
							
							<div class="required"><em>&nbsp;</em></div>
							<!--<div class="note">不许修改</div>-->
					
					
				</li>
				
    		    				
				<li>
					<label>公司</label>
					<span>
						<input type="text" name="name" id="name" class="user_input {required:true,letterdigit:true}" disabled=true>
					</span>
					<div class="required"><em>*</em></div>
				</li>
				
    		    		
			<li>
		<label>联系人</label>
		<span>
			<input type="text" name="conn" id="conn" class="user_input" >
								</span>
		<div class="required"><em>&nbsp;</em></div>
			</li>
  				
    		    				
    		    				
				<li>
					<label>电子邮件</label>
					<span>
						<input name="email" id="email" class="user_input {required:true,email:true}">
					</span>
					<div class="required"><em></em></div>
				</li>
				
		
				
    		    					<li>
		<label>电话</label>
		<span>
			<input type="text" name="tel" id="tel" class="user_input  " >
								</span>
		<div class="required"><em>&nbsp;</em></div>
			</li>
    		    				  				
    		    				
    	
    		    				
    		    					<li>
		<label>备注</label>
		<span class="textarea">
            <textarea name="desc" id="desc" class="user_text" ></textarea>
														</span>
	</li>
    		    				
    		</ul>

			
			<div align="center">
				<input class="button" type="button" onclick="msg_upd()" value="提交">  
				</div>
		</form>
		</div>

		<div id="YKTabCon_11">
			<form  class="dynamicform" >
			<div class="dyngroup">修改密码</div> 
					<span id="updInfo" style="color:red;padding-left:20px"></span>			
				<ul>
   				
				<li>
							<label>原密码</label>
							<span>
								<input type="password" name="oldPwd" id="oldPwd" class="user_input {required:true,letterdigit:true}" >
								
							</span>
							
							<span id="oldInfo" style="color:red"></span>
				</li>
    		    				
				<li>
					<label>新密码</label>
					<span>
						<input type="password" name="newPwd" id="newPwd" class="user_input {required:true}" >		
					</span>
					<span id="newInfo" style="color:red"></span>
				</li>
				
    		    				
				<li>
					<label>新密码确认</label>
					<span>
						<input type="password" name="confirmPwd" id="confirmPwd" class="user_input {required:true,email:true}">
					</span>
					<span id="confirmInfo" style="color:red"></span>
				</li>
				
    		</ul>

			
			<div align="center">
				<input class="button" type="button" onclick="msg_pwd_upd()" value="提交" />  
				</div>
				</form>
		</div>
	

	 		</div>
		<div class="main_right_bottom"></div>
	</div>
	<div class="clear"></div>
</div>

<!-- 内容结束 -->
<br>
<br>
<?php require_once('../inc/foot.inc.php'); ?>

</body>
</html>

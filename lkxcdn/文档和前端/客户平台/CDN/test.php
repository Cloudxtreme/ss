<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>用户登录</title>
<link rel="stylesheet" href="Login/CSS/Login.css?2012" type="text/css">
<script type="text/javascript" src="js/jquery-1.7.2.js"></script>
<script type="text/javascript" src="Login/JS/login.js"></script>
</head>

<body class="loginPageBg">

	<form>

		<div class="loginWinBg" >
			<span id="msg" style="font-size:13px;padding-left:220px;color:red"> <FONT color=#ff0000>
			</FONT> </span>
			 <input type="text" ID="UserCode" name="UserCode" MaxLength="25"
				Style="position: absolute; top: 215px; left: 139px;width:119px;" TabIndex="1" /> 
			<input type="password" ID="UserPwd" name="UserPwd"  MaxLength="25" 
				Style="position: absolute; top: 215px; left: 342px;width:127px" TabIndex="2" /> 			
			<input type="button" ID="ButtonLogin" value="登 录" onclick="login();" 
				Style="position: absolute; top: 252px; left: 343px;" TabIndex="3" Class="buttonLogin" /> 
			<input type="button" ID="ButtonExit" value="关 闭" Style="position: absolute; top: 252px; left: 421px;"
				TabIndex="4" Class="buttonLogin" onclick="window.opener=null;window.open('','_self');window.close();" />
				
				
			<input type="image" src="./code.php" Style="position: absolute; top: 256px; left: 86px; font-size: 13px;" TabIndex="3"/>		
		</div>
	</form>

	<!--初始焦点-->
	<script type="text/javascript">
		document.getElementById("UserCode").focus();

		document.onkeydown=function mykeyDown(e){
       e = e||event;
       if(e.keyCode == 13) { $("#ButtonLogin").click(); } 
		}
	</script>

</body>
</html>
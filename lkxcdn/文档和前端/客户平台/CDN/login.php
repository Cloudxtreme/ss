<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>用户登录</title>
<link rel="stylesheet" href="css/login.css" />
<script type="text/javascript" src="js/jquery-1.7.2.js"></script>
<script type="text/javascript" src="Login/JS/login.js"></script>
</head>

<body>

	<div class="loginbox">	
    	<ul>
        	<li><label for="name" class="name">用 户 名：</label><input type="text" ID="UserCode" name="UserCode" class="inputsize"/></li>
            <li>
              <label for="password" class="password">密 　 码：</label><input type="password" ID="UserPwd" name="UserPwd" class="inputsize"/></li>
            <li><label for="yzm" class="yzm">验 证 码：</label><input type="text" ID="randcode" name="randcode" class="inputsize2"/>
            <img src="./code.php" alt="点此刷新验证码" onclick="this.src='./code.php?'+Math.random();" style="cursor:pointer;" />
            	</li>
            <li>
            	<div>
        			<input type="button" value="登 录" id="ButtonLogin" class="loginbtn" onclick="login();" />
            		<input type="reset" value="重 置" class="resetbtn" />
        		</div>
            </li>
            <li>	
								<span id="msg" style="font-size:13px;padding-left:120px;color:red"><FONT color=#ff0000></FONT> </span>
            </li>
        </ul>   
        <p>
        	QQ:<span>2523801584</span><span class="line"> ｜ </span>电话:<span>18316490449、400-066-2212</span><span class="line"> ｜ </span>Email:<span>cdn@efly.cc</span>
        </p>
   </div>

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



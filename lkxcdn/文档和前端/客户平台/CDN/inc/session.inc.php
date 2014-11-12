<?php
		global $send_id;
		/*add by hyb @2013.07.03 处理跳转页面的session_id*/
		if ($send_id)
		{
			session_id($send_id);
		}
		session_start(); 
		/*
		if( isset($_SESSION["timeout"]) && (time(0) - $_SESSION["timeout"]) > 5)
		{
				echo "<script language='javascript'>alert('登陆超时,请重新登陆');</script>"; 
				echo "<script language='javascript'>window.location.href='/cdn/login.php';</script>"; 
				return;
		}
		*/
		if( $_SESSION["login_user"] ){ echo $_SESSION["login_user"]; }
		if( $_SESSION["login_user"] ){ echo '<input type="hidden" id="_login_type" value="'.$_SESSION["login_type"].'" />'; }
		//if( session_is_registered("login_type") ){ echo '<input type="hidden" id="_login_type" value="'.$_SESSION["login_type"].'" />'; }
		else{
				session_destroy(); 
				echo "<script language='javascript'>alert('登陆超时,请重新登陆');</script>"; 
				echo "<script language='javascript'>window.location.href='/cdn/login.php';</script>"; 
		};
?>
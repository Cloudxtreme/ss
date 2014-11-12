<?php
		session_start();
		if($_SESSION["login_type"]){
				echo $_SESSION["login_type"];
		}
?>
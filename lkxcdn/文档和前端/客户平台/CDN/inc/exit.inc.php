<?php
		session_start();
		unset( $_SESSION["login_user"] );
		echo '<script> window.location = "/cdn/login.php" </script>';
?>
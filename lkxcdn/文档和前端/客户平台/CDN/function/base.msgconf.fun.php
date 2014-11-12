<?php

	require_once('../common/mysql.com.php');
	require_once('./log.fun.php');
	$usr  = '';
	$pwd  = '';
	$newpwd  = '';

	if( !isset($_POST['get_type']) ) { exit; }
	if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { $usr = $_POST['user']; }
	if( isset($_POST['pwd']) && strlen($_POST['pwd']) > 0 ) { $pwd = $_POST['pwd']; }
	if( isset($_POST['newpwd']) && strlen($_POST['newpwd']) > 0 ) { $newpwd = $_POST['newpwd']; }
	
	switch( $_POST['get_type'] ) {
		
		case "_init" :
			print_r( query_user( $usr ) );
			break;
			
		case "_msg_upd":
			print_r( msg_upd( $usr  ));
			syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],$_POST['get_type'],null,null);
			break; 
			
		case "_msg_pwd_upd":
			echo msg_pwd_upd( $usr, $newpwd, $pwd );
			syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],$_POST['get_type'],null,null);
			break;  
			
		case "_msg_check_pwd":
			print_r( msg_check_pwd( $usr, $pwd ) );
			syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],$_POST['get_type'],null,null);
			break;     
			
		default :  
			exit; 
			break;
	}
	
	
	function query_user( $usr ){
		
			$mysql_class = new MySQL('ibss');
   		$mysql_class -> opendb("IBSS2014", "utf8");
   	//	$sql = "select * from `CDN_User` where  `User` = '$usr' ";
        $sql = "select c.Name as Name,a.CDN_User as User,a.Email as Email,a.CDN_pho as Phone,a.CDN_conn as CDNConn,a.`desc` as `Desc` from Ad_ContractCDN as a left join Ad_Contract as b on a.Contract_ID=b.ID left join Ad_Custom as c on b.Custom_ID=c.ID where  a.CDN_User = '$usr' ;";
   		$result = $mysql_class -> query( $sql );
   		
   		if ( $result ) { 
	   			while( ( $row = mysql_fetch_array($result) ) ){
		   				$user = $row['User'];
							$name = $row['Name'];
							$email = $row['Email'];
							$conn = $row['CDNConn'];
							$tel = $row['Phone'];
							$desc = $row['`Desc`'];
	   			}
   		}
   		
   		mysql_free_result($result);
   		
   		$res[] = array( 'user' => $user, 'name' => $name, 'email' => $email, 'conn' => $conn , 'tel' => $tel, 'desc' => $desc );
			
			return json_encode( $res );
	}
	
	function msg_upd( $usr ){
		
			$mysql_class = new MySQL('ibss');
   		$mysql_class -> opendb("IBSS2014", "utf8");
   		
   		$name = '';
			$email = '';
			$conn = '';
			$tel = '';
			$desc = '';
   		
   		if( isset($_POST['name']) && strlen($_POST['name']) > 0 ) { $name = $_POST['name']; }
   		if( isset($_POST['email']) && strlen($_POST['email']) > 0 ) { $email = $_POST['email']; }
   		if( isset($_POST['conn']) && strlen($_POST['conn']) > 0 ) { $conn = $_POST['conn']; }
   		if( isset($_POST['tel']) && strlen($_POST['tel']) > 0 ) { $tel = $_POST['tel']; }
   		if( isset($_POST['desc']) && strlen($_POST['desc']) > 0 ) { $desc = $_POST['desc']; }
			
   		
   		$sql = "update `Ad_ContractCDN` set  `desc` = '$desc', `Email` = '$email',`CDN_conn`='$conn',`CDN_pho`='$tel' where `CDN_User` = '$usr' ";
   
   		$result = $mysql_class -> query( $sql );
   		if( $result ){ return "true"; }
   		else{ return "error"; }
	}
	
	function msg_check_pwd( $usr, $pwd ){
		
			$mysql_class = new MySQL('ibss');
   		$mysql_class -> opendb("IBSS2014", "utf8");
			
   		$sql = "select count(*) from `Ad_ContractCDN` where `CDN_User` = '$usr' and `CDN_Passwd` = '".md5($pwd)."' ";
   
   		$result = $mysql_class -> query( $sql );
   		$row = mysql_fetch_row($result);

	    if( $row[0] == 1 ){ return "true"; }
	   	else{ return "false"; }
	}
	
	function msg_pwd_upd( $usr, $new_pwd, $old_pwd ){
		
			$mysql_class = new MySQL('ibss');
   		$mysql_class -> opendb("IBSS2014", "utf8");
			
   		$sql = "update `Ad_ContractCDN` set `CDN_Passwd` = '".md5($new_pwd)."'  where `CDN_User` = '$usr' and `CDN_Passwd` = '".md5($old_pwd)."' ";
   		$result = $mysql_class -> query( $sql );

	    if( $result ){ return "true"; }
	   	else{ return "false"; }
	}
	
?>
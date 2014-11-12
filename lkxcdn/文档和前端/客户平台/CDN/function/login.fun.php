<?php


	require_once('../common/mysql.com.php');
	session_start();
	$randcode = $_SESSION['randcode'];
	$input = $_POST['randcode'];
	session_write_close();
	//if( $randcode != $input ) { echo "2"; exit; }
	if(strncasecmp($randcode,$input,4) != 0 || strlen($input) != 4){ echo "2"; exit;}
	
	$mysql_class = new MySQL('ibss');
    $mysql_class -> opendb("IBSS2014", "utf8");
 //   $result = $mysql_class -> query("select count(*),`Name`,`User`,`Type`,`IsAction` from Ad_ContractCDN where `User`='".$_POST["user"]."' and `Pass`='".md5($_POST["pwd"])."'");
    $result = $mysql_class -> query("select count(*),c.Name,a.CDN_User,a.CDN_Type,a.CDN_Status from Ad_ContractCDN as a left join Ad_Contract as b on a.Contract_ID=b.ID left join Ad_Custom as c on b.Custom_ID=c.ID where a.CDN_User='".$_POST["user"]."' and a.CDN_Passwd='".md5($_POST["pwd"])."'");
    $row = mysql_fetch_row($result);

    if( $row[0] == 1 && $row[4] == 1)
	{
		date_default_timezone_set('PRC');
		$login_time = date('Y-m-d H:i:s');
		$login_ip = get_client_ip();
//		$result = $mysql_class -> query("update CDN_User set LastLoginTime ='".$login_time."',LastLoginIP ='".$login_ip."' where User='".$_POST["user"]."' and Pass='".md5($_POST["pwd"])."'");
		$result = $mysql_class -> query("update Ad_ContractCDN set LastLoginTime ='".$login_time."',LastLoginIP ='".$login_ip."' where CDN_User='".$_POST["user"]."' and CDN_Passwd='".md5($_POST["pwd"])."'");
		   			
		if($result)
		{
			session_start();
			$_SESSION["login_user"] = $row[1].' '.$row[2].', ';
			$_SESSION["login_type"] = $row[3];

			echo 'ok';
			return;
		}
   }
    
   // mysql_free_result($result);
    echo "0";
     
function get_client_ip(){
	$user_IP = !empty( $_SERVER['HTTP_CLIENT_IP'] ) ? $_SERVER['HTTP_CLIENT_IP'] :
				( !empty($_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] :
				( !empty($_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unknown' ) );

	return $user_IP;
}
      
?>
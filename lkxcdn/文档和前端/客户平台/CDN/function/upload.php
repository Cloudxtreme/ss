<?php 
define('MAX_SIZE_FILE_UPLOAD',10000000); //文件大小不超过1MB
define('FILE_UPLOAD_LOCAL_DIR', '/var/www/html/cdn/download/'); //上传文件的目录
define('FILE_UPLOAD_DIR', 'http://portal.cdn.efly.cc/cdn/download/'); //上传文件的目录

require_once('../common/mysql.com.php');

echo '处理中，请稍候。。'; 
$message = '';
$catch_type = ( isset($_POST['catchtype']) ) ? $_POST['catchtype'] : '';

session_start();
if(!$_SESSION["login_user"]){
	 $message = 4;
	 echo "<script>window.location.href='/cdn/base/url.php?msg=$message'</script>";
	 exit;
}

if( $catch_type == '' || (isset($_POST['uploadfile']))){
	 $message = 1;
	 echo "<script>window.location.href='/cdn/base/url.php?msg=$message'</script>";
	 exit;
}

date_default_timezone_set('PRC');
$upload_time = date('YmdHis');

//	chmod( FILE_UPLOAD_DIR , 0777);
if( $_FILES['uploadfile']['size'] >= 10 ){
		if($_FILES['uploadfile']['size'] < MAX_SIZE_FILE_UPLOAD ){
		    $file_name = "(" . $catch_type . ")" . $upload_time . "_" . $_FILES['uploadfile']['name']; 
				$_FILES['uploadfile']['name'] = $file_name;
				move_uploaded_file( $_FILES['uploadfile']['tmp_name'] , FILE_UPLOAD_LOCAL_DIR . $file_name );
				
				$mysql_class = new MySQL('cdnmgr');
   		  $mysql_class -> opendb("cdn_web", "utf8");
   		  
   		  $session_array = array();
   		  $session_array = explode(' ' ,$_SESSION["login_user"]);
   		  
   		  $sql = "insert into `web_cache_mgr`(`url`,`url_type`,`owner`,`type`,`status`) values('" . FILE_UPLOAD_DIR . $file_name . "','multi','" . substr($session_array[1],0,strlen($session_array[1])-1) . "','$catch_type','ready') ON DUPLICATE KEY UPDATE `url_type`='multi', `owner`='" . substr($session_array[1],0,strlen($session_array[1])-1) . "',`type`='$catch_type',`status`='ready', `finish_time`=NULL ";
        $result = $mysql_class -> query( $sql );  
        
				$message = 2; 
		}
		else{ $message = 3; }
}

echo "<script>window.location.href='/cdn/base/url.php?msg=$message'</script>";

?>

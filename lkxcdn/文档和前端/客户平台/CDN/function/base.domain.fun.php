<?php

require_once('../common/mysql.com.php');
require_once('./log.fun.php');

$usr = '';
$domain = '';
$id = '';

if( !isset($_POST['get_type']) ) { exit; }
if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { $usr = $_POST['user']; }
if( isset($_POST['domain']) && strlen($_POST['domain']) > 0 ) { $domain = $_POST['domain']; }
if( isset($_POST['id']) && strlen($_POST['id']) > 0 ) { $id = $_POST['id']; }

switch( $_POST['get_type'] ) {
	
	case "_init" :
		print_r( domain_query( $usr ) );
		break;
		
	case "_domain_add":
		echo domain_add( $domain, $usr );
		syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],null,null,null);
		break;  
		
	case "_domain_del":
		echo domain_del( $id );
		syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],null,null,null);
		break;     
		
	default :  
		exit; 
		break;
}


function domain_query( $usr ){

$mysql_class = new MySQL('cdninfo');
$mysql_class -> opendb("cdn_file", "utf8");
$res_arr = array();
$sql = "select * from `user_hostname` where  `owner` = '$usr' and `status` = 'true'; ";
$result = $mysql_class -> query( $sql );

if($result){
	if(count($result) == 0){ $res_arr[] = array('id' => '-', 'hostname' => '-'); }
	
	while( ($row = mysql_fetch_array($result)) ) {
		$id = $row['id'];
		$hostname = $row['hostname'];
		$hosttype = $row['type'];
		
		$res_arr[] = array('id' => $id, 'hostname' => $hostname, 'type' => $hosttype);
  }

mysql_free_result($result);	

}

return json_encode($res_arr);

}

function domain_add( $domain, $usr ){

$mysql_class = new MySQL('cdninfo');
$mysql_class -> opendb('cdn_file', "utf8");

$sql = "insert into `user_hostname`(`hostname`,`status`,`owner`)values('$domain','true','$usr');";

$result = $mysql_class -> query( $sql );
if( $result ){ return 'succ'; }

return 'error';

}

function domain_del( $id ){

$mysql_class = new MySQL('cdninfo');
$mysql_class -> opendb('cdn_file', "utf8");

$sql = "delete from `user_hostname`  where id = $id";

$result = $mysql_class -> query( $sql );
if( $result ){ return 'succ'; }

return 'error';

}

?>

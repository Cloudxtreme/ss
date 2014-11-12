<?php

	require_once('../common/mysql.com.php');
	require_once('./log.fun.php');
	
	$usr  = '';
	$url  = '';
	$type = '';
	$url_array = array();
	
	if( ! isset($_POST['get_type']) ) { exit; }
	if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { $usr = $_POST['user']; }
	if( isset($_POST['url']) && strlen($_POST['url']) > 0 ) { $url = $_POST['url']; }
  if( isset($_POST['type']) && strlen($_POST['type']) > 0 ) { $type = $_POST['type']; }
  
	syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],null,null,null);
	switch( $_POST['get_type'] ) {
		
		case "_clear_catch" :
			$url_array = explode("\n", $url );
		    echo clear_catch( $url_array, $usr, 'update', 'single' );
			break;
			
		case "_clear_path_catch":
			$url_array = explode("\n", $url );
			print_r(clear_catch( $url_array, $usr, 'clean', 'path' ));
			break; 
			
		case "_clear_record":
			print_r(clear_record( $usr ));
			break;  
			
		case "_query":
			print_r(query_catch( $usr, $type ));
			break;     
			
		default :  
			exit; 
			break;
	}
	
	
	
	function clear_catch( $url_array , $usr , $type, $url_type ){
		
			$mysql_class = new MySQL('cdnmgr');
   		$mysql_class -> opendb("cdn_web", "utf8");
   		$sql = web_catch_mgr_ins_sql( $url_array , $usr , $type , $url_type);
   
   		$result = $mysql_class -> query( $sql );
   //  return $sql;
  if ( $result ) { return 'true'; }
   		else { return 'false'; }  
	} 
	
	function query_catch( $usr , $type ){
		
			$mysql_class = new MySQL('cdnmgr');
   		$mysql_class -> opendb("cdn_web", "utf8");
   		$_res = array();
   		$sql = "select `url`,`owner`,`start_time`,`finish_time`,`status`,`type` from `web_cache_mgr` where  `owner` = '$usr' ";
   		if( $type != 'all'){ $sql .= " and `type`= '$type' "; }
   		$sql .= " order by `start_time` desc ;";
   		$result = $mysql_class -> query( $sql );
   		
   		if($result){
	   		if(count($result) == 0){ $_res[] = array('url' => '-', 'start_time' => '-', 'finish_time' => '-', 'status' => '-', 'type' => '-'); }
	   		
	   		while( ($row = mysql_fetch_array($result)) ) 
				{
					$url = $row['url'];
					$start_time = $row['start_time'];
					$finish_time = $row['finish_time'];
					$status = $row['status'];
					$type = $row['type'];
					
					switch ($status)
					{
						case 'clean':
							$_status = "清除";
							break;
						case 'push':
							$_status = "推送";
							break;
						case 'update':
							$_status = "更新";
							break;
						case 'finish':
							$_status = "执行完成";
							break;
						case 'ready':
							$_status = "准备执行";
							break;
						default:
							$_status = "正在执行";
							break;
					}
					
					$_res[] = array('url' => $url, 'start_time' => $start_time, 'finish_time' => $finish_time, 'status' => $_status, 'type' => $type);
			}
			
			mysql_free_result($result);	
		}
			
			return json_encode($_res);
	 }
	 
	 function clear_record( $usr ){
		
			$mysql_class = new MySQL('cdnmgr');
   		$mysql_class -> opendb("cdn_web", "utf8");
   		$sql = "delete from `web_cache_mgr` where `status`='finish' and `owner` ='" .$usr. "';";
   
   		$result = $mysql_class -> query( $sql );
   		
  	if ( $result ) { return 'true'; }
   		else { return 'false'; } 
	}
	
	function web_catch_mgr_ins_sql( $url_array , $usr , $type, $url_type ){
			$sql = "insert into `web_cache_mgr`(`url`,`url_type`,`owner`,`type`,`status`) values";
		  foreach( $url_array as $url ){
				$sql .= "('" . $url . "','". $url_type ."','" . $usr . "','". $type ."','ready'),";
			}
		$ns=strlen($sql)-1;
		$sql=substr($sql,0,$ns);
		$sql .=" ON DUPLICATE KEY UPDATE `url_type`='single', `owner`='" . $usr . "',`type`='$type',`status`='ready', `finish_time`=NULL;";	
		return $sql;
	}
	
?>

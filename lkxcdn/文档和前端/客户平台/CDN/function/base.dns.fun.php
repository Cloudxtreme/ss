<?php

require_once('../common/mysql.com.php');
require_once('./log.fun.php');

//print_r($_POST);
//exit;

$usr = '';
$tablename = '';
$domain = '';
$ttl = ''; 
$ip = '';
$id = '';
$type = '';

if( !isset($_POST['get_type']) ) { exit; }
if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) { $usr =  $_POST['user']; }
if( isset($_POST['domainname']) && strlen($_POST['domainname']) > 0 ) { $channel = $_POST['domainname']; }
if( isset($_POST['nettype']) && strlen($_POST['nettype']) > 0 ) { $nettype = $_POST['nettype']; }

if( isset($_POST['tablename']) && strlen($_POST['tablename']) > 0 ) { $tablename = $_POST['tablename']; }
if( isset($_POST['domain']) && strlen($_POST['domain']) > 0 ) { $domain = $_POST['domain']; }
if( isset($_POST['ttl']) && strlen($_POST['ttl']) > 0 ) { $ttl = $_POST['ttl']; }
if( isset($_POST['ip']) && strlen($_POST['ip']) > 0 ) { $ip = $_POST['ip']; }
if( isset($_POST['id']) && strlen($_POST['id']) > 0 ) { $id = $_POST['id']; }
if( isset($_POST['type']) && strlen($_POST['type']) > 0 ) { $type = $_POST['type']; }

switch( $_POST['get_type'] ) 
{
	
	case "_init" :
		print_r( dns_query( $usr ) );
		break;
		
	case "_select":
		print_r(dns_select($usr, $nettype, $channel));
		syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],null,null,null);
		break;
		
	case "_dns_upd":
		echo dns_upd( $tablename, $domain, $ttl, $ip, $id, $type );
		syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],null,null,null);
		break; 
		
	case "_dns_add":
		echo dns_add( $tablename, $domain, $ttl, $ip, $type );
		syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],null,null,null);
		break;  
		
	case "_dns_del":
		echo dns_del( $tablename, $id, $type );
		syslog_user_action($_POST['user'],$_SERVER['SCRIPT_NAME'],null,null,null);
		break;     
		
	default :  
		exit; 
		break;
}



function dns_select( $usr ,$nettype, $channel){

$mysql_class = new MySQL('cdninfo');
$mysql_class -> opendb("cdn_web", "utf8");
$sql = "SELECT distinct(`domainname`), `tablename` FROM `user_hostname` where `owner` = '$usr'  and status='true' ";

$result = $mysql_class -> query( $sql );

unset( $mysql_class );



$sql = '';
$cnc_result = '';
$ct_result = '';
$domainname_arr = array();
$dns_arr = array();
$result_arr = array();
$print_arr = array();
if ( $result ) { 
	  
	while( ( $row = mysql_fetch_array($result) ) )
	{
		$domainname = $row['domainname'];
		$tablename  = $row['tablename'];	
		$print_arr[] = array( 'domainname' => $domainname, 'tablename' => $tablename );
		//echo "domain:".$domainname."  ";
		//echo "channel:".$channel."  ";
		if ($tablename == $channel)
		{
			$domainname_arr[] = array( 'domainname' => $domainname, 'tablename' => $tablename );
			$sql .= "select *,'$tablename' as tablename  from `$tablename` where `rdtype` = 'A' and `name` not like 'ns.%';";	
		}
	}
}

print_r(json_encode($print_arr));
print_r('***');

$mysql_class = new MySQL('squiddns');
mysql_free_result($result);
$nettypes= json_decode($nettype, true);
if( $sql != '' ){
        for($i=0;$i<count($nettypes);$i++)
        {
	switch($nettypes[$i])
	{
	case 'cnc':
		$mysql_class -> opendb("squid_dns_cnc", "utf8");
		$cnc_result = $mysql_class -> query( $sql );
		if( $cnc_result )
		{ 
			while( $row = mysql_fetch_array( $cnc_result ) ){
				$dns_arr[] = array( 'domain' => $row['name'], 'ttl' => $row['ttl'], 'ip'=> $row['rdata'], 'type' => 'cnc', 'desc' => '网通', 'tablename' => $row['tablename'], 'id' => $row['id'] ); 	
			}
		}
		break;

	case 'ct':

		$mysql_class -> opendb("squid_dns_ct", "utf8");
		$ct_result = $mysql_class -> query( $sql );
		if( $ct_result )
		{
			while( $row = mysql_fetch_array( $ct_result )){
				$dns_arr[] = array( 'domain' => $row['name'], 'ttl' => $row['ttl'], 'ip' => $row['rdata'], 'type' => 'ct', 'desc' => '电信', 'tablename' => $row['tablename'], 'id' => $row['id'] );
			}
		}
		break;

	case 'mobile':

		$mysql_class -> opendb("squid_dns_mobile", "utf8");
		$mobile_result = $mysql_class -> query( $sql );
		if( $mobile_result )
		{
			while( $row = mysql_fetch_array( $mobile_result )){
				$dns_arr[] = array( 'domain' => $row['name'], 'ttl' => $row['ttl'], 'ip' => $row['rdata'], 'type' => 'mobile', 'desc' => '移动', 'tablename' => $row['tablename'], 'id' => $row['id'] );
			}
		}
		break;

	default;
		break;
	}
        }  
}
//print_r($dns_arr);


if( count( $dns_arr ) == 0){
	  foreach( $domainname_arr as $arr ){
        $result_arr[] = array( 'domainname' => $arr['domainname'], 'domain' => '-', 'ttl' => '-', 'ip' => '-', 'type' => '-', 'desc' => '-', 'id' => '-' );
    }
}
else{
    foreach( $domainname_arr as $arr ){
    	  $ifset = "no";
    	  $domain = $arr['domainname'];
        $table = $arr['tablename'];
        foreach( $dns_arr as $dns ){
            if( $dns['tablename'] == $table ){ 
                $result_arr[] = array( 'domainname' => $domain, 'tablename' => $dns['tablename'], 'domain' => $dns['domain'], 'ttl' => $dns['ttl'], 'ip' => $dns['ip'], 'type' => $dns['type'], 'desc' => $dns['desc'], 'id' => $dns['id'] ); 
                $ifset = "yes";
                continue;
            }
			/*
            if( $ifset == "no" ){
            	  $result_arr[] = array( 'domainname' => $domain, 'tablename' => '-', 'domain' => '-', 'ttl' => '-', 'ip' => '-', 'type' => '-', 'desc' => '-', 'id' => '-' ); 
            }  
			*/
        }
    }
}

sort($result_arr);

return json_encode( $result_arr );

}





function dns_query( $usr ){

$mysql_class = new MySQL('cdninfo');
$mysql_class -> opendb("cdn_web", "utf8");
$sql = "SELECT distinct(`domainname`), `tablename` FROM `user_hostname` where `owner` = '$usr'  and status='true' ";

$result = $mysql_class -> query( $sql );

unset( $mysql_class );
$mysql_class = new MySQL('squiddns');
$mysql_class -> opendb("squid_dns_cnc", "utf8");

$sql = '';
$cnc_result = '';
$ct_result = '';
$mobile_result = '';
$domainname_arr = array();
$dns_arr = array();
$result_arr = array();

if ( $result ) { 

    while( ( $row = mysql_fetch_array($result) ) ){
		    $domainname = $row['domainname'];
			  $tablename  = $row['tablename'];	
				
				$domainname_arr[] = array( 'domainname' => $domainname, 'tablename' => $tablename );
				
			  $sql .= "select *,'$tablename' as tablename  from `$tablename` where `rdtype` = 'A' and `name` not like 'ns.%'";	
			  $sql .= " union ";
	  }
}
$sql = substr($sql,0,-7);	
$sql.=";";
//print_r($sql);
print_r(json_encode($domainname_arr));
print_r('***');

mysql_free_result($result);
if( $sql != '' ){

    $cnc_result = $mysql_class -> query( $sql );
    if( $cnc_result ){ 
        while( $row = mysql_fetch_array( $cnc_result ) ){
   	        $dns_arr[] = array( 'domain' => $row['name'], 'ttl' => $row['ttl'], 'ip'=> $row['rdata'], 'type' => 'cnc', 'desc' => '网通', 'tablename' => $row['tablename'], 'id' => $row['id'] ); 	
   	    }
    }

    $mysql_class -> opendb("squid_dns_ct", "utf8");
    $ct_result = $mysql_class -> query( $sql );
    if( $ct_result ){
        while( $row = mysql_fetch_array( $ct_result )){
            $dns_arr[] = array( 'domain' => $row['name'], 'ttl' => $row['ttl'], 'ip' => $row['rdata'], 'type' => 'ct', 'desc' => '电信', 'tablename' => $row['tablename'], 'id' => $row['id'] );
        }
    }

    $mysql_class -> opendb("squid_dns_mobile", "utf8");
    $mobile_result = $mysql_class -> query( $sql );
    if( $mobile_result ){
        while( $row = mysql_fetch_array( $mobile_result )){
            $dns_arr[] = array( 'domain' => $row['name'], 'ttl' => $row['ttl'], 'ip' => $row['rdata'], 'type' => 'mobile', 'desc' => '移动', 'tablename' => $row['tablename'], 'id' => $row['id'] );
        }
    }

}
//print_r($dns_arr);


if( count( $dns_arr ) == 0){
	  foreach( $domainname_arr as $arr ){
        $result_arr[] = array( 'domainname' => $arr['domainname'], 'domain' => '-', 'ttl' => '-', 'ip' => '-', 'type' => '-', 'desc' => '-', 'id' => '-' );
    }
}
else{
    foreach( $domainname_arr as $arr ){
    	  $ifset = "no";
    	  $domain = $arr['domainname'];
        $table = $arr['tablename'];
        foreach( $dns_arr as $dns ){
            if( $dns['tablename'] == $table ){ 
                $result_arr[] = array( 'domainname' => $domain, 'tablename' => $dns['tablename'], 'domain' => $dns['domain'], 'ttl' => $dns['ttl'], 'ip' => $dns['ip'], 'type' => $dns['type'], 'desc' => $dns['desc'], 'id' => $dns['id'] ); 
                $ifset = "yes";
                continue;
            }
			/*
            if( $ifset == "no" ){
            	  $result_arr[] = array( 'domainname' => $domain, 'tablename' => '-', 'domain' => '-', 'ttl' => '-', 'ip' => '-', 'type' => '-', 'desc' => '-', 'id' => '-' ); 
            }  
			*/
        }
    }
}

sort($result_arr);

return json_encode( $result_arr );

}


function dns_add( $tablename, $domain, $ttl, $ip, $type ){

 $types= json_decode($type, true);
 //print_r( $types);
 $res='error';
 for($i=0;$i<count($types);$i++)
 {
   $db = "squid_dns_".$types[$i];
   $mysql_class = new MySQL('squiddns');
   $mysql_class -> opendb($db, "utf8");
   $sql = "insert into `$tablename` (`name`,`ttl`,`rdtype`,`rdata`)values('$domain',$ttl,'A','$ip')";   
   $result = $mysql_class -> query( $sql );
   if( $result )
   { 
       $res='succ';       
   }
   else
   {
      $res='error';
   }
    unset($mysql_class );
 }
    
   return $res;

}

function dns_upd( $tablename, $domain, $ttl, $ip, $id, $type ){

$db = "squid_dns_".$type;

$mysql_class = new MySQL('squiddns');
$mysql_class -> opendb($db, "utf8");

$sql = "update `$tablename` set `name` = '$domain' ,`ttl` = $ttl, `rdata` = '$ip' where id = $id";

$result = $mysql_class -> query( $sql );
if( $result ){ return 'succ'; }

return 'error';

}

function dns_del( $tablename, $id, $type ){

$db = "squid_dns_".$type;

$mysql_class = new MySQL('squiddns');
$mysql_class -> opendb($db, "utf8");

$sql = "delete from `$tablename`  where id = $id";

$result = $mysql_class -> query( $sql );
if( $result ){ return 'succ'; }

return 'error';

}



?>

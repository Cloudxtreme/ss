<?php

/*后台信息记录*/

error_reporting(E_ALL^E_NOTICE^E_WARNING);
date_default_timezone_set('Asia/Shanghai');

function syslog_user_action($client, $func, $channels, $begin_day, $end_day)
{
	$tar = "";
	openlog("cdn_user_action",0, LOG_LOCAL3);
	
	$tar = "[";
	if ( is_array($channels) )
	{
		foreach($channels as $channel)
		{
			$tar.= $channel;
			$tar.= ",";
		}
	}
	else
	{
		$tar.= "$channels";
	}
	
	if (strlen($tar) != 1)
	{
		$tar[strlen($tar)-1] = "]";
	}
	else
	{
		$tar.="]";
	}
	$date = date("Y/m/d H:i:s");
	$action = explode("/",$func);
	$i = count($action);
	$action = substr($action[$i-1],0,-4);
	$ip = get_client_ip();
	syslog(LOG_INFO, "[$date][$ip][$client][$action]($tar,[$begin_day~$end_day])");
	closelog();
}


function get_client_ip(){
	$user_IP = !empty( $_SERVER['HTTP_CLIENT_IP'] ) ? $_SERVER['HTTP_CLIENT_IP'] :
				( !empty($_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] :
				( !empty($_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unknown' ) );

	return $user_IP;
}
?>

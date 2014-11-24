<?php
ini_set('max_execution_time','0');
//process the adduser command
//listen on the 
require_once("/root/config/share/httpsqs_client.php");
require_once("user.php");
define("QUENE_NAME","pppoe_mgr_user_rec");
define("QUENE_SERVER","mid.pppoe.rightgo.net");
define("QUENE_PORT","12001");
define("SLEEP_TIME",10000);

$httpsqs = new httpsqs(QUENE_SERVER,QUENE_PORT);
while(1)
{
	$user_json = $result = $httpsqs->get(QUENE_NAME);
	if("HTTPSQS_GET_END" == $user_json)
	{
		usleep(SLEEP_TIME);
	}
	else
	{
		$user = new User($config->configs["center-db"]);
		$result = $user->getObject($user_json);
		if(!$result)
		{
			$user->updateManage(User::COMMAND_LIM,false);
			continue;
		}
		//print_r($user);
		$result = $user->handleCommand(User::COMMAND_REC);
		if($result)
		{
			$user->updateManage(User::COMMAND_REC,true);
		}
		else
		{
			$user->updateManage(User::COMMAND_REC,false);
		}
	}
}
?>



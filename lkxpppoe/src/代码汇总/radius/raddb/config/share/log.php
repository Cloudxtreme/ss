<?php

function mylog($head,$content)
{
	global $config;
	$logdir = $config->configs["log"]["logdir"];
	$logfile = $logdir."/".getToday();
	str_replace("//","/",$logfile);
	if(!is_dir($logdir))
	{
		echo "logdir[{$logdir}] must be  an existing directory!\n";
		return;
	}
	$str = getNowTime()." [{$head}]:{$content}\n";
	syslog(LOG_INFO,"[{$head}]:{$content}\n");
	file_put_contents($logfile,$str,FILE_APPEND);
}
?>

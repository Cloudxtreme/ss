<?php
date_default_timezone_set('Asia/Manila');
function getNowTime()
{
	return date( "Y-m-d H-i-s",time()).":";
}

function getToday()
{
	return date( "Y-m-d",time());
}

function getTimestamp()
{
	return time();
}

function getNextMonth()
{
	return date( "Y-m-d",time()+30*24*3600);
}
?>

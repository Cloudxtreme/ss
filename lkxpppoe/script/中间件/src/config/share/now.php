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
?>

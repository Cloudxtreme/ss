<?php
date_default_timezone_set('Asia/Manila');

$begin = today();
$end = today_pass(30);

unsetOverdue();

$users = getOverdue($begin,$end);
foreach($users as $user => $end)
{
	writeOverdue($user,$end);
}

function today()
{
	return date( "Y-m-d",time());
}

function today_pass($num)
{
	return date( "Y-m-d",time()+$num*24*3600);
}

function getOverdue($begin,$end)
{
	$users = array();
	$link = mysql_connect('127.0.0.1', 'root', 'rjkj@rjkj');
	mysql_select_db("radius", $link);
	$result = mysql_query("select username,end from userdate where exceed='false' and useup='false' and forbidden='false' and end>'{$begin}' and end<'{$end}'", $link);
	while ($row = mysql_fetch_array($result, MYSQL_NUM)) 
	{
        $users[$row[0]] = $row[1];
    }
	return $users;
}

function writeOverdue($username,$end)
{
	$link = mysql_connect('10.18.255.183', 'root', 'rjkj@rjkj');
	mysql_select_db("pppCenter", $link);
	mysql_query("insert into overdue(username,enddate,status) values('{$username}','{$end}','true') ON DUPLICATE KEY UPDATE enddate='{$end}',status='true';", $link);
}

function unsetOverdue()
{
	$link = mysql_connect('10.18.255.183', 'root', 'rjkj@rjkj');
	mysql_select_db("pppCenter", $link);
	mysql_query("update overdue set status='false';", $link);
}
?>
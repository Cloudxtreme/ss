<?php

require_once('./mysql.com.php');

function user_auth($user,$pwd)
{
	$mysql_class = new MySQL('ibss');
    $mysql_class -> opendb("IBSS_TEST", "utf8");
    $result = $mysql_class -> query("select count(*),`Name`,`User`,`Type`,`IsAction` from CDN_User where `User`='".$user."' and `Pass`='".$pwd."'");
	$row = mysql_fetch_row($result);
	if( $row[0] == 1 && $row[4] == 1)
	{
		return 0;
	}
	
	return -1;
} 


?>
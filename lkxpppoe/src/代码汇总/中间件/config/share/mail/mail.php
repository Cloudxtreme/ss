<?php

require("smtp.php");

function sendmail($title,$content)
{
	$smtpserver ="121.9.13.178";
	$smtpserverport =25;
	$smtpusermail = "hezx@efly.cc";
	$smtpemailto = "364691617@qq.com";
	$smtpuser = 'hezx@efly.cc';
	$smtppass = "hzx123456";
	$mailsubject = $title;
	$mailbody = "$content<p>";

	$mailtype = "HTML";

	$smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);
	$smtp->debug = false;
	$smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);

}
?>

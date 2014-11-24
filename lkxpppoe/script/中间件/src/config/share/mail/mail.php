<?php

require("smtp.php");

function sendmail($content)
{
	$smtpserver ="smtp.efly.cc";
	$smtpserverport =25;
	$smtpusermail = "hezx@efly.cc";
	$smtpemailto = "364691617@qq.com";
	$smtpuser = 'hezx@efly.cc';
	$smtppass = "hzx123456";
	$mailsubject = "pppoe中间件";
	$mailbody = "$content<p>";

	$mailtype = "HTML";

	$smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);
	$smtp->debug = false;
	$smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);

}
?>

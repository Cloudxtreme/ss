<?php

//db
$cdninfo_ip = "cdninfo.efly.cc";
$cdninfo_user = "root";
$cdninfo_pass = "rjkj@rjkj";
$cdninfo_file_database = "cdn_file";
$cdninfo_web_database = "cdn_web";

$cdnfile_ip = "fs.src.cdnfile.cdn.efly.cc";
$cdnfile_user = "root";
$cdnfile_pass = "rjkj@rjkj";
$cdnfile_database = "cdn_hot_file";

$cdnmgr_ip = "cdnmgr.efly.cc";
$cdnmgr_user = "root";
$cdnmgr_pass = "rjkj@rjkj";
$cdnmgr_file_database = "cdn_file";
$cdnmgr_web_database = "cdn_web";

$cdninfo_ip = $cdnmgr_ip;


//task
$task_start_limit = 100;

$web_cache_task_timeout = 600;
$web_cache_task_failreset = 1800;
$max_webcache_task_eachowner = 20;

$file_cache_minsize = 1000000;
$file_cache_tasklimit_eachowner = 100;
$file_cache_task_timeout = 300;
$file_cache_task_failreset = 1800;
$file_cache_ftp_host = "file.cdn.rightgo.net";

$file_md5_task_timeout = 300;
$file_md5_task_failreset = 1800;

$task_retry = 3;

$cdn_admin_user = "cdnadmincdn";
$cdn_admin_pass = "cdnadmincdn";

?>

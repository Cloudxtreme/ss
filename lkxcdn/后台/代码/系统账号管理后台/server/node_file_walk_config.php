<?php

$serverip = $_SERVER['REMOTE_ADDR'];
header("Content-type: text/plain");
echo "cdninfo.efly.cc /cdnmgr/cdn_file/cdn_node_filelist_post.php $serverip /opt/rsyncdata/cdn_file";	

?>

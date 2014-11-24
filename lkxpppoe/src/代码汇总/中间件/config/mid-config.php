<?php
require_once("/root/config/config.php");
define("MID_CONFIG_FILE","/root/config/mid-config.ini");

$config = new config;
$config->load(MID_CONFIG_FILE);
//print_r($config);
?>

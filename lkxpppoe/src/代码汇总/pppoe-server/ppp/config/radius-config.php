<?php
require_once("/etc/raddb/config/config.php");
define("RADIUS_CONFIG_FILE","/etc/raddb/config/radius-config.ini");

$config = new config;
$config->load(RADIUS_CONFIG_FILE);
//print_r($config);
?>

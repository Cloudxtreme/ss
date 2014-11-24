<?php
require_once("/etc/ppp/config/config.php");
define("PPPOE_CONFIG_FILE","/etc/ppp/config/pppoe-config.ini");

$config = new config;
$config->load(PPPOE_CONFIG_FILE);
//print_r($config);
?>

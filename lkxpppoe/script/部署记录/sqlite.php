<?php
$db = new SQLite3('/etc/ppp/pppoe.db');

$db->exec('CREATE TABLE pppoe (ppp STRING,pid STRING,ipaddr STRING,status STRING)');
?>

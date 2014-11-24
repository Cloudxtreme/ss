<?php
$eth=$argv[1];

system("/sbin/tc qdisc del dev {$eth} root");
system("/sbin/tc qdisc del dev {$eth} ingress");
?>
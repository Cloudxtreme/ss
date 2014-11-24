<?php
	var_dump(udpcheck("10.18.0.129",2222));
	function udpcheck($ipaddr)
	{
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    $msg = "Ping";
    $len = strlen($msg);
    socket_sendto($sock, $msg, $len, 0, $ipaddr, 1234);
    socket_close($sock);
	}
?>

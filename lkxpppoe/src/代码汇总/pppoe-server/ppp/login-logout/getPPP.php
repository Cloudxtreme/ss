<?php
function getPPPNetworks()
{
	
	$infos = array();
	$devfile = "/proc/net/dev";
	$fh = fopen( $devfile, 'r' );
	while($line = fgets( $fh ))
	{
		if(strpos($line,"ppp") != false)
		{
			if (preg_match( '/^(.*?):[ \t]*([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]*([0-9]+?)[ \t]+([0-9]+?)[ \t]*([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]+([0-9]+?)[ \t]*$/', $line, $found ))
	    {
	    	$eth = trim($found[1]);
	    	$infos[$eth] = array();
	    	$infos[$eth]["ReceiveByte"] = $found[2];
	    	$infos[$eth]["ReceivePackets"] = $found[3];
	    	
	    	$infos[$eth]["TransmitByte"] = $found[10];
	    	$infos[$eth]["TransmitPackets"] = $found[11];
	    }   
		}
	}
	return $infos;
}


?>

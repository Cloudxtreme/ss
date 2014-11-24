<?php
$dns = getDNS();
$dns_json = json_encode($dns);
print_r($dns_json);

function getDNS()
{
	$file="/etc/ppp/pppoe-server-options";
	
	$dns = array();
	
  $fh = fopen( $file, 'r' );
  while( $l = fgets( $fh ) )
  {
    if (preg_match( '/^ms-dns[ \t]*(.*?)$/', $l, $found ))
    {
    	$dns[] = $found[1];
    }   
  }//while
  fclose( $fh );
  return $dns;
}
?>

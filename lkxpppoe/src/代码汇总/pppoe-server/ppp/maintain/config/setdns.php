<?php
define("PPPOE_CONFIG_FILE","/etc/ppp/pppoe-server-options");

$dns_json = $argv[1];
$dns = json_decode($dns_json,true);
if($dns == null)
{
	echo "false";
	return;
}
$content = getWriteContent($dns);
setWriteContent($content);
echo "ok";
return;

function getWriteContent($dns)
{
	$file=PPPOE_CONFIG_FILE;
	$writeString="";
	
  $fh = fopen( $file, 'r' );
  while( $l = fgets( $fh ) )
  {
    if (preg_match( '/^ms-dns[ \t]*(.*?)$/', $l, $found ) || preg_match( '/^[ \t]*$/', $l))
    {
    	//delete
    }
    else
    {
    	$writeString.=$l;
    }  
  }//while
  fclose( $fh );
  
  foreach($dns as $d)
  {
  	$writeString.="ms-dns ".$d."\n";
  }
  return $writeString;
}

function setWriteContent($content)
{
	$file=PPPOE_CONFIG_FILE;
  $fh = fopen( $file, 'w' );
  fwrite( $fh, $content );
  fclose( $fh );
}
?>

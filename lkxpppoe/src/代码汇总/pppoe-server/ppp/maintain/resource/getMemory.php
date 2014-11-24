<?php
$mem = getMemFromProc();
print_r($mem);


function getMemFromProc()
{
	$mem = array();
	$filename = "/proc/meminfo";
	$fh = fopen( $filename, 'r' );
  while( $line = fgets( $fh ) )
  {
  	if (preg_match( '/^MemTotal:[ \t]+(.*?)[ \t]+kB$/', $line, $found ))
    {
    	$mem["MemTotal"] = $found[1];
    }
    
    if (preg_match( '/^MemFree:[ \t]+(.*?)[ \t]+kB$/', $line, $found ))
    {
    	$mem["MemFree"] = $found[1];
    }
    
    if (preg_match( '/^Buffers:[ \t]+(.*?)[ \t]+kB$/', $line, $found ))
    {
    	$mem["Buffers"] = $found[1];
    }
    
    if (preg_match( '/^Cached:[ \t]+(.*?)[ \t]+kB$/', $line, $found ))
    {
    	$mem["Cached"] = $found[1];
    }
    
    if (preg_match( '/^SwapCached:[ \t]+(.*?)[ \t]+kB$/', $line, $found ))
    {
    	$mem["SwapCached"] = $found[1];
    }
    
    if (preg_match( '/^Active:[ \t]+(.*?)[ \t]+kB$/', $line, $found ))
    {
    	$mem["Active"] = $found[1];
    }
    
    if (preg_match( '/^Inactive:[ \t]+(.*?)[ \t]+kB$/', $line, $found ))
    {
    	$mem["Inactive"] = $found[1];
    }
  }
  return $mem;
}

?>
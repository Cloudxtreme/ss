<?php
$cpus = getCPUFromProc();
print_r($cpus);


function getCPUFromProc()
{
	$cpus = array();
	$filename = "/proc/stat";
	$fh = fopen( $filename, 'r' );
  while( $line = fgets( $fh ) )
  {
  	if(strpos($line,"cpu") == -1)
  	{
  		continue;
  	}
  	
  	if (preg_match( '/^(cpu.*?)[ \t]+(.*?)[ \t]+(.*?)[ \t]+(.*?)[ \t]+(.*?)[ \t]+(.*?)[ \t]+(.*?)[ \t]+(.*?)[ \t]+(.*?)[ \t]+(.*?)[ \t]*$/', $line, $found ))
    {
    	$cpuname = trim($found[1]);
    	$cpus[$cpuname] = array();
    	$cpus[$cpuname]["user"] = trim($found[2]);
    	$cpus[$cpuname]["nice"] = trim($found[3]);
    	$cpus[$cpuname]["system"] = trim($found[4]);
    	$cpus[$cpuname]["idle"] = trim($found[5]);
    	$cpus[$cpuname]["iowait"] = trim($found[6]);
    	$cpus[$cpuname]["irq"] = trim($found[7]);
    	$cpus[$cpuname]["softirq"] = trim($found[8]);
    }   
  }
  return $cpus;
}

?>
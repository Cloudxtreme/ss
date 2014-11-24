<?php
$vlans = getVlansFromProc();
$pids = getAllPids();
$infos = getPidsFromProc($pids);
$result = getVlanInfos($vlans,$infos);
print_r($result);

function getVlansFromProc()
{
	$vlans = array();
	$vlanFilename = "/proc/net/vlan/config";
	$fh = fopen( $vlanFilename, 'r' );
  while( $line = fgets( $fh ) )
  {
  	if (preg_match( '/^(.*?)\|(.*?)\|[ \t]*em2$/', $line, $found ))
    {
    	$vlans[trim($found[1])] = trim($found[2]);
    }   
  }
  return $vlans;
}

function getPidsFromProc($pids)
{
	$infos = array();
	foreach($pids as $pid)
	{
		$file = "/proc/{$pid}/cmdline";
		$content = file_get_contents($file);
		if(strpos($content,"em2") != false && strpos($content,"pppd") === false)
		{
			$vlan = "";
			if (preg_match( '/^.*-I[ \t]*(.*?)-.*$/', $content, $found ))
	    {
	    	$vlan = trim($found[1]);
	    } 
	    
	    if($vlan == "")
	    {
	    	continue;
	    }
	    $infos[$vlan] = array();
	    
	    if (preg_match( '/^.*-L[ \t]*(.*?)-.*$/', $content, $found ))
	    {
	    	$infos[$vlan]["local"] = trim($found[1]);
	    } 
	    
	    if (preg_match( '/^.*-R[ \t]*(.*?)-.*$/', $content, $found ))
	    {
	    	$infos[$vlan]["remote"] = trim($found[1]);
	    } 
	    
	    if (preg_match( '/^.*-N[ \t]*(.*?)-.*$/', $content, $found ))
	    {
	    	$infos[$vlan]["number"] = trim($found[1]);
	    }   
		}
	}
	return $infos;
}

function getAllPids()
{
	$pids = array();
	$directory = "/proc";
	
	$handle = @opendir($directory) or die("Cannot open " . $directory);
	while($file = readdir($handle))
	{
		if (preg_match( '/^[0-9]+$/', $file))
    {
       $pids[] =  $file;
  	}
	}
	closedir($handle);
	return $pids;
}

function getVlanInfos($vlans,$pids)
{
	foreach($vlans as $valn=>$num)
	{
		if(empty($pids[$valn]))
		{
			$pids[$valn] = array();
		}
	}
	return $pids;
}
?>

<?php
$files = getLogs();
$files_json = json_encode($files);
print_r($files_json);


function getLogs()
{
	$dir="/var/log/pppoe_project";
	
	$files = array();
	
  if (is_dir($dir)) 
  {
    if ($dh = opendir($dir)) 
    {
        while (($file = readdir($dh)) !== false) 
        {
        	if(!is_dir($dir."/".$file))
        	{
        		$files[] = $file;
        	}
        }
        closedir($dh);
    }
	}
	return $files;
}
?>
<?php
	
Class config 
{
	public $configs;

	function load ($file) 
	{
		if (file_exists($file) == false) { return false; }

		$this->configs = parse_ini_file ($file, true);
	}

}

?>

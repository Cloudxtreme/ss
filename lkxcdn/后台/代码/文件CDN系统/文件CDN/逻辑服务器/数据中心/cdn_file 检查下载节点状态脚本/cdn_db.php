<?php
require_once('cdn_config.inc.php');

class DBObj
{
	public $link = null;
	
	function __construct() 
	{

	}

	function __destruct() 
	{
			if( $this->link && ! is_null($this->link) ) {
				mysql_close($this->link);		
			}
	}
	
	function conn()
	{
			global $global_databaseip, $global_databaseuser, $global_databasepwd;
  		$this->link = @mysql_pconnect($global_databaseip, $global_databaseuser, $global_databasepwd);
  		return $this->link;
	}

	function select_db($dbname)
	{
		return mysql_select_db($dbname, $this->link);
	}
	
	function query($string)
	{
		if( ! $this->link ) {
			return false;
		}
		return @mysql_query($string, $this->link);
	}
	
	function get_last_id()
	{
		return @mysql_insert_id($this->link);
	}
	
	function affected_rows()
	{
		return @mysql_affected_rows($this->link);
	}
	
	function error()
	{
		return mysql_error($this->link);
	}
}

?>

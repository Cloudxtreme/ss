<?php
//require_once('config.inc.php');

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
	
	function conn2($ip, $user, $pass)
	{
  		$this->link = @mysql_pconnect($ip, $user, $pass);
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
	
	function error()
	{
		return mysql_error($this->link);
	}
}

	function db_gethandle($db_ip, $db_user, $db_pass, $databasename)
	{
		$try = 0;
		$dbobj = new DBObj;
		while(!$dbobj->conn2($db_ip, $db_user, $db_pass)) 
		{
			$try++;
			//printf("conn error!\n");
			//print($dbobj->error());
			if($try > 3)
				return false;
			sleep(3);
		}
		$dbobj->query("set names utf8;");
		$dbobj->select_db($databasename);
		return $dbobj;
	}

	function db_query($dbobj, $query)
	{
		return $dbobj->query($query);	
	}

?>

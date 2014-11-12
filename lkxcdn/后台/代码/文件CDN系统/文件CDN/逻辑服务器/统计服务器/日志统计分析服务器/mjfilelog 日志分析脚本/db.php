<?php
require_once('config.inc.php');

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
		$dbobj = new DBObj;
		while( ! $dbobj->conn2($db_ip, $db_user, $db_pass) ) 
		{
			printf("conn error!\n");
			print($dbobj->error());
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
	
	function db_sql_exec($dbobj, $sql, $print_flag)
	{
		$ret = db_query($dbobj, $sql);
		if($print_flag)
		{
			print(date('Y-m-d H:i:s'));
			printf(" $sql ret:$ret\n\n");
		}
		return $ret;
	}

?>

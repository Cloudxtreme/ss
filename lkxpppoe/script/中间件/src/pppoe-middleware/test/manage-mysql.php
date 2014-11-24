<?php
class manage_mysql
{
	private static $instance;
	private $connection;
	const server = "192.168.22.135";
	const username = "root";
	const password = "rjkj@rjkj";

	private function __construct() 
	{
		$this->connection = mysql_connect(self::server,self::username,self::password);
		if (!$this->connection) 
		{
		    die('Could not connect: ' . mysql_error());
		}

	}

	public static function getInstance()
	{
		if (!isset(self::$instance) || !(self::$instance->connection)) 
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}

	public function useDB($db)
	{
		mysql_select_db($db,$this->connection ) or die ("Can\'t use ".$db.":" . mysql_error());
	}

	public function query($statement)
	{
		return 	mysql_query($statement,$this->connection);
	}
}
?>

<?php
define("DB_SERVER","center.pppoe.rightgo.net");
define("DB_USERNAME","root");
define("DB_PASSWORD","rjkj@rjkj");
define("DB_DB","pppCenter");

class mysqlconnection
{
	private $connection;
	
	private $server;
	private $username;
	private $password;

	function __construct($server,$username,$password)
	{
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
		$this->connect();
	}
	
	public function connect()
	{
		if($this->server == "")
		{
			throw new Exception("database server can no be null");
		}
		else
		{
			$this->connection = mysql_pconnect($this->server,$this->username,$this->password);
			if(!$this->connection)
			{
				throw new Exception('Could not connect to server '.$this->server.':'.mysql_error());
			}
		}
	}


	public function useDB($db)
	{
		$result = mysql_select_db($db,$this->connection ); 
		if(!$result)
		{
			throw new Exception("unable to use database ".$db.":".mysql_error());
		}
	}

	public function query($statement)
	{
		$result =  mysql_query($statement,$this->connection);
		if($result)
		{
			return $result;
		}
		else
		{
			throw new Exception("mysql query error:\n".$statement."\n".mysql_error());
		}
	}
}
?>

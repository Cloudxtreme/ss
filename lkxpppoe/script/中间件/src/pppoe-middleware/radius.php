<?php
require_once("/root/config/share/mysqlconnection.php");

class radius
{
	public $server;
	public $dbname;
	public $username;
	public $password;

	private $connection;

	function __construct($connection)
	{
		$this->connection = $connection;
	}


	//do nothing about the exception throw by mysql
	public function contructbyid($id)
	{
		$statement = "select serverip,dbname,username,password from radiusInfo where id={$id}";
		//echo $statement."\n";
		$result = $this->connection->query($statement);
			//set the attrubutes
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$this->server = $row["serverip"];
			$this->dbname = $row["dbname"];
			$this->username = $row["username"];
			$this->password = $row["password"];
			return true;
		}
		else
		{
			echo "radius id no found\n";
			return false;
		}
	}
}
?>

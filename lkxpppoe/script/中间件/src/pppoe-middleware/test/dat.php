<?php
//simulate the manager interface to send a command to the pppoe-server

include_once("httpsqs_client.php");
include_once("manage-mysql.php");

define("USERNAME","heliu");
define("PASSWORD","");
define("RADIUS_SERVER",1);
define("MEAL_ID",4);
define("QUENE_NAME","pppoe_mgr_user_dat");
define("QUENE_SERVER","192.168.22.199");
define("QUENE_PORT","12001");

class User
{
	public $username;
	public $password;
	public $radiusid;		//radius server ip
	public $mealid;

	function __construct($username,$password,$radiusid,$mealid)
	{
		$this->username = $username;
		$this->password = $password;
		$this->radiusid = $radiusid;
		$this->mealid = $mealid;
	}
}

$user = new User(USERNAME,PASSWORD,RADIUS_SERVER,MEAL_ID);
$user_json = json_encode($user);

$httpsqs = new httpsqs(QUENE_SERVER,QUENE_PORT);
$result = $httpsqs->put(QUENE_NAME,$user_json);
if($result)
{
	//echo "post successfully\n";
	$manage = manage_mysql::getInstance();
	$manage->useDB("pppCenter");
	$statement = "update client set statu='wait_dat' where user='".USERNAME."'";
	echo $statement."\n";
	$manage->query($statement);
}
else
{
	echo "post fail\n";
}
?>

<?php
//process the adduser command
//listen on the 
require_once("meal.php");
require_once("radius.php");
require_once("/root/config/mid-config.php");
require_once("mail/mail.php");
require_once("/root/config/share/now.php");
require_once("/root/config/share/log.php");
define("LOG_FILE","/var/log/mid-log.log");

class User
{
    public $username;
	public $password;
	public $radius;		//class radius
	public $mealid;

	private $message = "no message";

	private $startDate = -1;
	private $endDate = -1;

	public $center_db;

	public $deleteUsernaem = "";

	const COMMAND_ADD = "ADD_USER";
	const COMMAND_PWD = "CHANGE_PASSWORD";
	const COMMAND_DEL = "DELETE_USER";
	const COMMAND_FOR = "FORBIDDEN_USER";
	const COMMAND_REC = "RECOVER_USER";
	const COMMAND_LIM = "LIMIT_USER";
	const COMMAND_DAT = "DATE_USER";
	const COMMAND_KIL = "KILL_USER";
	const COMMAND_SIM = "SIMUL";


	const STATUS_WAIT_ADD = "wait_add";
	const STATUS_WAIT_PWD = "wait_pwd";
	const STATUS_WAIT_DEL = "wait_del";
	const STATUS_WAIT_LIM = "wait_lim";
	const STATUS_WAIT_DAT = "wait_dat";
	const STATUS_WAIT_KIL = "wait_kil";
	const STATUS_WAIT_SIM = "wait_sim";
	const STATUS_TRUE = "true";
	const STATUS_FALSE = "false";
	
	const STATEMENT_PASS = "PASS";

	const QUENE_PORT = 12001;
	const QUENE_NAME = "pppoe_mgr_user_kil";

	public function __construct($center_db)
	{
		$this->center_db = $center_db;
	}	

	public function getObject($json,$checkStatus=true)  //return bool
	{
		$object = json_decode($json);
		//print_r($object);
		if(!isset($object->username) || !isset($object->password) || !isset($object->radiusid) || !isset($object->mealid))
		{
			$this->message =  "json error\n";
			return false;
		}
		$this->username = $object->username;
		$this->password = $object->password;
		$radiusid = $object->radiusid;
		$this->mealid = $object->mealid;

		$manage = new mysqlconnection($this->center_db["server"],$this->center_db["username"],$this->center_db["password"]);
		$manage->useDB($this->center_db["db"]);

		$this->radius = new radius($manage);
		$result = $this->radius->contructbyid($radiusid);
		if(!$result)
		{
			$this->radius = null;
			$this->message = "get radius information error\n";
			return false;
		}
		else
		{
			$radius = new mysqlconnection($this->radius->server,$this->radius->username,$this->radius->password);
			//print_r($radius);
			if(!$radius->connectable)	
			{
				$this->message = "can not connect to radius server[{$this->radius->server}]\n";
				$this->radius = null;
				return false;
			}
		}
		
		$status = $this->getClientStatus();
                if($checkStatus &&( $status==="true" || $status==="false"))
                {
                        $head = "user.php - getObject";
                        $content = "receive bad command:user[{$this->username}],checkstatus[{$checkStatus}],status[$status]";
                        mylog($head,$content);
                        return false;
                }
		return true;
		//print_r($user);
	}
	
	private function getClientStatus()
	{
		$manage = new mysqlconnection($this->center_db["server"],$this->center_db["username"],$this->center_db["password"]);
		$manage->useDB($this->center_db["db"]);
		$statement = "select statu from client where user='{$this->username}'";
                $result = $manage->query($statement);
                if($row = mysql_fetch_array($result, MYSQL_ASSOC))
                {
                        return $row["statu"];
                }
                return null;
	}

	public function handleCommand($command)
	{
		$head = $command;
		$content = "username[{$this->username}],password[{$this->password}],radius[{$this->radius}],startDate[{$this->startDate}],endDate[{$this->endDate}],mealid[{$this->mealid}]";
		mylog($head,$content);

		if($this->radius == null)
		{	
			$this->message = "radius can be null\n";
			return false;
		}
		//call the procedure adduser
		$radius = new mysqlconnection($this->radius->server,$this->radius->username,$this->radius->password);
		$radius->useDB($this->radius->dbname);
		//$statement = "call adduser('".$this->username."','".$this->password."','".$this->group."')";
		$statement = null;

		switch($command)
		{
			case self::COMMAND_ADD :
				$statement = $this->addUser();
				break;
			case self::COMMAND_PWD:
				$statement = $this->changePassword();
				break;
			case self::COMMAND_DEL:
				$statement = $this->deleteUser();
				break;
			case self::COMMAND_FOR:
                                $statement = $this->forbiddenUser();
                                break;
			case self::COMMAND_REC:
				$statement = $this->recoverUser();
				break;

			case self::COMMAND_LIM:
				$statement = $this->limitUser();
				break;
			case self::COMMAND_DAT:
				$statement = $this->dateUser();
				break;
			case self::COMMAND_KIL:
				$statement = $this->killUser();
				break;
			case self::COMMAND_SIM:
				$statement = $this->simul();
				break;
	
			default:
				$this->message =  "user command error\n";
				return false;
		}

		if($statement == null)
		{
			return false;
		}
		else if($statement == self::STATEMENT_PASS)
		{
			return true;
		}
		$result = $radius->query($statement);
		return $result;
	}

	public function updateManage($command,$result)
	{
		//update the pppCenter database
		$message = "";
		if(!$result)
		{
			$message = $this->message;
			if($message == "")
			{
				$message = "no message";
			}
		}
		$status = $result;
		$manage = new mysqlconnection($this->center_db["server"],$this->center_db["username"],$this->center_db["password"]);
		$manage->useDB($this->center_db["db"]);
		$statement = null;

		if(!$status)
		{
			$content = "{$command} error <p>";
			$content .= "username:{$this->username} <p>";
			$content .= "password:{$this->password} <p>";
			$content .= "radius:{$this->radius->server} <p>";
			$content .= "mealid:{$this->mealid} <p>";
			$content .= "message:{$this->message} <p>";

			$head = $command;
			mylog($head,"send mail");
			sendmail($content);
		}
			
		switch($command)
		{
			case self::COMMAND_ADD :
				$statement = $this->addUser_result($status,$message);
				break;
			case self::COMMAND_PWD:
				$statement = $this->changePassword_result($status,$message);
				break;
			case self::COMMAND_DEL:
				$statement = $this->deleteUser_result($status,$message);
				break;

			case self::COMMAND_FOR:
                                $statement = $this->forbiddenUser_result($status,$message);
                                break;
			case self::COMMAND_REC:
				$statement = $this->recoverUser_result($status,$message);
				break;

			case self::COMMAND_LIM:
				$statement = $this->limitUser_result($status,$message);
				break;
			case self::COMMAND_DAT:
				$statement = $this->dateUser_result($status,$message);
				break;
			case self::COMMAND_KIL:
				$statement = $this->killUser_result($status,$message);
				break;			
			case self::COMMAND_SIM:
				$statement = $this->simul_result($status,$message);
				break;	
			default:
				$this->message =  "user command error\n";
				return false;
		}
		//$statement = "update client set statu='${status}' where user='{$this->username}'";
		if($statement == null)
		{
			return false;
		}

		$head = $command;
		mylog($head,$statement);

		return $manage->query($statement);
	}


	public function isExist($username) //return bool
	{
		$radius = new mysqlconnection($this->radius->server,$this->radius->username,$this->radius->password);
		if($radius == null)
		{
			return;
		}
		$radius->useDB($this->radius->dbname);
		$statement = "select username from userinfo where username='{$this->username}'";
		$result = $radius->query($statement);
		$num = mysql_num_rows($result);
		return $num;
	}


	public function isOnlime($username) //return bool
        {
                $radius = new mysqlconnection($this->radius->server,$this->radius->username,$this->radius->password);
                if($radius == null)
                {
                        return;
                }
                $radius->useDB($this->radius->dbname);
                $statement = "select server,pid from userinfo where username='{$this->username}' and server!='off'";
                $result = $radius->query($statement);
                $num = mysql_num_rows($result);
                return $num;
        }

	private function addUser()
	{	
		if($this->isExist($this->username))
		{
			$this->message =  "user {$this->username} exists\n";
			return null;
		}
		$statement = "call adduser('".$this->username."','".$this->password."')";
		return $statement;
	}

	private function changePassword()
	{
		if(!$this->isExist($this->username))
		{
			$this->message = "user {$this->username} does not exist\n";
			return null;
		}
		$statement = "update radcheck set value='{$this->password}' where username='{$this->username}' and attribute='User-Password'";
		return $statement;
	}

	private function deleteUser()
	{
		if($this->isOnlime($this->username))
		{
			$this->kill($this->username);
		}
		$time = getTimestamp();
		$this->deleteUsernaem = $this->username."-".$time;
		//$statement = "update `userdate` set `forbidden`='true' where `username`='{$this->username}'";
		$statement = "call deleteuser('{$this->username}','{$time}')";
		return $statement;
	}


	private function forbiddenUser()
        {
                if($this->isOnlime($this->username))
                {
                        $this->kill($this->username);
                }
                $statement = "update `userdate` set `forbidden`='true' where `username`='{$this->username}'";
                //$statement = "call deleteuser('{$this->username}')";
                return $statement;
        }
	
	private function recoverUser()
	{
		if(!$this->isExist($this->username))
		{
			$this->message = "user {$this->username} does not exist\n";
			return null;
		}
		//$statement = "call deleteuser('{$this->username}')";
		$statement = "update `userdate` set `forbidden`='false' where `username`='{$this->username}'";
		return $statement;
	}

	public  function limitUser()
	{
		if(!$this->isExist($this->username))
		{
			$this->message = "user {$this->username} does not exist\n";
			return null;
		}
		$manage = new mysqlconnection($this->center_db["server"],$this->center_db["username"],$this->center_db["password"]);
		$manage->useDB($this->center_db["db"]);
		$meal = new Meal($manage);
		$meal->contructbyid($this->mealid);

		$connection = new mysqlconnection($this->radius->server,$this->radius->username,$this->radius->password);
		$connection->useDB($this->radius->dbname);

	//	$statement = "delete from `radusergroup` where `username`='{$this->username}'";
	//	$connection->query($statement);

		$meal->updateLimit($this->username,$connection);
//		$meal->dataLimit($this->username);
		//$meal->speedLimit($this->username,$connection);
		//$meal->timeLimit($this->username,$connection);
		//$meal->trafficLimit($this->username,$connection);
		return self::STATEMENT_PASS;
	}

	private function dateUser()
	{
		if(!$this->isExist($this->username))
		{
			$this->message = "user {$this->username} does not exist\n";
			return null;
		}
		
		$this->getDates();	
		if(($this->startDate==-1) || ($this->endDate==-1))
        {
			$this->message = "no startdate and enddate defineed";
            return null;
        }

		$this->setDate();
		return self::STATEMENT_PASS;
	}

	public function setDate()
	{
		$connection = new mysqlconnection($this->radius->server,$this->radius->username,$this->radius->password);
		$connection->useDB($this->radius->dbname);
		$statement = "delete from `userdate` where `username`='{$this->username}'";
		$result = $connection->query($statement);
		$statement = "insert into `userdate`(`username`,`begin`,`end`) values('{$this->username}','{$this->startDate}','{$this->endDate}')";
		$result = $connection->query($statement);

	}

	public function getDates()
	{
		$manage = new mysqlconnection($this->center_db["server"],$this->center_db["username"],$this->center_db["password"]);
		$manage->useDB($this->center_db["db"]);

		$statement = "select `startdate`,`enddate` from `roleDate`,`client` where `roleDate`.`cId`=`client`.`id` and `client`.`user`='{$this->username}'";
		$result = $manage->query($statement);
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))	
		{
			$this->startDate = $row["startdate"];
			$this->endDate = $row["enddate"];
		}
	}

	private function killUser()
	{
		$result = $this->kill($this->username);
		if($result)
		{
			return self::STATEMENT_PASS;
		}
		//fasle
		$this->message = "user[{$this->username}] is off";
		return null;
	}


	private function simul()
	{
		if(!$this->isExist($this->username))
		{
			$this->message = "user {$this->username} does not exist\n";
			return null;
		}
		$sim = $this->getSimul();
		if($sim == 0)
		{
			$this->message = "user[{$this->username}] does not exist";
			return null;
		}
		$this->clearSimul();
		//$statement = "call deleteuser('{$this->username}')";
		$statement = "insert into radcheck(username,attribute,op,value) values('{$this->username}','Simultaneous-Use',':=','{$sim}')";
		return $statement;
	}

	
	private function getSimul()
	{
		$simul = 0;
		$manage = new mysqlconnection($this->center_db["server"],$this->center_db["username"],$this->center_db["password"]);
		$manage->useDB($this->center_db["db"]);

		$statement = "select Simultaneous from client where user='{$this->username}'";
		$result = $manage->query($statement);
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))	
		{
			$simul = $row["Simultaneous"];
		}
		return $simul;
	}

	public function clearSimul()
	{
		$radius = new mysqlconnection($this->radius->server,$this->radius->username,$this->radius->password);
		$radius->useDB($this->radius->dbname);
		$statement = "delete from radcheck where attribute='Simultaneous-Use' and username='{$this->username}'";
		$radius->query($statement);
	}

	private function addUser_result($status,$message)
	{
		//$status = $status."_add";
		$statement = $this->user_result($status,$message); 
		return $statement;
	}

	private function changePassword_result($status,$message)
	{
		//$status = $status."_pwd";
		$statement = $this->user_result($status,$message);
		return $statement;
	}

	private function deleteUser_result($status,$message)
	{	
		$statement = "";
		if($status)
		{
			$statement = "update client set statu='false',httpInfo='',user='{$this->deleteUsernaem}' where user='{$this->username}'";
		}
		else
		{
			$statement = "update client set httpInfo='{$message}' where user='{$this->username}'";
		}
		return $statement;
	}

	private function forbiddenUser_result($status,$message)
        {
                $statement = "";
                if($status)
                {
                        $statement = "update client set statu='stop',httpInfo='' where user='{$this->username}'";
                }
                else
                {
                        $statement = "update client set httpInfo='{$message}' where user='{$this->username}'";
                }
                return $statement;
        }
	
	private function recoverUser_result($status,$message)
	{	
		$statement = "";
		if($status)
		{
			$statement = "update client set statu='true',httpInfo='' where user='{$this->username}'";
		}
		else
		{
			$statement = "update client set httpInfo='{$message}' where user='{$this->username}'";
		}
		return $statement;
	}


	private function limitUser_result($status,$message)
	{
		//$status = $status."_lim";
		$statement = $this->user_result($status,$message); 
		return $statement;
	}

	

	private function dateUser_result($status,$message)
	{
		//$status = $status."_dat";
		$statement = $this->user_result($status,$message); 
		return $statement;
		
	}

	private function killUser_result($status,$message)
	{
		//$status = $status."_kil";
		$statement = $this->user_result($status,$message); 
		return $statement;
	}


	private function simul_result($status,$message)
	{
		//$status = $status."_kil";
		$statement = $this->user_result($status,$message); 
		return $statement;
	}

	private function user_result($status,$message)
	{
		$statement = "";
		if($status)
		{
			$statement = "update client set statu='true',httpInfo='' where user='{$this->username}'";
		}
		else
		{
			$statement = "update client set httpInfo='{$message}' where user='{$this->username}'";
		}
		return $statement;
	}
	
	private function kill($username)
	{
		$connection = new mysqlconnection($this->radius->server,$this->radius->username,$this->radius->password);
		$connection->useDB($this->radius->dbname);
		$statement = "select `server`,`pid` from `userinfo` where username='{$this->username}' and server!='off'";
		$result = $connection->query($statement);
		if($row = mysql_fetch_array($result, MYSQL_ASSOC))	
		{
			$server = $row["server"];
			$pid = $row["pid"];
			$content = "recieve a kill command <p>";
                        $content .= "username:{$username} <p>";
                        $content .= "server:{$server} <p>";
                        $content .= "pid:{$pid} <p>";
                        
			$head = "kill";
			mylog($head,"send mial");
                        sendmail($content);

			if($server!="off")
			{
				$object["pid"] = $pid;
				$object["username"] = $username;
				$info = json_encode($object);
				$pppoeserver = new httpsqs($server,self::QUENE_PORT);
				$result = $pppoeserver->put(self::QUENE_NAME,$info);
				if($result)
				{
					return true;
				}
			}
		}
		return false;		
	}
	
}

?>



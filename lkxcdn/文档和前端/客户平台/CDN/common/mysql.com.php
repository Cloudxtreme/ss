<?php
class MySQL {
    private $host;//服务器
    private $dbname;//数据库名
    private $db;//数据库连接句柄
    private $link;//连接名
    public  $errmsg;//错误信息
    
    public function __construct($host){
    		$this -> open($host);
    }
    public function __destruct(){ //应用析构函数自动释放连接资源
				mysql_close($this -> link);
		}
    
    //连接主机
    private function open($host){
    		
    		$mysql_arr = array();
    		$mysql_arr = $this -> set_host($host);
    		
	    	$this -> link = mysql_connect($mysql_arr[0],$mysql_arr[1],$mysql_arr[2]) or 
	    		  							die (mysql_error());
    }
    
    //设置数据库服务器
    private function set_host($host){
    		require_once('host.com.php');
    		if (class_exists('host')) {
				    $host_class = new host();
				}
    		$host_arr = array();
    			switch($host){
    					case "ibss" : 
    								$host_arr[0] = $host_class -> get_hostname();
    								$host_arr[1] = $host_class -> get_username();
    								$host_arr[2] = $host_class -> get_password();
    									break;
    					case "cdninfo" : 
    								$host_arr[0] = $host_class -> get_cdninfo_hostname();
    								$host_arr[1] = $host_class -> get_username();
    								$host_arr[2] = $host_class -> get_cdn_password();
    									break;
    					case "filestats" : 
    								$host_arr[0] = $host_class -> get_filestats_hostname();
    								$host_arr[1] = $host_class -> get_username();
    								$host_arr[2] = $host_class -> get_cdn_password();
    									break;
    					case "webstats" : 
    								$host_arr[0] = $host_class -> get_webstats_hostname();
    								$host_arr[1] = $host_class -> get_username();
    								$host_arr[2] = $host_class -> get_cdn_password();
    									break;
    					case "newcdn" : 
    								$host_arr[0] = $host_class -> get_newcdn_hostname();
    								$host_arr[1] = $host_class -> get_username();
    								$host_arr[2] = $host_class -> get_cdn_password();
    									break;
						case "newcdnfile" : 
										$host_arr[0] = $host_class -> get_newcdn_file_hostname();
										$host_arr[1] = $host_class -> get_username();
										$host_arr[2] = $host_class -> get_cdn_password();
    									break;
    			  case "squiddns" : 
										$host_arr[0] = $host_class -> get_squiddns_hostname();
										$host_arr[1] = $host_class -> get_squiddns_username();
										$host_arr[2] = $host_class -> get_squiddns_password();
    									break;
    				case "cdnmgr" : 
										$host_arr[0] = $host_class -> get_cdnmgr_hostname();
										$host_arr[1] = $host_class -> get_username();
										$host_arr[2] = $host_class -> get_cdn_password();
    									break;										
    					default: 
    								  break;
    			}
    			return $host_arr;
    }
    
    public function to2DArray($result){
		    $_2DArray = Array();
		    $arr = new ArrayObject($_2DArray);
		    while($row = mysql_fetch_array($result)){
		    		$arr -> append($row);
	   	  }
	   	 return $arr ;
    }
    
    //连接数据库
    public function opendb($database,$charset){
		    $this -> dbname = $database;
		    mysql_query("set names ".$charset);//设置字符集
		    $this -> db = mysql_select_db( $this -> dbname, $this -> link );
		    if (!$this -> db){
		    		$this -> errmsg = "连接数据库错误！";
		    }
    }
    
    public function query($sql) {
		    $result = mysql_query($sql);
		    if ( !$result ){
		    	 $this -> errmsg = "运行错误!";
		    }
		    return $result;
    }
    
    //错误方法吸收
    public function __call($n,$v){
    		return "不存在".$n."()方法";
    }
 }
?> 
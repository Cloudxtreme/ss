<?php
// 本类由系统自动生成，仅供测试用途
class IndexAction extends Action {
    public function index(){
		if($_SESSION['user'] === 'admin'){
			$this->display();
		}else{
			//header("Location: ".__APP__."/Index/timeout");
			header("Location: ".__APP__."/Index/login");
		}
    }
	public function login(){
		//$_SESSION['user'] = null;
		if($_SERVER['REQUEST_METHOD' ] === 'GET'){
			//if(Get_Pwd() === "admin")
			$fd = fopen("/etc/efvpn/pass", "r");
			if($fd)
			{
				$password = trim(fgets($fd));
				if($password == md5("admin"))
					$this->error = "密码为默认 admin, 不安全, 请登录后点击右上  \"修改密码\"";
				fclose($fd);
			}
			else
			{
				$this->error = "密码为默认 admin, 不安全, 请登录后点击右上  \"修改密码\"";
			}
			$this->display();
		}
		else{
			clearstatcache();
			if(file_exists("/etc/efvpn/pass"))
				$password = trim(file_get_contents("/etc/efvpn/pass"));
			else
				$password = md5("admin");
			$password = trim($password);
			$userpass = md5($_POST['password']);
			//echo "$password,$userpass";
			//exit();
			if($userpass == $password){
				$_SESSION['user'] = 'admin';
				header("Location: ".__APP__."/Index/index");
			} else {
				$this->error = "密码输入错误，请核对后再试！";
				$this->display();
			}
		}
	}
	public function logout(){
		unset($_SESSION['user']);
		//session(null);
		echo "ok";
	}
	public function timeout(){
		//$_SESSION['user'] = null;
		$this->display();
	}
}

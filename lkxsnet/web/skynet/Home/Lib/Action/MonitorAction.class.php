<?php
class MonitorAction extends Action {

	public $node_list;
	public function __init(){
		if(empty($this->node_list)){
			$this->node_list = M('list');
		}
	}

	public function traffic(){
		if(isset($_SESSION['user'])){
		
			$this->__init();
			if($_SESSION['zone'] == "all"){
				$glist = $this->node_list->Distinct(true)->field('type')->select();
			}else{
				$condition['type'] = $_SESSION['zone'];
				$glist = $this->node_list->Distinct(true)->field('type')->where($condition)->select();
			}
			
			$condition['status'] = "true";
			if(!empty($_GET['g'])){
				$condition['type'] = $_GET['g'];
				$this->assign('group', $_GET['g']);
			}else{
				$condition['type'] = $glist[0]["type"];
			}
			$zlist = $this->node_list->Distinct(true)->field('subtype')->where($condition)->select();
			
			if(empty($_GET['z'])){
				$condition['subtype'] = $zlist[0]['subtype'];
			}else{
				if($_GET['z'] != "全部"){
					$condition['subtype'] = $_GET['z'];
				}
				$this->assign('subtype', $_GET['z']);
			}
			if($_SESSION['zone'] == "all"){
				$tlist = $this->node_list->where($condition)->order('id desc')->select();
			}else{
				$condition['type'] = $_SESSION['zone'];
				$tlist = $this->node_list->where($condition)->order('id desc')->select();
			}
			
			$v_list = M("baseinfo_kv");
			foreach($tlist as $n=>$val){
				$condition1['sid'] = $val['sid'];
				$condition1['key'] = 'ifDescr';
				$name = $v_list->where($condition1)->getField('value');
				//print_r(explode(" ", $name));exit;
				$tlist[$n]['dev'] = $name;
			}
			
			$this->assign('grouplist', $glist);
			$this->assign('zonelist', $zlist);
			$this->assign('tlist', $tlist);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function trafficInit(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
				$nowday = date('Y-m-d',time());
				$nowtime = date("Y-m-d H:i:s", time());
				$lastday = date('Y-m-d',strtotime("$nowtime-1 day"));
				$lasttime = date('Y-m-d H:i:s',strtotime("$nowtime-1 day"));
				
				$nowList = M("netdev_data_".$nowday);
				$lastList = M("netdev_data_".$lastday);
				
				if(!empty($lastList)){
					$map['dev'] = array('eq', $_POST['dev']);
					$map['sid'] = array('eq', $_POST['sid']);
					$map['timestamp']  = array('gt', $lasttime);
					$last = $lastList->where($map)->field('id,in,out,timestamp')->select();
					
					$condition['dev'] = $_POST['dev'];
					$condition['sid'] = $_POST['sid'];
					$now = $nowList->where($condition)->field('id,in,out,timestamp')->select();
					
					$c = MergeArray($last, $now); 
					$sort_in = list_sort_by($c, 'in', 'desc');
					$sort_out = list_sort_by($c, 'out', 'desc');
					$rslt["maxin"] = $sort_in[0];
					$rslt["maxout"] = $sort_out[0];
					$rslt["result"] = $c;
					echo json_encode($rslt);
				}
				else{
					$condition['dev'] = $_POST['dev'];
					$condition['sid'] = $_POST['sid'];
					$now = $nowList->where($condition)->field('id,in,out,timestamp')->select();
					$sort_in = list_sort_by($now, 'in', 'desc');
					$sort_out = list_sort_by($now, 'out', 'desc');
					$rslt["maxin"] = $sort_in[0];
					$rslt["maxout"] = $sort_out[0];
					$rslt["result"] = $now;
					echo json_encode($rslt);
				}
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function trafficPoint(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$day = date('Y-m-d',time());
				$List = M("netdev_data_".$day);
				
				$map['dev'] = array('eq', $_GET['dev']);
				$map['sid'] = array('eq', $_GET['sid']);
				$map['timestamp']  = array('gt', $_GET['time']);
				
				$tlist = $List->where($map)->field('id,in,out,timestamp')->select(); //order('timestamp desc')->top60();
				$sort_in = list_sort_by($tlist, 'in', 'desc');
				$sort_out = list_sort_by($tlist, 'out', 'desc');
				$rslt["maxin"] = $sort_in[0];
				$rslt["maxout"] = $sort_out[0];
				$rslt["result"] = $tlist;
				echo json_encode($rslt);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function thistory(){
		if(isset($_SESSION['user'])){
			if(empty($_GET['b']) || empty($_GET['e'])){
				$this->error('访问错误！');
			}
			else{
				$this->__init();
				if($_SESSION['zone'] == "all"){
					$glist = $this->node_list->Distinct(true)->field('type')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$glist = $this->node_list->Distinct(true)->field('type')->where($condition)->select();
				}
				//$n_list = M("list");
				$condition['status'] = "true";
				if(!empty($_GET['g'])){
					$condition['type'] = $_GET['g'];
				}else{
					$condition['type'] = $glist[0]["type"];
				}
				$zlist = $this->node_list->Distinct(true)->field('subtype')->where($condition)->select();
			
				if(empty($_GET['z'])){
					$condition['subtype'] = $zlist[0]['subtype'];
				}else{
					if($_GET['z'] != "全部"){
						$condition['subtype'] = $_GET['z'];
					}
					$this->assign('subtype', $_GET['z']);
				}
				if($_SESSION['zone'] == "all"){
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}
				//$tlist = $this->node_list->where($condition)->order('id desc')->select();
				$v_list = M("baseinfo_kv");
				foreach($tlist as $n=>$val){
					$condition1['sid'] = $val['sid'];
					$condition1['key'] = 'ifDescr';
					$name = $v_list->where($condition1)->getField('value');
					$tlist[$n]['dev'] = $name;
				}
				$this->assign('grouplist', $glist);
				$this->assign('zonelist', $zlist);
				$this->assign('tlist', $tlist);
				$this->display();
			}
			
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function historyInit(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$byear=((int)substr($_GET['begin'],0,4));//取得年份
				$bmonth=((int)substr($_GET['begin'],5,2));//取得月份
				$bday=((int)substr($_GET['begin'],8,2));//取得几号
				$start = mktime(0,0,0,$bmonth,$bday,$byear);
				
				$map['dev'] = array('eq', $_GET['dev']);
				$map['sid'] = array('eq', $_GET['sid']);

				$j = 0;
				$data = null;
				for($i = strtotime($_GET['begin']); $i <= strtotime($_GET['end']); $i += 86400) {
					$t = date("Y-m-d", $start + $j * 24 * 3600);
					$List = M("netdev_data_".$t);
					if(!empty($List)){
						$temp = $List->where($map)->field('id,in,out,timestamp')->select();
						if(empty($data)){
							$data = $temp;
						}else{
							$data = array_merge($data, $temp);
						}
					}
					$j++;
				}
				$result = $data;
				$th = ceil(count($data) * 0.95);
				$sort_out = list_sort_by($data, 'out', 'asc');
				//echo json_encode($sort_out[$th]);
				foreach ($result as $key => $value){
					$result[$key]['rateout'] = $sort_out[$th]["out"];
				}
				$sort_ou = list_sort_by($data, 'out', 'desc');
				$sort_in = list_sort_by($data, 'in', 'desc');
				$rslt["maxin"] = $sort_in[0];
				$rslt["maxout"] = $sort_ou[0];
				$rslt["result"] = $result;
				echo json_encode($rslt);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function system(){
		if(isset($_SESSION['user'])){
		
			$this->__init();
			if($_SESSION['zone'] == "all"){
					$glist = $this->node_list->Distinct(true)->field('type')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$glist = $this->node_list->Distinct(true)->field('type')->where($condition)->select();
				}
			//$glist = $this->node_list->Distinct(true)->field('type')->select();
			
			$condition['status'] = "true";
			if(!empty($_GET['g'])){
				$condition['type'] = $_GET['g'];
				$this->assign('group', $_GET['g']);
			}else{
				$condition['type'] = $glist[0]["type"];
			}
			$zlist = $this->node_list->Distinct(true)->field('subtype')->where($condition)->select();
			
			if(empty($_GET['z'])){
				$condition['subtype'] = $zlist[0]['subtype'];
			}else{
				if($_GET['z'] != "全部"){
					$condition['subtype'] = $_GET['z'];
				}
				$this->assign('subtype', $_GET['z']);
			}
			if($_SESSION['zone'] == "all"){
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}
			//$tlist = $this->node_list->where($condition)->order('id desc')->select();
			
			$this->assign('grouplist', $glist);
			$this->assign('zonelist', $zlist);
			$this->assign('tlist', $tlist);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function systemInit(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
				$nowday = date('Y-m-d',time());
				$nowtime = date("Y-m-d H:i:s", time());
				$lastday = date('Y-m-d',strtotime("$nowtime-1 day"));
				$lasttime = date('Y-m-d H:i:s',strtotime("$nowtime-1 day"));
				
				$nowList = M("cpu_data_".$nowday);
				$lastList = M("cpu_data_".$lastday);
				
				if(!empty($lastList)){
					$map['timestamp']  = array('gt', $lasttime);
					$map['sid'] = array('eq', $_POST['sid']);
					$last = $lastList->where($map)->field('id,uper,timestamp')->select();
					
					$condition['sid'] = $_POST['sid'];
					$now = $nowList->where($condition)->field('id,uper,timestamp')->select();
					$c = MergeArray($last, $now);
					echo json_encode($c);
				}
				else{
					$condition['sid'] = $_POST['sid'];
					$now = $nowList->where($condition)->field('id,uper,timestamp')->select();
					echo json_encode($now);
				}
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function systemPoint(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$day = date('Y-m-d',time());
				$List = M("cpu_data_".$day);
				
				$map['sid'] = array('eq', $_GET['sid']);
				$map['timestamp']  = array('gt', $_GET['time']);
				
				$tlist = $List->where($map)->field('id,uper,timestamp')->select();
				echo json_encode($tlist);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function shistory(){
		if(isset($_SESSION['user'])){
			if(empty($_GET['b']) || empty($_GET['e'])){
				$this->error('访问错误！');
			}
			else{
				$this->__init();
				if($_SESSION['zone'] == "all"){
					$glist = $this->node_list->Distinct(true)->field('type')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$glist = $this->node_list->Distinct(true)->field('type')->where($condition)->select();
				}
				//$glist = $this->node_list->Distinct(true)->field('type')->select();
				//$n_list = M("list");
				$condition['status'] = "true";
				if(!empty($_GET['g'])){
					$condition['type'] = $_GET['g'];
				}else{
					$condition['type'] = $glist[0]["type"];
				}
				$zlist = $this->node_list->Distinct(true)->field('subtype')->where($condition)->select();
			
				if(empty($_GET['z'])){
					$condition['subtype'] = $zlist[0]['subtype'];
				}else{
					if($_GET['z'] != "全部"){
						$condition['subtype'] = $_GET['z'];
					}
					$this->assign('subtype', $_GET['z']);
				}
				if($_SESSION['zone'] == "all"){
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}
				//$tlist = $this->node_list->where($condition)->order('id desc')->select();
				
				$this->assign('grouplist', $glist);
				$this->assign('zonelist', $zlist);
				$this->assign('tlist', $tlist);
				$this->display();
			}
			
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function shistoryInit(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$byear=((int)substr($_GET['begin'],0,4));//取得年份
				$bmonth=((int)substr($_GET['begin'],5,2));//取得月份
				$bday=((int)substr($_GET['begin'],8,2));//取得几号
				$start = mktime(0,0,0,$bmonth,$bday,$byear);
				
				$map['sid'] = array('eq', $_GET['sid']);

				$j = 0;
				$data = null;
				for($i = strtotime($_GET['begin']); $i <= strtotime($_GET['end']); $i += 86400) {
					$t = date("Y-m-d", $start + $j * 24 * 3600);
					$List = M("cpu_data_".$t);
					if(!empty($List)){
						$temp = $List->where($map)->field('id,uper,timestamp')->select();
						if(empty($data)){
							$data = $temp;
						}else{
							$data = array_merge($data, $temp);
						}
					}
					$j++;
				}
				echo json_encode($data);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function memory(){
		if(isset($_SESSION['user'])){
			$this->__init();
			if($_SESSION['zone'] == "all"){
					$glist = $this->node_list->Distinct(true)->field('type')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$glist = $this->node_list->Distinct(true)->field('type')->where($condition)->select();
				}
			//$glist = $this->node_list->Distinct(true)->field('type')->select();
			
			$condition['status'] = "true";
			if(!empty($_GET['g'])){
				$condition['type'] = $_GET['g'];
				$this->assign('group', $_GET['g']);
			}else{
				$condition['type'] = $glist[0]["type"];
			}
			$zlist = $this->node_list->Distinct(true)->field('subtype')->where($condition)->select();
			
			if(empty($_GET['z'])){
				$condition['subtype'] = $zlist[0]['subtype'];
			}else{
				if($_GET['z'] != "全部"){
					$condition['subtype'] = $_GET['z'];
				}
				$this->assign('subtype', $_GET['z']);
			}
			if($_SESSION['zone'] == "all"){
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}
			//$tlist = $this->node_list->where($condition)->order('id desc')->select();
			
			$this->assign('grouplist', $glist);
			$this->assign('zonelist', $zlist);
			$this->assign('tlist', $tlist);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function memoryInit(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
				$nowday = date('Y-m-d',time());
				$nowtime = date("Y-m-d H:i:s", time());
				$lastday = date('Y-m-d',strtotime("$nowtime-1 day"));
				$lasttime = date('Y-m-d H:i:s',strtotime("$nowtime-1 day"));
				
				$nowList = M("mem_data_".$nowday);
				$lastList = M("mem_data_".$lastday);
				
				if(!empty($lastList)){
					$map['timestamp']  = array('gt', $lasttime);
					$map['sid'] = array('eq', $_POST['sid']);
					$last = $lastList->where($map)->field('id,uper,super,timestamp')->select();
					
					$condition['sid'] = $_POST['sid'];
					$now = $nowList->where($condition)->field('id,uper,super,timestamp')->select();
					$c = MergeArray($last, $now);
					echo json_encode($c);
				}
				else{
					$condition['sid'] = $_POST['sid'];
					$now = $nowList->where($condition)->field('id,uper,super,timestamp')->select();
					echo json_encode($now);
				}
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function memoryPoint(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$day = date('Y-m-d',time());
				$List = M("mem_data_".$day);
				
				$map['sid'] = array('eq', $_GET['sid']);
				$map['timestamp']  = array('gt', $_GET['time']);
				
				$tlist = $List->where($map)->field('id,uper,super,timestamp')->select();
				echo json_encode($tlist);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function mhistory(){
		if(isset($_SESSION['user'])){
			if(empty($_GET['b']) || empty($_GET['e'])){
				$this->error('访问错误！');
			}
			else{
				$this->__init();
				if($_SESSION['zone'] == "all"){
					$glist = $this->node_list->Distinct(true)->field('type')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$glist = $this->node_list->Distinct(true)->field('type')->where($condition)->select();
				}
				//$glist = $this->node_list->Distinct(true)->field('type')->select();
				//$n_list = M("list");
				$condition['status'] = "true";
				if(!empty($_GET['g'])){
					$condition['type'] = $_GET['g'];
				}else{
					$condition['type'] = $glist[0]["type"];
				}
				$zlist = $this->node_list->Distinct(true)->field('subtype')->where($condition)->select();
			
				if(empty($_GET['z'])){
					$condition['subtype'] = $zlist[0]['subtype'];
				}else{
					if($_GET['z'] != "全部"){
						$condition['subtype'] = $_GET['z'];
					}
					$this->assign('subtype', $_GET['z']);
				}
				if($_SESSION['zone'] == "all"){
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}
				//$tlist = $this->node_list->where($condition)->order('id desc')->select();
				
				$this->assign('grouplist', $glist);
				$this->assign('zonelist', $zlist);
				$this->assign('tlist', $tlist);
				$this->display();
			}
			
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function mhistoryInit(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$byear=((int)substr($_GET['begin'],0,4));//取得年份
				$bmonth=((int)substr($_GET['begin'],5,2));//取得月份
				$bday=((int)substr($_GET['begin'],8,2));//取得几号
				$start = mktime(0,0,0,$bmonth,$bday,$byear);
				
				$map['sid'] = array('eq', $_GET['sid']);

				$j = 0;
				$data = null;
				for($i = strtotime($_GET['begin']); $i <= strtotime($_GET['end']); $i += 86400) {
					$t = date("Y-m-d", $start + $j * 24 * 3600);
					$List = M("mem_data_".$t);
					if(!empty($List)){
						$temp = $List->where($map)->field('id,uper,super,timestamp')->select();
						if(empty($data)){
							$data = $temp;
						}else{
							$data = array_merge($data, $temp);
						}
					}
					$j++;
				}
				echo json_encode($data);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function disk(){
		if(isset($_SESSION['user'])){
			$this->__init();
			
			if($_SESSION['zone'] == "all"){
					$glist = $this->node_list->Distinct(true)->field('type')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$glist = $this->node_list->Distinct(true)->field('type')->where($condition)->select();
				}
			//$glist = $this->node_list->Distinct(true)->field('type')->select();

			$condition['status'] = "true";
			if(!empty($_GET['g'])){
				$condition['type'] = $_GET['g'];
				$this->assign('group', $_GET['g']);
			}else{
				$condition['type'] = $glist[0]["type"];
			}
			$zlist = $this->node_list->Distinct(true)->field('subtype')->where($condition)->select();
			
			if(empty($_GET['z'])){
				$condition['subtype'] = $zlist[0]['subtype'];
			}else{
				if($_GET['z'] != "全部"){
					$condition['subtype'] = $_GET['z'];
				}
				$this->assign('subtype', $_GET['z']);
			}
			if($_SESSION['zone'] == "all"){
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}
			//$tlist = $this->node_list->where($condition)->order('id desc')->select();
			
			$v_list = M("baseinfo_kv");
			foreach($tlist as $n=>$val){
				$condition1['sid'] = $val['sid'];
				$condition1['key'] = 'disks';
				$name = $v_list->where($condition1)->getField('value');
				//print_r(explode(" ", $name));exit;
				$tlist[$n]['dev'] = $name;
			}

			$this->assign('grouplist', $glist);
			$this->assign('zonelist', $zlist);
			$this->assign('tlist', $tlist);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function diskInit(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
				$nowday = date('Y-m-d',time());
				$nowtime = date("Y-m-d H:i:s", time());
				$lastday = date('Y-m-d',strtotime("$nowtime-1 day"));
				$lasttime = date('Y-m-d H:i:s',strtotime("$nowtime-1 day"));
				
				$nowList = M("disk_data_".$nowday);
				$lastList = M("disk_data_".$lastday);
				
				if(!empty($lastList)){
					$map['dev'] = array('eq', $_POST['dev']);
					$map['sid'] = array('eq', $_POST['sid']);
					$map['timestamp']  = array('gt', $lasttime);
					$last = $lastList->where($map)->field('id,uper,timestamp')->select();
					
					$condition['dev'] = $_POST['dev'];
					$condition['sid'] = $_POST['sid'];
					$now = $nowList->where($condition)->field('id,uper,timestamp')->select();
					
					$c = MergeArray($last, $now); 
					//var_export($c);
					echo json_encode($c);
				}
				else{
					$condition['dev'] = $_POST['dev'];
					$condition['sid'] = $_POST['sid'];
					$now = $nowList->where($condition)->field('id,uper,timestamp')->select();
					echo json_encode($now);
				}
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function diskPoint(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$day = date('Y-m-d',time());
				$List = M("disk_data_".$day);
				
				$map['dev'] = array('eq', $_GET['dev']);
				$map['sid'] = array('eq', $_GET['sid']);
				$map['timestamp']  = array('gt', $_GET['time']);
				
				$tlist = $List->where($map)->field('id,uper,timestamp')->select();
				echo json_encode($tlist);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function dhistory() {
		if(isset($_SESSION['user'])){
			if(empty($_GET['b']) || empty($_GET['e'])){
				$this->error('访问错误！');
			}else{
				$this->__init();
				if($_SESSION['zone'] == "all"){
					$glist = $this->node_list->Distinct(true)->field('type')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$glist = $this->node_list->Distinct(true)->field('type')->where($condition)->select();
				}
				//$glist = $this->node_list->Distinct(true)->field('type')->select();
				//$n_list = M("list");
				$condition['status'] = "true";
				if(!empty($_GET['g'])){
					$condition['type'] = $_GET['g'];
				}else{
					$condition['type'] = $glist[0]["type"];
				}
				$zlist = $this->node_list->Distinct(true)->field('subtype')->where($condition)->select();
			
				if(empty($_GET['z'])){
					$condition['subtype'] = $zlist[0]['subtype'];
				}else{
					if($_GET['z'] != "全部"){
						$condition['subtype'] = $_GET['z'];
					}
					$this->assign('subtype', $_GET['z']);
				}
				if($_SESSION['zone'] == "all"){
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$tlist = $this->node_list->where($condition)->order('id desc')->select();
				}
				//$tlist = $this->node_list->where($condition)->order('id desc')->select();
				$v_list = M("baseinfo_kv");
				foreach($tlist as $n=>$val){
					$condition1['sid'] = $val['sid'];
					$condition1['key'] = 'disks';
					$name = $v_list->where($condition1)->getField('value');
					$tlist[$n]['dev'] = $name;
				}
				$this->assign('grouplist', $glist);
				$this->assign('zonelist', $zlist);
				$this->assign('tlist', $tlist);
				$this->display();
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function dhistoryInit() {
		if(isset($_SESSION['user'])) {
			if($_SERVER['REQUEST_METHOD' ] === 'GET'){
				$byear=((int)substr($_GET['begin'],0,4));//取得年份
				$bmonth=((int)substr($_GET['begin'],5,2));//取得月份
				$bday=((int)substr($_GET['begin'],8,2));//取得几号
				$start = mktime(0,0,0,$bmonth,$bday,$byear);
				
				$map['dev'] = array('eq', $_GET['dev']);
				$map['sid'] = array('eq', $_GET['sid']);

				$j = 0;
				$data = null;
				for($i = strtotime($_GET['begin']); $i <= strtotime($_GET['end']); $i += 86400) {
					$t = date("Y-m-d", $start + $j * 24 * 3600);
					$List = M("disk_data_".$t);
					if(!empty($List)){
						$temp = $List->where($map)->field('id,uper,timestamp')->select();
						if(empty($data)){
							$data = $temp;
						}else{
							$data = array_merge($data, $temp);
						}
					}
					$j++;
				}
				//echo $List->getlastsql();exit;
				echo json_encode($data);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function host(){
		if(isset($_SESSION['user'])){
			if(empty($_GET['nid'])){
				$this->error('访问错误！');
			}else{
				$this->__init();
				
				$condition['id'] = $_GET['nid'];
				$node = $this->node_list->where($condition)->find();  

				$v_list = M("baseinfo_kv");
				$condition1['sid'] = $node['sid'];
				$condition1['key'] = 'ifDescr';
				$dev = $v_list->where($condition1)->getField('value');
				$node['dev'] = $dev;
				
				$condition2['sid'] = $node['sid'];
				$condition2['key'] = 'disks';
				$disk = $v_list->where($condition2)->getField('value');
				$node['disk'] = $disk;
				$this->assign('node', $node);
				
				if($_SESSION['zone'] == "all"){
					$nlist = $this->node_list->where('status = "true"')->order('id desc')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$condition['status'] = "true";
					$nlist = $this->node_list->where($condition)->order('id desc')->select();
				}
				//$nlist = $this->node_list->where('status = "true"')->order('id desc')->select();
				$this->assign('tlist', $nlist);
				
				$this->display();
			}
			
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function hostInit(){
		if(isset($_SESSION['user'])){
			if(empty($_POST['sid']) || empty($_POST['dev']) || empty($_POST['disk'])){
				$this->error('访问错误！');
			}else{
				$nowday = date('Y-m-d',time());
				$nowtime = date("Y-m-d H:i:s", time());
				$lastday = date('Y-m-d',strtotime("$nowtime-1 day"));
				$lasttime = date('Y-m-d H:i:s',strtotime("$nowtime-1 day"));
				
				$now_net_List = M("netdev_data_".$nowday);
				$last_net_List = M("netdev_data_".$lastday);
				$net_data = null;
				if(!empty($last_net_List)){
					$map['dev'] = array('eq', $_POST['dev']);
					$map['sid'] = array('eq', $_POST['sid']);
					$map['timestamp']  = array('gt', $lasttime);
					$last = $last_net_List->where($map)->field('id,in,out,timestamp')->select();
					
					$condition['dev'] = $_POST['dev'];
					$condition['sid'] = $_POST['sid'];
					$now = $now_net_List->where($condition)->field('id,in,out,timestamp')->select();
					
					$net_data = MergeArray($last, $now); 
				}else{
					$condition['dev'] = $_POST['dev'];
					$condition['sid'] = $_POST['sid'];
					$now = $now_net_List->where($condition)->field('id,in,out,timestamp')->select();
					$net_data = $now;
				}
				$sort_in = list_sort_by($net_data, 'in', 'desc');
				$sort_out = list_sort_by($net_data, 'out', 'desc');
				$rslt["maxin"] = $sort_in[0];
				$rslt["maxout"] = $sort_out[0];
				
				$now_cpu_List = M("cpu_data_".$nowday);
				$last_cpu_List = M("cpu_data_".$lastday);
				$cpu_data = null;
				if(!empty($last_cpu_List)){
					$map['sid'] = array('eq', $_POST['sid']);
					$map['timestamp']  = array('gt', $lasttime);
					$last = $last_cpu_List->where($map)->field('id,uper,timestamp')->select();
					
					$condition['sid'] = $_POST['sid'];
					$now = $now_cpu_List->where($condition)->field('id,uper,timestamp')->select();
					
					$cpu_data = MergeArray($last, $now); 
				}else{
					$condition['sid'] = $_POST['sid'];
					$now = $now_cpu_List->where($condition)->field('id,uper,timestamp')->select();
					$cpu_data = $now;
				}
				
				$now_mem_List = M("mem_data_".$nowday);
				$last_mem_List = M("mem_data_".$lastday);
				$mem_data = null;
				if(!empty($last_mem_List)){
					$map['sid'] = array('eq', $_POST['sid']);
					$map['timestamp']  = array('gt', $lasttime);
					$last = $last_mem_List->where($map)->field('id,uper,super,timestamp')->select();
					
					$condition['sid'] = $_POST['sid'];
					$now = $now_mem_List->where($condition)->field('id,uper,super,timestamp')->select();
					
					$mem_data = MergeArray($last, $now); 
				}else{
					$condition['sid'] = $_POST['sid'];
					$now = $now_mem_List->where($condition)->field('id,uper,super,timestamp')->select();
					$mem_data = $now;
				}
				
				$now_disk_List = M("disk_data_".$nowday);
				$last_disk_List = M("disk_data_".$lastday);
				$disk_data = null;
				if(!empty($last_disk_List)){
					$map['dev'] = array('eq', $_POST['disk']);
					$map['sid'] = array('eq', $_POST['sid']);
					$map['timestamp']  = array('gt', $lasttime);
					$last = $last_disk_List->where($map)->field('id,uper,timestamp')->select();
					
					$condition['dev'] = $_POST['disk'];
					$condition['sid'] = $_POST['sid'];
					$now = $now_disk_List->where($condition)->field('id,uper,timestamp')->select();
					
					$disk_data = MergeArray($last, $now); 
				}else{
					$condition['dev'] = $_POST['disk'];
					$condition['sid'] = $_POST['sid'];
					$now = $now_disk_List->where($condition)->field('id,uper,timestamp')->select();
					$disk_data = $now;
				}
				
				$rslt["net"] = $net_data;
				$rslt["cpu"] = $cpu_data;
				$rslt["mem"] = $mem_data;
				$rslt["disk"] = $disk_data;
				echo json_encode($rslt);
			}
			
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function hhistory(){
		if(isset($_SESSION['user'])){
			if(empty($_GET['b']) || empty($_GET['e']) || empty($_GET['nid'])){
				$this->error('访问错误！');
			}else{
				$this->assign('begin', $_GET['b']);
				$this->assign('end', $_GET['e']);
				
				$this->__init();
				$condition['id'] = $_GET['nid'];
				$node = $this->node_list->where($condition)->find();  

				$v_list = M("baseinfo_kv");
				$condition1['sid'] = $node['sid'];
				$condition1['key'] = 'ifDescr';
				$dev = $v_list->where($condition1)->getField('value');
				$node['dev'] = $dev;
				
				$condition2['sid'] = $node['sid'];
				$condition2['key'] = 'disks';
				$disk = $v_list->where($condition2)->getField('value');
				$node['disk'] = $disk;
				//print_r($node);exit;
				$this->assign('node', $node);
				
				if($_SESSION['zone'] == "all"){
					$nlist = $this->node_list->where('status = "true"')->order('id desc')->select();
				}else{
					$condition['type'] = $_SESSION['zone'];
					$condition['status'] = "true";
					$nlist = $this->node_list->where($condition)->order('id desc')->select();
				}
				//$nlist = $this->node_list->where('status = "true"')->order('id desc')->select();
				$this->assign('tlist', $nlist);
				
				$this->display();
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function hhistoryInit(){
		if(isset($_SESSION['user'])){
			if(empty($_GET['begin']) || empty($_GET['end']) || empty($_GET['sid'])){
				$this->error('访问错误！');
			}else{
				$byear=((int)substr($_GET['begin'],0,4));//取得年份
				$bmonth=((int)substr($_GET['begin'],5,2));//取得月份
				$bday=((int)substr($_GET['begin'],8,2));//取得几号
				$start = mktime(0,0,0,$bmonth,$bday,$byear);
				
				$map_dev['sid'] = array('eq', $_GET['sid']);
				$map_dev['dev'] = array('eq', $_GET['dev']);
				
				$map_disk['sid'] = array('eq', $_GET['sid']);
				$map_disk['dev'] = array('eq', $_GET['disk']);
				
				$map['sid'] = array('eq', $_GET['sid']);
				
				$j = 0;
				$net_data = null;
				$cpu_data = null;
				$mem_data = null;
				$disk_data = null;
				for($i = strtotime($_GET['begin']); $i <= strtotime($_GET['end']); $i += 86400) {
					$t = date("Y-m-d", $start + $j * 24 * 3600);
					
					$netList = M("netdev_data_".$t);
					if(!empty($netList)){
						$temp = $netList->where($map_dev)->field('id,in,out,timestamp')->select();
						if(empty($net_data)){
							$net_data = $temp;
						}else{
							$net_data = array_merge($net_data, $temp);
						}
					}
					$sort_in = list_sort_by($net_data, 'in', 'desc');
					$sort_out = list_sort_by($net_data, 'out', 'desc');
					$rslt["maxin"] = $sort_in[0];
					$rslt["maxout"] = $sort_out[0];
					
					$cpuList = M("cpu_data_".$t);
					if(!empty($cpuList)){
						$temp = $cpuList->where($map)->field('id,uper,timestamp')->select();
						if(empty($cpu_data)){
							$cpu_data = $temp;
						}else{
							$cpu_data = array_merge($cpu_data, $temp);
						}
					}
					$memList = M("mem_data_".$t);
					if(!empty($memList)){
						$temp = $memList->where($map)->field('id,uper,super,timestamp')->select();
						if(empty($mem_data)){
							$mem_data = $temp;
						}else{
							$mem_data = array_merge($mem_data, $temp);
						}
					}
					$diskList = M("disk_data_".$t);
					if(!empty($diskList)){
						$temp = $diskList->where($map_disk)->field('id,uper,timestamp')->select();
						if(empty($disk_data)){
							$disk_data = $temp;
						}else{
							$disk_data = array_merge($disk_data, $temp);
						}
					}
					$j++;
				}
				
				$rslt["net"] = $net_data;
				$rslt["cpu"] = $cpu_data;
				$rslt["mem"] = $mem_data;
				$rslt["disk"] = $disk_data;
				//echo $diskList->getlastsql();exit; 
				echo json_encode($rslt);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function hhistoryDev(){
		if(isset($_SESSION['user'])){
			if(empty($_GET['begin']) || empty($_GET['end']) || empty($_GET['sid'])){
				$this->error('访问错误！');
			}else{
				$byear=((int)substr($_GET['begin'],0,4));//取得年份
				$bmonth=((int)substr($_GET['begin'],5,2));//取得月份
				$bday=((int)substr($_GET['begin'],8,2));//取得几号
				$start = mktime(0,0,0,$bmonth,$bday,$byear);
				
				$map_dev['sid'] = array('eq', $_GET['sid']);
				$map_dev['dev'] = array('eq', $_GET['dev']);
				
				$j = 0;
				$net_data = null;
				for($i = strtotime($_GET['begin']); $i <= strtotime($_GET['end']); $i += 86400) {
					$t = date("Y-m-d", $start + $j * 24 * 3600);
					$netList = M("netdev_data_".$t);
					if(!empty($netList)){
						$temp = $netList->where($map_dev)->field('id,in,out,timestamp')->select();
						if(empty($net_data)){
							$net_data = $temp;
						}else{
							$net_data = array_merge($net_data, $temp);
						}
					}
					$j++;
				}
				$sort_in = list_sort_by($net_data, 'in', 'desc');
				$sort_out = list_sort_by($net_data, 'out', 'desc');
				$rslt["maxin"] = $sort_in[0];
				$rslt["maxout"] = $sort_out[0];
				$rslt["result"] = $net_data;
				echo json_encode($rslt);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function hhistoryDisk(){
		if(isset($_SESSION['user'])){
			if(empty($_GET['begin']) || empty($_GET['end']) || empty($_GET['sid'])){
				$this->error('访问错误！');
			}else{
				$byear=((int)substr($_GET['begin'],0,4));//取得年份
				$bmonth=((int)substr($_GET['begin'],5,2));//取得月份
				$bday=((int)substr($_GET['begin'],8,2));//取得几号
				$start = mktime(0,0,0,$bmonth,$bday,$byear);
				
				$map_disk['sid'] = array('eq', $_GET['sid']);
				$map_disk['dev'] = array('eq', $_GET['dev']);
				
				$j = 0;
				$disk_data = null;
				for($i = strtotime($_GET['begin']); $i <= strtotime($_GET['end']); $i += 86400) {
					$t = date("Y-m-d", $start + $j * 24 * 3600);
					$diskList = M("disk_data_".$t);
					if(!empty($diskList)){
						$temp = $diskList->where($map_disk)->field('id,uper,timestamp')->select();
						if(empty($disk_data)){
							$disk_data = $temp;
						}else{
							$disk_data = array_merge($disk_data, $temp);
						}
					}
					$j++;
				}
				echo json_encode($disk_data);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
}
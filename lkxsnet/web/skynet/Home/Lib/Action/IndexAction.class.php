<?php
// 本类由系统自动生成，仅供测试用途
class IndexAction extends Action {

	public $node_list;
	public function __init(){
		if(empty($this->node_list)){
			$this->node_list = M('list');
		}
	}

	public function index(){
		if(isset($_SESSION['user'])){
			$this->__init();
			//$n_list = M("list");
			$f_list = M("file");
			$i_list = M("baseinfo_keys");
			import('ORG.Util.Page');// 导入分页类
			
			if(empty($_GET['g']) || $_GET['g'] == "全部"){
				if(empty($_GET['z']) || $_GET['z'] == "全部"){
					if($_SESSION['zone'] == "all"){
						$count = $this->node_list->count(); //总条数
					}else{
						$condition["type"] = $_SESSION['zone'];
						$count = $this->node_list->where($condition)->count(); //总条数
					}
					$Page = new Page($count, 100);
					$nowPage = isset($_GET['p']) ? $_GET['p'] : 1;
					if($_SESSION['zone'] == "all"){
						$list = $this->node_list->order('type desc')->page($nowPage.','.$Page->listRows)->select();
					}else{
						$condition["type"] = $_SESSION['zone'];
						$list = $this->node_list->order('id desc')->page($nowPage.','.$Page->listRows)->where($condition)->select();
					}
					$this->assign('nodelist', $list);
				}else{
					if($_SESSION['zone'] == "all"){
						$condition["subtype"] = $_GET['z'];
					}else{
						$condition["type"] = $_SESSION['zone'];
						$condition["subtype"] = $_GET['z'];
					}
				
					$count = $this->node_list->where($condition)->count(); //总条数
					$Page = new Page($count, 100);
					$nowPage = isset($_GET['p']) ? $_GET['p'] : 1;
					$list = $this->node_list->order('id desc')->page($nowPage.','.$Page->listRows)->where($condition)->select();
					$this->assign('nodelist', $list);
					
					$this->assign('subtype', $_GET['z']);
				}
				//获取大区列表
				if($_SESSION['zone'] == "all"){
					$alist = $this->node_list->Distinct(true)->field('subtype')->select();
				}else{
					$condition["type"] = $_SESSION['zone'];
					$alist = $this->node_list->Distinct(true)->field('subtype')->where($condition)->select();
				}
				$this->assign('arealist', $alist);
			}else{
				if(empty($_GET['z']) || $_GET['z'] == "全部"){
				
					$condition["type"] = $_GET['g'];
					
					$count = $this->node_list->where($condition)->count(); //总条数
					$Page = new Page($count, 100);
					$nowPage = isset($_GET['p']) ? $_GET['p'] : 1;
					$list = $this->node_list->where($condition)->order('id desc')->page($nowPage.','.$Page->listRows)->select();
					$this->assign('nodelist', $list);
					
					$this->assign('group', $_GET['g']);
				}else{
					$condition["type"] = $_GET['g'];
					$condition["subtype"] = $_GET['z'];
					
					$count = $this->node_list->where($condition)->count(); //总条数
					$Page = new Page($count, 100);
					$nowPage = isset($_GET['p']) ? $_GET['p'] : 1;
					$list = $this->node_list->where($condition)->order('id desc')->page($nowPage.','.$Page->listRows)->select();
					$this->assign('nodelist', $list);
					
					$this->assign('group', $_GET['g']);
					$this->assign('subtype', $_GET['z']);
				}
				//根据组名获取大区列表
				$condition1["type"] = $_GET['g'];
				$alist = $this->node_list->Distinct(true)->field('subtype')->where($condition1)->select();
				//echo $this->node_list->getlastsql();exit; 
				$this->assign('arealist', $alist);
			}
			
			$flist = $f_list->order('id desc')->select();
			$this->assign('filelist', $flist);
				
			$ilist = $i_list->order('id desc')->select();
			$this->assign('typelist', $ilist);
			
			if($_SESSION['zone'] == "all"){
				$glist = $this->node_list->Distinct(true)->field('type')->select();
			}else{
				$condition["type"] = $_SESSION['zone'];
				$glist = $this->node_list->Distinct(true)->field('type')->where($condition)->select();
			}
			$this->assign('grouplist', $glist);
			
			//获取cpu的策略列表
			$rulelist = M('alarm_rule');
			$rlist = $rulelist->query('SELECT * FROM `node_alarm_rule` WHERE (`type` = \'mem\' or `type` = \'cpu\') ORDER BY id desc');
			$this->assign('rulelist', $rlist);

			$show = $Page->show();// 分页显示输出
			$this->assign('page', $show);// 赋值分页输出
			//print_r($t_list); exit;
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function upload() {
		if(isset($_SESSION['user'])){
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();    //实例化上传类
			$upload->maxSize  = 314572800;  //设置附件上传大小
			$upload->uploadReplace = false; //覆盖同名文件
			//$upload->allowExts  = array('sh', 'rar', 'zip', 'jar', 'txt');// 设置附件上传类型
			$upload->saveRule = uniqid;  //命名规则
			$arr = C("TMPL_PARSE_STRING");
			$path = $_SERVER['DOCUMENT_ROOT'].$arr['__PUBLIC__']."/Uploads/"; //设置附件上传目录
			//print_r($path);exit;
			$upload->savePath = $path;
			if(!$upload->upload()) {
				//上传错误提示错误信息
				$this->error($upload->getErrorMsg());
			}else{
				//上传成功
				$info = $upload->getUploadFileInfo();
				$p = "Public/Uploads/".$info[0]['savename'];

				//保存表单数据 包括附件数据
				$file = M("file"); // 实例化file对象
				$file->create(); // 创建数据对象
				$file->name = $_POST['file_name']; // 保存上传的照片根据需要自行组装
				$file->path = $p;
				$file->desc = $_POST['file_desc']; // 保存上传的照片根据需要自行组装
				$file->add(); // 写入用户数据到数据库
				
				WriteOptLog("添加", "上传文件：".$_POST['file_name']."，".$_POST['file_desc']);
				$this->success('上传成功！');
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function node_status(){
		if(isset($_SESSION['user'])){
			//$list = M("list");
			$this->__init();
			$node_list = $this->node_list->select();
			date_default_timezone_set("prc");
			foreach($node_list as $n=>$val){
				$node_list[$n]['time'] = abs(strtotime('now') - strtotime($node_list[$n]['lasttime'])) / 60; //换算成分钟
			}
			echo json_encode($node_list);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function task_status(){
		if(isset($_SESSION['user'])){
			$list = M("task");
			$f_list = M("task_feedback");
			$tasklist = $list->field(array('id'))->select();
			foreach($tasklist as $n=>$val){
				$total = $f_list->where('taskid=\''.$val['id'].'\'')->count();
				$finish = $f_list->where('taskid=\''.$val['id'].'\' and status=\'finish\'')->count();
				$tasklist[$n]['rate'] = round($finish / $total, 2) * 100;
			}
			echo json_encode($tasklist);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function reTask(){
		if(isset($_SESSION['user'])){
			if(isset($_POST['id'])){
				$f_list = M("task_feedback");
				$condition['id'] = $_POST['id'];
				$data = array('try'=>'0', 'status'=>'ready');
				$re = $f_list->where($condition)->setField($data);
				//echo $f_list->getlastsql();exit; 
				if($re == 1){
					$ret = array("result"=>1, "reason"=>"下发成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"下发失败，请联系管理员！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function updateSet(){
		if(isset($_SESSION['user'])){
			if(isset($_POST['id'])){
				$blist = M("baseinfo_kv");
				$condition['id'] = $_POST['id'];
				$data['key'] = $_POST['key'];
				$data['value'] = $_POST['value'];
				
				$re = $blist->where($condition)->data($data)->save(); // 根据条件保存修改的数据
				if($re == 1){
					WriteOptLog("修改", "修改设备属性：".$_POST['id']."，".$_POST['key']."，".$_POST['value']);
					$ret = array("result"=>1, "reason"=>"修改成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"没有改动的数据！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function AlarmSet(){
		if(isset($_SESSION['user'])){
			$alist = M("alarm");
			
			if(isset($_POST['sidstr']) && isset($_POST['ridstr'])){
			
				$sidarr = $_POST['sidstr'];
				$sarr = explode(',', $sidarr);
				
				$ridarr = $_POST['ridstr'];
				$rarr = explode(',', $ridarr);
				
				$type = $_POST['type'];
				$level = $_POST['level'];
				$span = $_POST['span'];
				$status = $_POST['status'];
				
				foreach ($sarr as $sid) {
					//删除原来有的cpu和内存策略
					$rule = $alist->query("select node_alarm.id, node_alarm.sid, node_alarm.rid, node_alarm_rule.type from node_alarm left join node_alarm_rule on node_alarm.rid=node_alarm_rule.id where node_alarm.sid = '$sid'");
					if(!empty($rule)){
						foreach($rule as $n=>$val){
							if($rule[$n]['type'] == $type){
								$condition["id"] = $rule[$n]['id'];
								$alist->where($condition)->delete();
							}
						}
					}
					//添加新的策略
					foreach ($rarr as $rid) {
						$alarm['sid'] = $sid;
						$alarm['rid'] = $rid;
						$alarm['level'] = $level;
						$alarm['timespan'] = $span;
						$alarm['status'] = $status;
						$alist->data($alarm)->add();
					}
				}
				WriteOptLog("添加", "设置报警策略：".$_POST['sidstr']."，".$_POST['ridstr']);
				$ret = array("result"=>1, "reason"=>"添加报警成功！");
			}else{
				$ret = array("result"=>0, "reason"=>"添加报警失败，请联系管理员！");
			}
			echo json_encode($ret);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function multSet(){
		if(isset($_SESSION['user'])){
			$blist = M("baseinfo_kv");
			
			if(isset($_POST['sidstr'])){
				$sidarr = $_POST['sidstr'];
				$arr = explode(',', $sidarr);
				foreach ($arr as $sid) {
					$condition['sid'] = $sid;
					$condition['key'] = $_POST['type'];
					$aa = $blist->where($condition)->select();
					if(!empty($aa)){
						$data['value'] = $_POST['value'];
						$blist->where($condition)->data($data)->save();
					}else{
						$baseinfo['sid'] = $sid;
						$baseinfo['key'] = $_POST['type'];
						$baseinfo['value'] = $_POST['value'];
						$blist->data($baseinfo)->add();
					}
				}
				WriteOptLog("添加", "批量设置设备属性：".$_POST['type']."，".$_POST['value']."，设备：".$sidarr);
				$ret = array("result"=>1, "reason"=>"批量设置成功！");
			}else{
				$ret = array("result"=>0, "reason"=>"批量设置失败，请联系管理员！");
			}
			echo json_encode($ret);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function readAttr(){
		if(isset($_SESSION['user'])){
			$v_list = M("baseinfo_kv");
			$k_list = M("baseinfo_keys");
			$condition['sid'] = $_POST['sid'];
			$rlt_list = $v_list->where($condition)->select();
			foreach($rlt_list as $n=>$val){
				$kcondition['key'] = $val['key'];
				$name = $k_list->where($kcondition)->getField('name');
				//echo $name;
				$rlt_list[$n]['attr'] = $name;
			}
			echo json_encode($rlt_list);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function readAlarm(){
		if(isset($_SESSION['user'])){
			$a_list = M("alarm");
			$r_list = M("alarm_rule");
			$condition['sid'] = $_POST['sid'];
			$alam_list = $a_list->where($condition)->select();
			foreach($alam_list as $n=>$val){
				$kcondition['id'] = $val['rid'];
				$name = $r_list->where($kcondition)->find();
				//echo $name;
				$alam_list[$n]['rule'] = $name["rule"];
				$alam_list[$n]['rtype'] = $name["type"];
				$alam_list[$n]['rstatus'] = $name["status"];
			}
			echo json_encode($alam_list);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function stopAlarm(){
		if(isset($_SESSION['user'])){
			$a_list = M("alarm");
			$condition['id'] = $_POST['id'];
			$data['status'] = "off";
			$a_list->where($condition)->data($data)->save();
			$ret = array("result"=>0, "reason"=>"报警策略停用成功！");
			echo json_encode($ret);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	public function startAlarm(){
		if(isset($_SESSION['user'])){
			$a_list = M("alarm");
			$condition['id'] = $_POST['id'];
			$data['status'] = "on";
			$a_list->where($condition)->data($data)->save();
			$ret = array("result"=>0, "reason"=>"报警策略启用成功！");
			echo json_encode($ret);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function delAlarm(){
		if(isset($_SESSION['user'])){
			$a_list = M("alarm");
			$condition['id'] = $_POST['id'];
			$a_list->where($condition)->delete();
			$ret = array("result"=>0, "reason"=>"报警策略删除成功！");
			echo json_encode($ret);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function tasks(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD'] === 'GET'){
			
				$t_list = M("task");
				$f_list = M("task_feedback");
				
				import('ORG.Util.Page');// 导入分页类
				if($_SESSION['zone'] == "all"){
					$count = $t_list->count(); //总条数
					$Page = new Page($count,20);
					$nowPage = isset($_GET['p'])?$_GET['p']:1;
					$tasklist = $t_list->order('timestamp desc')->page($nowPage.','.$Page->listRows)->select();
					//$tasklist = $t_list->order('timestamp desc')->limit(($nowPage-1) * 5, 5)->select();
				}else{
					$cnd["ntype"] = $_SESSION['zone'];
					$count = $t_list->where($cnd)->count(); //总条数
					$Page = new Page($count,20);
					$nowPage = isset($_GET['p'])?$_GET['p']:1;
					$tasklist = $t_list->order('timestamp desc')->page($nowPage.','.$Page->listRows)->where($cnd)->select();
				}

				foreach($tasklist as $n=> $val){
					$total = $f_list->where('taskid=\''.$val['id'].'\'')->count();
					$finish = $f_list->where('taskid=\''.$val['id'].'\' and status=\'finish\'')->count();
					$tasklist[$n]['rate'] = round($finish / $total, 2) * 100;
					$tasklist[$n]['status'] = $val['status'] == 'true' ? '可用' : '不可用';
				}
				
				$this->assign('tasklist', $tasklist);
				$show = $Page->show();// 分页显示输出
				$this->assign('page',$show);// 赋值分页输出
				$this->display();
			}else{	//查看任务执行详情
				$f_list = M("task_feedback");
				$n_list = M("list");
				$condition['taskid'] = $_POST['id'];
				$task_list = $f_list->where($condition)->select();
				foreach($task_list as $n=>$val){
					$desc = $n_list->where('sid=\''.$val['sid'].'\'')->find();
					$task_list[$n]['desc'] = $desc["desc"];
				}
				echo json_encode($task_list);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function license(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD'] === 'GET'){
			
				$this->__init();
				//$node_list = $this->node_list->select();
				
				import('ORG.Util.Page');// 导入分页类
				$condition['license'] = "yes";
				$count = $this->node_list->where($condition)->count(); //总条数
				$Page = new Page($count, 20);
				$nowPage = isset($_GET['p'])?$_GET['p']:1;
				$devicelist = $this->node_list->order('id desc')->page($nowPage.','.$Page->listRows)->where($condition)->select();

				foreach($devicelist as $n=> $val){
					$devicelist[$n]['status'] = $val['status'] == 'connected' ? '可用' : '不可用';
				}
				
				$this->assign('devicelist', $devicelist);
				$show = $Page->show();// 分页显示输出
				$this->assign('page',$show);// 赋值分页输出
				$this->display();
			}else{
				$this->__init();
				$condition['sid'] = $_POST['id'];
				$device_list = $this->node_list->where($condition)->select();
				echo json_encode($device_list);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function createLicence(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD'] === 'GET'){
				$f_list = M("file");
				$flist = $f_list->order('id desc')->select();
				$this->assign('filelist', $flist);
				
				$this->__init();
				$condition['sid'] = $_GET['sid'];
				$device = $this->node_list->where($condition)->find();
				$this->assign('device', $device);
				$this->display();
			}else{
				$key = $_POST['key'];
				
				$data['sid'] = $_POST['sid'];
				$data['start'] = $_POST['start'];
				$data['over'] = $_POST['end'];
				$data['info'] = $_POST['info'];
				$path = "/var/www/html/skynet";
				$data['license_file'] = $path."/License/".$data['sid'].".license";
				$data['create_time'] = date("Y-m-d H:i:s", time());
				
				$cmd="python ".$path."/build_license.py ".$key." ".$path."/License/".$data['sid'].".license ".$data['sid'];
				echo $cmd;
				exec($cmd, $out, $states);
				//print_r($out);print_r($states);exit;
				if(empty($out) && $states === 1){
					$list = M("license");
					$condition['sid'] = $data['sid'];
					$license = $list->where($condition)->find();
					if(empty($license)){
						$list->data($data)->add();
					}else{
						$list->where($condition)->data($data)->save();
					}
					WriteOptLog("添加", "生成授权文件： ".$data['sid']."，".$data['start']."，".$data['over']);
					$ret = array("result"=>1, "reason"=>"成功生成授权文件！");
				}else{
					$ret = array("result"=>0, "reason"=>"生成授权文件失败！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function downLicence(){
		if(isset($_SESSION['user'])){
			$l_list = M("license");
			$condition['sid'] = $_GET['sid'];
			$llist = $l_list->where($condition)->select();
			$this->assign('licenselist', $llist);
			
			/*$this->__init();
			$condition['sid'] = $_GET['sid'];
			$device = $this->node_list->where($condition)->find();
			$this->assign('device', $device);*/
			
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function download(){
		if(isset($_SESSION['user'])){
			$path = '/var/www/html/skynet/License/';   //设置文件上传路径，服务器上的绝对路径
			$sid = $_GET['sid'];   //GET方式传到此方法中的参数id,即文件在数据库里的保存id.根据之查找文件信息。
			
			if(!isset($sid))    //如果id为空而出错时，程序跳转到项目的Index/index页面。或可做其他处理。
			{
				$this->redirect('downLicence','Index','',APP_NAME,'',1);
			}
			$file = M('license');       //利用与表file对应的数据模型类FileModel来建立数据对象。
			$result = $file->find($sid);  //根据id查询到文件信息
			if($result == false)    //如果查询不到文件信息而出错时，程序跳转到项目的Index/index页面。或可做其他处理
			{
				$this->redirect('downLicence','Index','',APP_NAME,'',1);
			}
			
			$showname = $file->sid.".lincense"; //文件原名
			$filename = $file->license_file;  //完整文件名（路径加名字）
			//print_r($filename);exit;
			import('ORG.Net.Http');
			Http::download($filename, $showname);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function delLicense(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
			      $List = M("license");
			      $condition['sid'] = $_POST['sid'];
			      $re = $List->where($condition)->delete();
			      if($re == 1){
				      WriteOptLog("删除", "授权文件： ".$_POST['sid']);
				      $ret = array("result"=>1, "reason"=>"删除成功！");
			      }else{
				      $ret = array("result"=>0, "reason"=>"删除失败，请联系管理员！");
			      }
			      echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function addDevice(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD'] === 'POST'){
				$this->__init();
				
				$data['sid'] = $_POST['sid'];
				$data['wanip'] = $_POST['ip'];
				$data['type'] = $_POST['type'];
				$data['subtype'] = $_POST['subtype'];
				$data['zone'] = $_POST['zone'];
				$data['local'] = $_POST['local'];
				$data['status'] = $_POST['status'];
				$data['desc'] = $_POST['desc'];
				$data['license'] = $_POST['license'];
				
				$re = $this->node_list->data($data)->add();
				if($re > 0){
					WriteOptLog("添加", "设备： ".$data['sid']."，".$data['wanip']."，".$data['desc']);
					$ret = array("result"=>1, "id"=>$re, "reason"=>"添加成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"添加失败，请联系管理员！");
				}
				echo json_encode($ret);
			}else{
				$this->display();
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function readDevice(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD'] === 'POST'){
				$d_list = M("device");
				$condition['id'] = $_POST['id'];
				$device = $d_list->where($condition)->find();
				echo json_encode($device);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}

	public function edit(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
			      $this->__init();
			      //$List = M("list");
			      $condition['id'] = $_POST['id'];
			      $data['wanip'] = $_POST['ip'];
			      $data['sid'] = $_POST['sn'];
			      /*$data['type'] = $_POST['type'];
			      $data['subtype'] = $_POST['subtype'];
			      $data['zone'] = $_POST['zone'];
			      $data['local'] = $_POST['local'];*/
			      $data['status'] = $_POST['status'];
			      $data['desc'] = $_POST['desc'];
			      
			      //$re = $List->where($condition)->setField(array('ip','type','zone','local','desc'),array($data['ip'],$data['type'],$data['zone'],$data['local'],$data['desc']));
			      $re = $this->node_list->where($condition)->data($data)->save(); // 根据条件保存修改的数据
			      //echo $List->getlastsql();exit;
			      if($re == 1){
				      WriteOptLog("修改", "设备： ".$_POST['sn']."，".$data['wanip']);
				      $ret = array("result"=>1, "reason"=>"修改成功！");
			      }else{
				      $ret = array("result"=>0, "reason"=>"没有改动的数据！");
			      }
			      echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	/*public function add(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
				$this->__init();
				//$List = M("list");
				$data['wanip'] = $_POST['ip'];
				$data['sid'] = $_POST['sn'];
				$data['type'] = $_POST['type'];
				$data['subtype'] = $_POST['subtype'];
				$data['zone'] = $_POST['zone'];
				$data['local'] = $_POST['local'];
				$data['status'] = $_POST['status'];
				$data['desc'] = $_POST['desc'];
				
				$re = $this->node_list->data($data)->add();
				if($re > 0){
					$ret = array("result"=>1, "id"=>$re, "reason"=>"添加成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"添加失败，请联系管理员！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}*/
	
	public function deleteFile(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
			      $List = M("file");
			      $condition['id'] = $_POST['id'];
			      $re = $List->where($condition)->delete();
			      if($re == 1){
				      WriteOptLog("删除", "文件： ".$_POST['id']);
				      $ret = array("result"=>1, "reason"=>"删除成功！");
			      }else{
				      $ret = array("result"=>0, "reason"=>"删除失败，请联系管理员！");
			      }
			      echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function delete(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
				$this->__init();
				//$List = M("list");
				$condition['id'] = $_POST['id'];
				$re = $this->node_list->where($condition)->delete();
				if($re == 1){
					WriteOptLog("删除", "设备： ".$_POST['id']);
					$ret = array("result"=>1, "reason"=>"删除成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"删除失败，请联系管理员！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function delTask(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
			      $TList = M("task");
			      $Flist = M("task_feedback");
			      $tcondition['id'] = $_POST['id'];
			      $fcondition['taskid'] = $_POST['id'];
			      $tre = $TList->where($tcondition)->delete();
			      $fre = $Flist->where($fcondition)->delete();
			      if($tre && $fre){
				      WriteOptLog("删除", "任务： ".$_POST['id']);
				      $ret = array("result"=>1, "reason"=>"删除成功！");
			      }else{
				      $ret = array("result"=>0, "reason"=>"删除失败，请联系管理员！");
			      }
			      echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function addTask(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
			      $TList = M("task");
			      $Flist = M("task_feedback");
			      $sidarr = $_POST['sidarr'];
			      
			      $task['name'] = $_POST['name'];
			      $task['ntype'] = $_SESSION['zone'];
			      $task['type'] = $_POST['type'];
			      $task['timeout'] = $_POST['time'];
			      $task['retry'] = $_POST['num'];
			      $task['data'] = $_POST['exe'];
			      $task['status'] = $_POST['status'];
			      
			      $re = $TList->data($task)->add();
			      if($re > 0){
				      $arr = explode(',', $sidarr);
				      foreach ($arr as $ip) {
					  $feedback['taskid'] = $re;
					  $feedback['sid'] = $ip;
					  $feedback['timeout'] = $_POST['time'];
					  $feedback['retry'] = $_POST['num'];
					  $feedback['status'] = "ready";
					  $Flist->data($feedback)->add();
				      }
				      WriteOptLog("添加", "发送任务： ".$task['name']."，命令：".$task['data']."，设备：".$sidarr);
				      $ret = array("result"=>1, "id"=>$re, "reason"=>"任务已经成功发送！");
			      }else{
				      $ret = array("result"=>0, "reason"=>"任务发送失败，请联系管理员！");
			      }
			      echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function login(){
		if($_SERVER['REQUEST_METHOD' ] === 'GET'){
			$this->__init();
			$glist = $this->node_list->Distinct(true)->field('type')->select();
			$this->assign('typelist', $glist);
			$this->display();
		}
		else{
			if(!isset($_POST['u']) || !isset($_POST['p'])){
				$ret = array("result"=>0, "reason"=>"用户名和密码不能为空！");
			}else{
				$condition['user'] = $_POST['u'];
				$condition['pwd'] = $_POST['p'];
				$condition['type'] = $_POST['t'];
				$AList = M("admin");
				$re = $AList->where($condition)->count();
				//print_r($_POST['t']); exit;
				if($re == 1){
					$_SESSION['user'] = $_POST['u'];
					if(strpos($_POST['t'],"admin") > 0 || $_POST['t']=="admin"){
						$_SESSION['type'] = "admin";
					}else{
						$_SESSION['type'] = "cacti";
					}

					$arr = explode('|', $_POST['t']);
					$_SESSION['zone'] = $arr[0];
					if($_SESSION['type'] == "admin"){
						$ret = array("result"=>1, "reason"=>"登陆成功！");
					}else{
						$ret = array("result"=>2, "reason"=>"登陆成功！");
					}
					WriteOptLog("登陆", "身份：".$_SESSION['zone']."|".$_SESSION['type']);
				}else{
					$ret = array("result"=>0, "reason"=>"用户名或密码错误！");
				}
			}
			echo json_encode($ret);
		}
	}
	
	public function system(){
		if(isset($_SESSION['user'])){
			$AList = M("admin");
			$list = $AList->order('id desc')->select();
			foreach($list as $n=>$val){
					$arr = explode('|', $list[$n]['type']);
					if(count($arr) == 2){
						$list[$n]['type'] = $arr[1];
						$list[$n]['zone'] = $arr[0];
					}else{
						$list[$n]['type'] = $list[$n]['type'];
						$list[$n]['zone'] = "all";
					}
				}
				
			$this->__init();
			if($_SESSION['zone'] == "all"){
				$glist = $this->node_list->Distinct(true)->field('type')->select();
			}else{
				$condition['type'] = $_SESSION['zone'];
				$glist = $this->node_list->Distinct(true)->field('type')->where($condition)->select();
			}
			$this->assign('glist', $glist);
			$this->assign('ulist', $list);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function optlog(){
		if(isset($_SESSION['user'])){
			$List = M("optlog");
			import('ORG.Util.Page');// 导入分页类
			if($_SESSION['zone'] == "all"){
				if(isset($_GET['type'])){
					$condition["opt_type"] = $_GET['type'];
					$count = $List->where($condition)->count(); //总条数
					$Page = new Page($count, 50);
					$nowPage = isset($_GET['p'])?$_GET['p']:1;
					$optlist = $List->order('id desc')->page($nowPage.','.$Page->listRows)->where($condition)->select();
				}else{
					$count = $List->count(); 
					$Page = new Page($count, 50);
					$nowPage = isset($_GET['p'])?$_GET['p']:1;
					$optlist = $List->order('id desc')->page($nowPage.','.$Page->listRows)->select();
				}
			}else{
				if(isset($_GET['type'])){
					$condition["opt_type"] = $_GET['type'];
				}
				$condition["opt_zone"] = $_SESSION['zone'];
				$count = $List->where($condition)->count(); //总条数
				$Page = new Page($count, 50);
				$nowPage = isset($_GET['p'])?$_GET['p']:1;
				$optlist = $List->order('id desc')->page($nowPage.','.$Page->listRows)->where($condition)->select();
			}
			$this->assign('optlist', $optlist);
			$show = $Page->show();// 分页显示输出
			$this->assign('page',$show);// 赋值分页输出
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function addAttr(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
				$List = M("baseinfo_keys");
				$data['key'] = $_POST['key'];
				$data['name'] = $_POST['name'];
				
				$re = $List->data($data)->add();
				if($re > 0){
					WriteOptLog("添加", "设备属性： ".$data['key']."，".$data['name']);
					$ret = array("result"=>1, "id"=>$re, "reason"=>"添加成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"添加失败，请联系管理员！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function alarm(){
		if(isset($_SESSION['user'])){
			$rList = M("alarm_rule");
			$list = $rList->order('id desc')->select();
			$this->assign('rulelist', $list);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function addRule(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
				$List = M("alarm_rule");
				$data['rule'] = $_POST['act'];
				$data['type'] = $_POST['type'];
				$data['top'] = $_POST['top'];
				$data['status'] = $_POST['status'];
				
				$re = $List->data($data)->add();
				if($re > 0){
					WriteOptLog("添加", "报警策略： ".$data['rule']."，".$data['type']."，".$data['top']);
					$ret = array("result"=>1, "id"=>$re, "reason"=>"添加成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"添加失败，请联系管理员！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function delRule(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
			      $rList = M("alarm_rule");
			      $alist = M("alarm_action");
			      $list = M("alarm");
			      
			      $rcondition['id'] = $_POST['id'];
			      $acondition['rid'] = $_POST['id'];
			      
			      $rre = $rList->where($rcondition)->delete();
			      $alist->where($acondition)->delete();
			      $list->where($acondition)->delete();
			      if($rre){
				      WriteOptLog("删除", "报警策略： ".$_POST['id']);
				      $ret = array("result"=>1, "reason"=>"删除成功！");
			      }else{
				      $ret = array("result"=>0, "reason"=>"删除失败，请联系管理员！");
			      }
			      echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function turnRule(){
		if(isset($_SESSION['user'])){
			if(isset($_POST['id'])){
				$alist = M("alarm_rule");
				$condition['id'] = $_POST['id'];
				$data['status'] = $_POST['status'];
				//print_r($data['status']);exit;
				$re = $alist->where($condition)->data($data)->save(); // 根据条件保存修改的数据
				if($re == 1){
					WriteOptLog("修改", "修改策略状态：".$_POST['id']."，状态为： ".$_POST['status']);
					$ret = array("result"=>1, "reason"=>"设置成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"设置失败！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function readRule(){
		if(isset($_SESSION['user'])){
			$d_list = M("alarm_rule");
			$condition['type'] = $_POST['type'];
			$device = $d_list->where($condition)->select();
			echo json_encode($device);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function readAction(){
		if(isset($_SESSION['user'])){
			$d_list = M("alarm_action");
			$condition['rid'] = $_POST['id'];
			$device = $d_list->where($condition)->select();
			echo json_encode($device);
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function addAction(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
				$List = M("alarm_action");
				$data['rid'] = $_POST['rid'];
				$data['person'] = $_POST['person'];
				$data['way'] = $_POST['way'];
				$data['action'] = $_POST['action'];
				$data['status'] = $_POST['status'];
				
				$re = $List->data($data)->add();
				if($re > 0){
					WriteOptLog("添加", "报警接收人： ".$data['person']."，".$data['action']);
					$ret = array("result"=>1, "id"=>$re, "reason"=>"添加成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"添加失败，请联系管理员！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function turnAction(){
		if(isset($_SESSION['user'])){
			if(isset($_POST['id'])){
				$alist = M("alarm_action");
				$condition['id'] = $_POST['id'];
				$data['status'] = $_POST['status'];
				//print_r($data['status']);exit;
				$re = $alist->where($condition)->data($data)->save(); // 根据条件保存修改的数据
				if($re == 1){
					WriteOptLog("修改", "修改报警接收人：".$_POST['id']."，状态为： ".$_POST['status']);
					$ret = array("result"=>1, "reason"=>"设置成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"设置失败！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function delAction(){
		if(isset($_SESSION['user'])){
			if(isset($_POST['id'])){
				$alist = M("alarm_action");
				$condition['id'] = $_POST['id'];
				$re = $alist->where($condition)->delete();
				if($re){
					WriteOptLog("删除", "报警接收人： ".$_POST['id']);
					$ret = array("result"=>1, "reason"=>"删除成功！");
				}else{
					$ret = array("result"=>0, "reason"=>"删除失败，请联系管理员！");
				}
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function addUser(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
			      $aList = M("admin");
			      
			      $task['user'] = $_POST['user'];
			      $task['type'] = $_POST['type'];
			      $task['pwd'] = $_POST['pwd'];
			      $task['desc'] = $_POST['desc'];
			      
			      $re = $aList->data($task)->add();
			      //echo $aList->getlastsql();exit;
			      if($re > 0){
				      WriteOptLog("添加", "用户名： ".$task['user']."，用户类型：".$task['type']);
				      $ret = array("result"=>1, "id"=>$re, "reason"=>"添加用户成功！");
			      }else{
				      $ret = array("result"=>0, "reason"=>"添加用户失败，请联系管理员！");
			      }
			      echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function editUser(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
			      $aList = M("admin");
			      $condition['id'] = $_POST['id'];
			      $data['user'] = $_POST['user'];
			      $data['type'] = $_POST['type'];
			      $data['pwd'] = $_POST['pwd'];
			      $data['desc'] = $_POST['desc'];
			      
			      $re = $aList->where($condition)->data($data)->save(); // 根据条件保存修改的数据
			      //echo $List->getlastsql();exit;
			      if($re == 1){
				      WriteOptLog("修改", "用户： ".$_POST['user']);
				      $ret = array("result"=>1, "reason"=>"修改成功！");
			      }else{
				      $ret = array("result"=>0, "reason"=>"没有改动的数据！");
			      }
			      echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function deleteUser(){
		if(isset($_SESSION['user'])){
			if($_SERVER['REQUEST_METHOD' ] === 'POST'){
			      $aList = M("admin");
			      $condition['id'] = $_POST['id'];
			      $re = $aList->where($condition)->delete();
			      if($re == 1){
				      WriteOptLog("删除", "用户： ".$_POST['id']);
				      $ret = array("result"=>1, "reason"=>"删除成功！");
			      }else{
				      $ret = array("result"=>0, "reason"=>"删除失败，请联系管理员！");
			      }
			      echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function attrMgr(){
		if(isset($_SESSION['user'])){
			$rList = M("baseinfo_keys");
			$list = $rList->order('id desc')->select();
			$this->assign('attrlist', $list);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	 
	public function logout(){
		WriteOptLog("退出", "身份：".$_SESSION['zone']."|".$_SESSION['type']);
		unset($_SESSION['user']);
		$ret = array("result"=>1, "reason"=>"ok");
		echo json_encode($ret);
	}
}

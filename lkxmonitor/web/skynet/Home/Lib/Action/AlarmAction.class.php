<?php
// 本类由系统自动生成，仅供测试用途
class AlarmAction extends Action {

	public $node_list;
	public function __init(){
		if(empty($this->node_list)){
			$this->node_list = M('list');
		}
	}

	public function talarm(){
		if(isset($_SESSION['user'])){
			$List = M("alarm_rule");
			$rlist = $List->query('SELECT * FROM `node_alarm_rule` WHERE (`type` = \'out\' or `type`=\'in\') ORDER BY id desc');
			//echo $List->getlastsql();exit; 
			$this->assign('rlist', $rlist);
			
			$alist = M("alarm");
			$sid = $_GET['sid'];
			//print_r($sid);exit;
			$alarmlist = $alist->query("select node_alarm.id, node_alarm.sid, node_alarm.rid, node_alarm.key, node_alarm.level, node_alarm.timespan, node_alarm.status, node_alarm_rule.type, node_alarm_rule.rule from node_alarm left join node_alarm_rule on node_alarm.rid=node_alarm_rule.id where node_alarm.sid='$sid'");  // and (node_alarm_rule.type='in' or node_alarm_rule.type='out')
			//echo $alist->getlastsql();exit;
			$v_list = M("baseinfo_kv");
			$condition1['sid'] = $_GET['sid'];
			$condition1['key'] = 'ifDescr';
			$name = $v_list->where($condition1)->getField('value');
			//print_r($name);exit;
			$this->assign('namelist', $name);
			$this->assign('alarmlist', $alarmlist);
			$this->assign('sid', $_GET['sid']);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function dalarm(){
		if(isset($_SESSION['user'])){
			$List = M("alarm_rule");
			$rlist = $List->query('SELECT * FROM `node_alarm_rule` WHERE (`type` = \'disk\') ORDER BY id desc');
			//echo $List->getlastsql();exit; 
			$this->assign('rlist', $rlist);
			
			$alist = M("alarm");
			$sid = $_GET['sid'];
			//print_r($sid);exit;
			$alarmlist = $alist->query("select node_alarm.id, node_alarm.sid, node_alarm.rid, node_alarm.key, node_alarm.level, node_alarm.timespan, node_alarm.status, node_alarm_rule.type, node_alarm_rule.rule from node_alarm left join node_alarm_rule on node_alarm.rid=node_alarm_rule.id where node_alarm.sid='$sid'");  // and (node_alarm_rule.type='in' or node_alarm_rule.type='out')
			//echo $alist->getlastsql();exit;
			$v_list = M("baseinfo_kv");
			$condition1['sid'] = $_GET['sid'];
			$condition1['key'] = 'disks';
			$name = $v_list->where($condition1)->getField('value');
			//print_r($name);exit;
			$this->assign('namelist', $name);
			$this->assign('alarmlist', $alarmlist);
			$this->assign('sid', $_GET['sid']);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function salarm(){
		if(isset($_SESSION['user'])){
			$List = M("alarm_rule");
			$rlist = $List->query('SELECT * FROM `node_alarm_rule` WHERE (`type` = \'cpu\') ORDER BY id desc');
			//echo $List->getlastsql();exit; 
			$this->assign('rlist', $rlist);
			
			$alist = M("alarm");
			$sid = $_GET['sid'];
			//print_r($sid);exit;
			$alarmlist = $alist->query("select node_alarm.id, node_alarm.sid, node_alarm.rid, node_alarm.level, node_alarm.timespan, node_alarm.status, node_alarm_rule.type, node_alarm_rule.rule from node_alarm left join node_alarm_rule on node_alarm.rid=node_alarm_rule.id where node_alarm.sid='$sid'");
			//echo $alist->getlastsql();exit;
			$this->assign('alarmlist', $alarmlist);
			$this->assign('sid', $_GET['sid']);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function malarm(){
		if(isset($_SESSION['user'])){
			$List = M("alarm_rule");
			$rlist = $List->query('SELECT * FROM `node_alarm_rule` WHERE (`type` = \'mem\') ORDER BY id desc');
			//echo $List->getlastsql();exit; 
			$this->assign('rlist', $rlist);
			
			$alist = M("alarm");
			$sid = $_GET['sid'];
			//print_r($sid);exit;
			$alarmlist = $alist->query("select node_alarm.id, node_alarm.sid, node_alarm.rid, node_alarm.level, node_alarm.timespan, node_alarm.status, node_alarm_rule.type, node_alarm_rule.rule from node_alarm left join node_alarm_rule on node_alarm.rid=node_alarm_rule.id where node_alarm.sid='$sid'");
			//echo $alist->getlastsql();exit;
			$this->assign('alarmlist', $alarmlist);
			$this->assign('sid', $_GET['sid']);
			$this->display();
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function addAlarm(){
		if(isset($_SESSION['user'])){
			if(isset($_POST['sid'])){
				$list = M("alarm");
				$data['sid'] = $_POST['sid'];
				$data['key'] = isset($_POST['key'])?$_POST['key']:"";
				$data['rid'] = $_POST['rid'];
				$data['level'] = $_POST['level'];
				$data['timespan'] = $_POST['span'];
				$data['status'] = $_POST['status'];
				$list->data($data)->add();
				WriteOptLog("添加", "添加报警：".$_POST['sid']."，".$_POST['rid']."，".$_POST['level']."，".$_POST['timespan']);
				$ret = array("result"=>1, "reason"=>"添加成功！");
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	
	public function delAlarm(){
		if(isset($_SESSION['user'])){
			if(isset($_POST['id'])){
				$alist = M("alarm");
				$condition['id'] = $_POST['id'];
				$alist->where($condition)->delete();
				WriteOptLog("删除", "删除报警：".$_POST['id']);
				$ret = array("result"=>1, "reason"=>"删除成功！");
				echo json_encode($ret);
			}
		}else{
			header("Location: ".__APP__."/Index/login");
		}
	}
	public function turnAlarm(){
		if(isset($_SESSION['user'])){
			if(isset($_POST['id'])){
				$alist = M("alarm");
				$condition['id'] = $_POST['id'];
				$data['status'] = $_POST['status'];
				//print_r($data['status']);exit;
				$re = $alist->where($condition)->data($data)->save(); // 根据条件保存修改的数据
				if($re == 1){
					WriteOptLog("修改", "修改报警：".$_POST['id']."，状态为： ".$_POST['status']);
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
	
}

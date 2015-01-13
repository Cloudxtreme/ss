<?php
	function WriteOptLog($type, $obj){
		$List = M("optlog");
		$data['opt_zone'] = $_SESSION['zone'];
		$data['opt_time'] = date("Y-m-d H:i:s", time());
		$data['opt_user'] = $_SESSION['user'];
		$data['opt_type'] = $type;
		$data['opt_obj'] = $obj;
				
		$re = $List->data($data)->add();
		
	}
	
	/**
	+----------------------------------------------------------
	* 合并两个数组函数
	+----------------------------------------------------------
	* @access public
	+----------------------------------------------------------
	* @param array $list1 数组名
	* @param array $list2 数组名
	* $username=MergeArray($list1,$list2);
	+----------------------------------------------------------
	* @return array
	+----------------------------------------------------------
	*/
	function MergeArray($list1,$list2){
		if(!empty($list1) && !empty($list2)) 
		{
			return array_merge($list1,$list2);
		}
		else return (empty($list1)?(empty($list2)?null:$list2):$list1);
	}
	
	/**
	+----------------------------------------------------------
	* 单条件查询数据库函数
	+----------------------------------------------------------
	* @access public
	+----------------------------------------------------------
	* @param string $table 查询表名
	* @param string $fields 返回的字段名
	* @param string $id  条件字段的值
	* @param string $str 条件字段的名称
	* $username=Getx2x('User','username','12'); 或者
	* $username=Getx2x('User','username','12','id');
	+----------------------------------------------------------
	* @return string or array
	+----------------------------------------------------------
	*/
	function Getx2x($table, $fields, $id, $str){
		$tb=M($table);
		if(empty($str)){
			$expression='getByid';
		}else{
			$expression='getBy'.$str;
		}
		$rlt = $tb->field($fields)->$expression($id);
		$arr = explode(',',$fields);
		if(count($arr) <= 1){
			return $rlt[$fields];
		}else{
			return $rlt;
		}
	}
	
	/**
	+----------------------------------------------------------
	* 对查询结果集进行排序
	+----------------------------------------------------------
	* @access public
	+----------------------------------------------------------
	* @param array $list 查询结果
	* @param string $field 排序的字段名
	* @param string $sortby 排序类型
	* asc正向排序 desc逆向排序 nat自然排序
	+----------------------------------------------------------
	* @return array
	+----------------------------------------------------------
	*/
	function list_sort_by($list, $field, $sortby='asc') {
		if(is_array($list)){
			$refer = $resultSet = array();
			foreach ($list as $i => $data)
				$refer[$i] = &$data[$field];
			switch ($sortby) {
				case 'asc': // 正向排序
					asort($refer);
					break;
				case 'desc':// 逆向排序
					arsort($refer);
					break;
				case 'nat': // 自然排序
					natcasesort($refer);
					break;
			}
			foreach ( $refer as $key=> $val)
				$resultSet[] = &$list[$key];
			return $resultSet;
		}
		return false;
	}
?>
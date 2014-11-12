<?php

	require_once('../db.php');

	function get_task($dbobj, $nodeip, $task_type)
        {
                $node_task = array();
                $sql = "select id,task_from_id,task_type from node_task where ip=(select distinct match1 from server_list where ip='$nodeip') and task_type='$task_type' and status='ready' limit 0,1;";
		//$up_sql = "update node_task set task_start_time=now(),task_finish_time=NULL,status='doing' where ip='$nodeip' and task_type='$task_type' and status='ready';";

                if( ! ($result = db_query($dbobj, $sql)) )
                {
                        print($dbobj->error());
                        return false;
                }
                while( ($row = mysql_fetch_array($result)) )
                {
                        $id = $row['id'];
                        $task_from_id = $row['task_from_id'];
			$up_sql = "update node_task set task_start_time=now(),task_finish_time=NULL,status='doing' where id=$id and status='ready';";

                        //echo "$id--$task_from_id\n";

                        $node_task[$task_from_id] = $id;
			db_query($dbobj, $up_sql);
                }
                mysql_free_result($result);
		//db_query($dbobj, $up_sql);
                return $node_task;
        }

	function get_task_node_type($dbobj, $task_id)
	{
		$ret = false;
		$sql = "select node_type from node_task where id=$task_id;";
		if(($result = db_query($dbobj, $sql)))
		{
			if(($row = mysql_fetch_array($result)))
			{
				$node_type = $row['node_type'];
				$ret = $node_type;
			}
			mysql_free_result($result);
		}
		return $ret;
	}

	function build_loopcmd($cmd)
	{
		$loop_cmd = "$cmd\n";
		$loop_cmd .= "while [ $? -ne 0 ];\n";
		$loop_cmd .= "do\n";
		$loop_cmd .= "\tsleep 3\n";
		$loop_cmd .= "\t$cmd\n";
		$loop_cmd .= "done\n";
		return $loop_cmd;
	}
?>

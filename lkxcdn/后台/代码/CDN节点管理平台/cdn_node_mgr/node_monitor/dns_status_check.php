<?php
	//中文UTF-8
	require_once('db.php');

	function record_exists($dbobj, $table, $domain_name, $node)
	{
		$ret = false;
		$sql = "select * from `$table` where name='$domain_name' and rdata='$node';";
		if( ($result = db_query($dbobj, $sql)) )
		{
			if($row = mysql_fetch_array($result))
			{
				$ret = true;
			}
			mysql_free_result($result);
		}
		return $ret;
	}

	function check_backup_ifneed($dbobj, $table, $domain_name, $node)
	{
		$ret = true;
		$sql = "select * from `$table` where name='$domain_name' and rdtype='A' and status='true' and rdata!='$node';";
		if( ($result = db_query($dbobj, $sql)) )
                {
                        if($row = mysql_fetch_array($result))
                        {
                                $ret = false;
                        }
                        mysql_free_result($result);
                }
                return $ret;
	}

	function get_node_tables($dbobj, $ip)
	{
		$tables = array();
		$sql_node_locate = "select distinct tablename from ip_list where rdata = '$ip' and status = 'true';";

                if( ($result = db_query($dbobj, $sql_node_locate)) )
                {
                        while( ($row = mysql_fetch_array($result)) )
                        {
                                $tablename = $row['tablename'];
				$tables[] = $tablename;
                        }

                        mysql_free_result($result);
                }
		return $tables;
	}

	function get_node_domains($dbobj, $ip)
	{
		$domains = array();
		$sql_node_locate = "select distinct dnsid from ip_list where rdata = '$ip' and status = 'true';";

                if( ($result = db_query($dbobj, $sql_node_locate)) )
                {
                        while( ($row = mysql_fetch_array($result)) )
                        {
                                $dnsid = $row['dnsid'];
                                $domains[] = $dnsid;
                        }

                        mysql_free_result($result);
                }
                return $domains;
	}

	function get_node_record($dbobj, $ip)
	{
		$records = array();
		$sql_node_locate = "select id,dnsid,ttl,rdtype,tablename from ip_list where rdata = '$ip' and status = 'true';";

		if( ($result = db_query($dbobj, $sql_node_locate)) )
                {
                        while( ($row = mysql_fetch_array($result)) )
                        {
                                $dnsid = $row['dnsid'];
				$ttl = $row['ttl'];
				$rdtype = $row['rdtype'];
				$tablename = $row['tablename'];
                                
				$records[$id]['dnsid'] = $dnsid;
				$records[$id]['ttl'] = $ttl;
				$records[$id]['rdtype'] = $rdtype;
				$records[$id]['tablename'] = $tablename;
                        }
                        mysql_free_result($result);
                }
                return $records;
	}

	function get_domain_list($dbobj)
	{
		$domain_list = array();
		$query = "select id,name from dns_list where status='true';";
		
		if( ! ($result = db_query($dbobj, $query)) )
                {
                         //print($info_dbobj->error());
                         return false;
                }

                while( ($row = mysql_fetch_array($result)) )
                {
                        $id = $row['id'];
			$name = $row['name'];
                        $domain_list[$id] = $name;
                }
                mysql_free_result($result);
		return $domain_list;
	}

	function add_backup_node($info_dbobj, $dbobj, $ip, $nettype, $zone, $local)
	{
		$backup_node = array();
		$backup_vals = "";
		$query = "select ip from server_list_tmp where status='true' and type='node' and nettype = '$nettype';";
		$sql = "";
		
		$node_tables = get_node_tables($dbobj, $ip);
		$node_domains = get_node_domains($dbobj, $ip);
		//$node_records = get_node_records($dbobj, $ip);
		$domain_list = get_domain_list($dbobj);

		if( ! ($result = db_query($info_dbobj, $query)) )
                {
                         printf("$dbobj->error() \n");
                         return false;
                }

                while( ($row = mysql_fetch_array($result)) )
                {
                        $backup_ip = $row['ip'];
                        $backup_node[] = $backup_ip;
                }
                mysql_free_result($result);

		foreach($node_tables as $table)
		{
			$sql_a = "insert into `$table`(`name`,`ttl`,`rdtype`,`rdata`,`status`,`desc`) values ";
			$backup_vals = "";
			foreach($node_domains as $dnsid)
			{
				if(!array_key_exists($dnsid, $domain_list)) continue;
				$domain_name = $domain_list[$dnsid];
				if(!check_backup_ifneed($dbobj, $table, $domain_name, $ip))
				{
					printf("$domain_name do not have to backup\n\n");
					continue;
				}
				foreach($backup_node as $node)
				{
					if(record_exists($dbobj, $table, $domain_name, $node) == false)
					{
						if(!strlen($backup_vals))
							$backup_vals = "('$domain_name',300,'A','$node','true','backup')";
						else
							$backup_vals .= ",('$domain_name',300,'A','$node','true','backup')";
					}
				}
			}
			if(strlen($backup_vals))
			{
				$sql_a .= "$backup_vals;";
				db_sql_exec($dbobj, $sql_a, 1);
				//printf("sql: %s\n", $sql_a);
			}
		}
	}

	function del_backup_node($dbobj, $ip)
	{
		$sql = "";

                $node_tables = get_node_tables($dbobj, $ip);
                
                foreach($node_tables as $table)
                {
                                $sql = "delete from `$table` where `desc`='backup';";

                                db_sql_exec($dbobj, $sql, 1);

                                //printf("sql : %s\n", $sql);
                }
	}
	
	function update_dnstable($dbobj, $ip, $status)
        {
                $sql = "";

                $node_tables = get_node_tables($dbobj, $ip);

                foreach($node_tables as $table)
                {
                                $sql = "update `$table` set status='$status' where rdata='$ip';";

                                db_sql_exec($dbobj, $sql, 1);

                                //printf("sql : %s\n", $sql);
                }
        }

	function dns_check_node($info_dbobj, $dns_dbobj)
	{
		$query = "select id,ip,last_status,status,nettype,zone,local from server_list_tmp where type='node' and nettype!='移动';";
		if( ! ($result = db_query($info_dbobj, $query)) ) 
		{
	   		 //print($info_dbobj->error()); 
	   		 return false;
		}

		while( ($row = mysql_fetch_array($result)) )
		{
			$id = $row['id'];
	      	 	$ip = $row['ip'];
			$last_status = $row['last_status'];
       			$status = $row['status'];
	       		$nettype = $row['nettype'];
	       		$zone = $row['zone'];
	       		$local = $row['local'];

			if($last_status != $status)
			{
				$u_sql = "update server_list_tmp set last_status='$status' where id=$id;";
				switch($status)
				{
					case "true":
						printf("del backup\n");
						del_backup_node($dns_dbobj, $ip);
						break;
					case "false":
						printf("add backup\n");
						add_backup_node($info_dbobj, $dns_dbobj, $ip, $nettype, $zone, $local);
						break;
					default:;
				}
				update_dnstable($dns_dbobj, $ip, $status);
				db_sql_exec($info_dbobj, $u_sql, 1);
				//printf("$u_sql \n");
			}
		}
		mysql_free_result($result);
	}

	function dns_check_records($info_dbobj, $dns_dbobj)
	{
		$sql = "select id,dnsid,ttl,rdtype,rdata,tablename,front_end from ip_list where status='true' and front_end!=back_end;";
		$sqls = array();

		$domain_list = get_domain_list($dns_dbobj);

		if( ($result = db_query($dns_dbobj, $sql)) )
                {
                        while( ($row = mysql_fetch_array($result)) )
                        {
                                $id = $row['id'];
				$dnsid = $row['dnsid'];
				$ttl = $row['ttl'];
				$rdtype = $row['rdtype'];
				$rdata = $row['rdata'];
				$tablename = $row['tablename'];
				$front_end = $row['front_end'];
		

				$u_sql = "update `ip_list` set back_end='$front_end' where id=$id;";
				db_sql_exec($dns_dbobj, $u_sql, 1);
				//printf("$u_sql \n");

				if(!array_key_exists($dnsid, $domain_list)) continue;
				$domain_name = $domain_list[$dnsid];

				switch($front_end)
				{
					case "add":
						if(record_exists($dns_dbobj, $tablename, $domain_name, $rdata) == false)
						{
							if(!array_key_exists($tablename, $sqls))
								$sqls[$tablename] = "insert into `$tablename`(`name`,`ttl`,`rdtype`,`rdata`,`status`)
											values ('$domain_name',$ttl,'A','$rdata','true')";
							else
								$sqls[$tablename] .= ",('$domain_name',$ttl,'A','$rdata','true')";
						}
						break;
					case "del":
						$d_sql = "delete from `$tablename` where 
								name='$domain_name' and ttl=$ttl and rdtype='A' and rdata='$rdata';";
						db_sql_exec($dns_dbobj, $d_sql, 1);
						//printf("$d_sql \n");
						break;
					default:;
				}
                        }
                        mysql_free_result($result);

			foreach($sqls as $table => $sql)
                	{
				$sql .= ";";
                        	db_sql_exec($dns_dbobj, $sql, 1);
                        	//printf("$sql \n");
                	}
                }
	}

	//begin
	global $cdninfo_ip;
	global $cdninfo_user;
	global $cdninfo_pass;
	global $cdninfo_web_database;

	global $cdndns_ip;
	global $cdndns_user;
	global $cdndns_pass;
	global $cdndns_database;

	$info_dbobj = db_gethandle($cdninfo_ip, $cdninfo_user, $cdninfo_pass, $cdninfo_web_database);
	$dns_dbobj = db_gethandle($cdndns_ip, $cdndns_user, $cdndns_pass, $cdndns_database);
	//while(1)
	{
	        dns_check_node($info_dbobj, $dns_dbobj);
		dns_check_records($info_dbobj, $dns_dbobj);
	        //sleep(1);
	}
?>


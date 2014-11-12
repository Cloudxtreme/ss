<?php

	require_once('db.php');

	function compress_portrate()
	{
		$do_max_id = 0;
		$ip_nettype = array();
                $dbobj_cdninfo = db_gethandle("cdninfo.efly.cc", "root", "rjkj@rjkj", "cdn_web");
                $dbobj_local = db_gethandle("localhost", "root", "rjkj@rjkj", "cdn_portrate_stats");

                if((!$dbobj_cdninfo) || (!$dbobj_local))
                {
                        echo "[compress_portrate] conn db error!\n";
                        return;
                }

                $yesday = date("Y-m-d",strtotime("-1 day"));
		$today = date("Y-m-d");
		$hh = date("H");
		$mm = date("i");
		if(($hh == "00") && ($mm < "10"))
			$today = $yesday;

                $source_tablename = $today;
                $tablename = "${today}";
		$tmp_now = "${today}-now";


		$s_sql = "select max(id) from `${tmp_now}`;";
		if($result = db_query($dbobj_local, $s_sql))
		{
			$row = mysql_fetch_array($result);
			$do_max_id = $row['max(id)'];
			mysql_free_result($result);
		}
		if(!$do_max_id)
			return;

				$all_ip_list = "";
                $s_sql = "select ip, nettype from server_list where type='cdn_stats' and status='true';";
                if($result = db_query($dbobj_cdninfo, $s_sql))
                {
                        while($row = mysql_fetch_array($result))
                        {
                                $ip = $row['ip'];
                                $nettype = $row['nettype'];
								if(!strlen($all_ip_list))
									$all_ip_list = "'$ip'";
								else
									$all_ip_list .= ",'$ip'";
                                if(!isset($ip_nettype["$nettype"]))
                                        $ip_nettype["$nettype"] = "'$ip'";
                                else
                                        $ip_nettype["$nettype"] .= ",'$ip'";
                        }
			mysql_free_result($result);
                }
				if(strlen($all_ip_list))
                {
                	$u_sql2 = "update `${tmp_now}` set ip='移动' where ip not in ($all_ip_list) and id<=${do_max_id};";
                    db_query($dbobj_local, $u_sql2);
                }
                foreach($ip_nettype as $nettype=>$ip_list)
                {
                        $u_sql = "update `${tmp_now}` set ip='$nettype' where ip in ($ip_list) and id<=${do_max_id};";
						db_query($dbobj_local, $u_sql);
                }

		$c_sql = "create table if not exists `$tablename`(
				id int(11) primary key auto_increment,
				ip char(20),
				hostname char(100),
				outrate bigint,
				inrate bigint,
				`time` time,
				UNIQUE KEY `ip_hostname_time` (`ip`,`hostname`,`time`)
				)ENGINE=MyISAM DEFAULT CHARSET=utf8;";

		$bk_sql = "CREATE TABLE IF NOT EXISTS `${tablename}-bak` (
  				`id` int(11) NOT NULL auto_increment,
  				`ip` char(20) NOT NULL,
  				`hostname` char(100) NOT NULL,
  				`port` int(11) NOT NULL,
  				`outrate` bigint(20) NOT NULL,
  				`inrate` bigint(20) NOT NULL,
  				`time` time NOT NULL,
  				PRIMARY KEY  (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8";

		$de_sql = "delete from `${tmp_now}` where id<=${do_max_id};";

		$i_sql = "insert into `${tablename}`(ip,hostname,outrate,inrate,`time`)
				select ip,hostname,sum(outrate),sum(inrate),concat(cast(hour(time) as char),':',cast(minute(time) div 5 \052 5 as char),':00')
				from `${tmp_now}` where id<=${do_max_id} group by ip,hostname,hour(time),minute(time) div 5
				on duplicate key update outrate=outrate+values(outrate),inrate=inrate+values(inrate);";

		$ibk_sql = "insert into `${tablename}-bak` select * from `${tmp_now}`;";

		//$an_sql = "alter table `$tablename` rename to `$source_tablename`;";
		$dr_sql = "drop table `${tmp_now}`;";

		db_query($dbobj_local, $c_sql);
		//db_query($dbobj_local, $bk_sql);
		db_query($dbobj_local, $i_sql);
		//db_query($dbobj_local, $ibk_sql);
		db_query($dbobj_local, $de_sql);
		if(($hh == "00") && ($mm < "10"))
			db_query($dbobj_local, $dr_sql);
		//db_query($dbobj_local, $an_sql);
	}


	function compress_traffic()
	{
                $dbobj_local = db_gethandle("localhost", "root", "rjkj@rjkj", "cdn_client_traffic");

                if(!$dbobj_local)
                {
                        echo "[compress_traffic] conn db error!\n";
                        return;
                }

                $yesday = date("Y-m-d",strtotime("-1 day"));

                $source_tablename = $yesday;
                $tablename = "$yesday-gb1d";

        	$c_sql = "create table if not exists `$tablename`(
				id int(11) primary key auto_increment,
				hostname char(100),
				traffic bigint)ENGINE=MyISAM DEFAULT CHARSET=utf8;";

        	$de_sql = "delete from `$tablename`;";

        	$i_sql = "insert into `$tablename`(hostname,traffic)
				select hostname,sum(traffic)
				from `$source_tablename` group by hostname;";

        	$an_sql = "alter table `$tablename` rename to `$source_tablename`;";
		$dr_sql = "drop table `$source_tablename`;";

		db_query($dbobj_local, $c_sql);
		db_query($dbobj_local, $de_sql);
		db_query($dbobj_local, $i_sql);
		db_query($dbobj_local, $dr_sql);
		db_query($dbobj_local, $an_sql);
	}

	function compress_clienthit()
	{
		$do_max_id = 0;
		$ip_nettype = array();
        	$dbobj_cdninfo = db_gethandle("cdninfo.efly.cc", "root", "rjkj@rjkj", "cdn_web");
		$dbobj_local = db_gethandle("localhost", "root", "rjkj@rjkj", "cdn_client_hit");

		if((!$dbobj_cdninfo) || (!$dbobj_local))
		{
			echo "[compress_clienthit] conn db error!\n";
			return;
		}

        	$yesday = date("Y-m-d",strtotime("-1 day"));
		$today = date("Y-m-d");
		$hh = date("H");
        $mm = date("i");
		if(($hh == "00") && ($mm < "10"))
            $today = $yesday;

        	$source_tablename = $today;
        	$tablename = "$today";
		$tmp_now = "${today}-now";


                $s_sql = "select max(id) from `${tmp_now}`;";
                if($result = db_query($dbobj_local, $s_sql))
                {
                        $row = mysql_fetch_array($result);
                        $do_max_id = $row['max(id)'];
                        mysql_free_result($result);
                }
		if(!$do_max_id)
			return;

		$s_sql = "select ip, nettype from server_list where type='cdn_stats' and status='true';";
		if($result = db_query($dbobj_cdninfo, $s_sql))
		{
			while($row = mysql_fetch_array($result))
			{
				$ip = $row['ip'];
				$nettype = $row['nettype'];
				if(!isset($ip_nettype["$nettype"]))
					$ip_nettype["$nettype"] = "'$ip'";
				else
					$ip_nettype["$nettype"] .= ",'$ip'";
			}
			mysql_free_result($result);
		}
		foreach($ip_nettype as $nettype=>$ip_list)
		{
			$u_sql = "update `${tmp_now}` set ip='$nettype' where ip in ($ip_list) and id<=${do_max_id};";
			db_query($dbobj_local, $u_sql);
		}

        	$c_sql = "create table if not exists `$tablename`(
				id int(11) primary key auto_increment,
				ip char(20),
				hostname char(100),
				cnt int(11),
				sent bigint,
				hit_cnt int(11),
				hit_sent bigint,
				`timestamp` time)ENGINE=MyISAM DEFAULT CHARSET=utf8;";

        	$de_sql = "delete from `${tmp_now}` where id<=${do_max_id};";

        	$i_sql = "insert into `$tablename`(ip,hostname,cnt,sent,hit_cnt,hit_sent,`timestamp`)
				select ip,hostname,sum(cnt),sum(sent),sum(hit_cnt),sum(hit_sent),concat(cast(hour(timestamp) as char),':',cast(minute(timestamp) div 5 \052 5 as char),':00')
				from `${tmp_now}` where id<=${do_max_id} group by ip,hostname,hour(timestamp),minute(timestamp) div 5;";

        	//$an_sql = "alter table `$tablename` rename to `$source_tablename`;";
        	$dr_sql = "drop table `${tmp_now}`;";

		db_query($dbobj_local, $c_sql);
		db_query($dbobj_local, $i_sql);
		db_query($dbobj_local, $de_sql);
		if(($hh == "00") && ($mm < "10"))
			db_query($dbobj_local, $dr_sql);
		//db_query($dbobj_local, $an_sql);
	}

	compress_portrate();
	//compress_traffic();
	compress_clienthit();

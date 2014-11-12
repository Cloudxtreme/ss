<?php
	require_once('db.php');

	function table_add_column($dbobj, $tablename, $column, $type, $len)
	{
		$sql = "alter table `$tablename` add column `$column` $type($len);";

		db_query($dbobj, $sql);

		//printf("$sql\n");
	}

	function get_all_dnstable($dbobj)
	{
		$dnstable = array();
		$sql = "select `tablename` from `zone_table`;";

		if( ! ($result = db_query($dbobj, $sql)) )
        	{
                	print($dbobj->error());
                	return false;
       		}

        	while( ($row = mysql_fetch_array($result)) )
        	{
                	$tablename = $row['tablename'];
                	$dnstable[] = $tablename;
        	}
		mysql_free_result($result);
		return $dnstable;
	}

	global $cdndns_ip;
	global $cdndns_user;
	global $cdndns_pass;
	global $cdndns_database;

	$dbobj = db_gethandle($cdndns_ip, $cdndns_user, $cdndns_pass, $cdndns_database);
	$dnstable = get_all_dnstable($dbobj);

	foreach($dnstable as $table)
	{
		table_add_column($dbobj, $table, "desc", "char", 50);
	}
?>

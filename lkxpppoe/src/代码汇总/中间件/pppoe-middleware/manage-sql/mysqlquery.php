<?php
require_once("/root/config/share/httpsqs_client.php");
class mysqlquery
{
	const QUENE_NAME = "pppoe_mgr_manage_sql";
	const QUENE_SERVER = "mid.pppoe.rightgo.net";
	const QUENE_PORT = 12001;
	const QUENE_FULL_SLEEP = 100;

	public function query($sql)
	{
		$httpsqs = new httpsqs(self::QUENE_SERVER,self::QUENE_PORT);
		$result = $httpsqs->put(self::QUENE_NAME,$sql);
		if(!$result)
		{
			usleep(self::QUENE_FULL_SLEEP);
		}
	}
}
?>

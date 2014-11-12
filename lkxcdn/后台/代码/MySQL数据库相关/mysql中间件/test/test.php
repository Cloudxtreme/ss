<?php

	$str = "1 cdninfo.efly.cc cdn_web insert into server_list values(11)";
	$str_arr = explode(" ", $str);
	$sql = "";
	for($i = 3; $i < count($str_arr); $i++)
	{
		$sql .= $str_arr[$i] . " ";
	}
	echo $sql;
	return;


	for($i = 0; $i < 10; $i++)
	{
		echo $i;
	}
	return;
	$date = date("Y-m-d");
	echo $date;
	echo date("Y-m-d",strtotime("-1 day"));
	return;
	$xml = simplexml_load_file("./test.xml");
	$handle = fopen("./test.xml", "w");
	$xml->last_gen = $xml->last_gen + 1;
	fwrite($handle, $xml->asXML());
	fclose($handle);
	return;


	$now = date('Y-m-d H:i');
	echo $now . "\n";
	return;
	$reg = "/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/";
	preg_match($reg, $now, $arr);
	if($arr)
		print_r($arr);
	else
		echo "false\n";
	return;

	$year = substr($now, 0, 4);
	$mon = substr($now, 5, 2);
	$day = substr($now, 8, 2);
	$hour = substr($now, 11, 2);
	$min = substr($now, 14, 2);

	$nxt = mktime($hour, $min, 0, $mon, $day, $year) + 60;
	echo $now . "\n";
	echo date('Y-m-d h:i', $nxt) . "\n";
?>

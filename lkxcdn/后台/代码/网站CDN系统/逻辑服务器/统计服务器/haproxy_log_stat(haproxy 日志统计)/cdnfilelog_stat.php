<?php
	require_once('db.php');

	ini_set('memory_limit', '-1');

	function parse_each_record($parse_date, $parse_user, $parse_file)
	{
		$file_count = array();
		$file_total = 0;

		$handle = fopen("$parse_file", "r");
		if(!$handle)
		{
			echo "Get file content error!\n";
			return false;
		}

		$file_count["5k"] = 0;
		$file_count["20k"] = 0;
		$file_count["50k"] = 0;
		$file_count["100k"] = 0;
		$file_count["500k"] = 0;
		$file_count["500k+"] = 0;

		while(!feof($handle))
		{
			$record = fgets($handle);
			$file_status = explode(" ", $record);

			if(count($file_status) < 3) continue;

			$http_status = $file_status[9];
			$http_send = $file_status[10];

			if($http_status != 200) continue;

			$file_total += 1;
			if($http_send <= 5120) {$file_count["5k"] += 1;continue;}
			if($http_send <= 20480) {$file_count["20k"] += 1;continue;}
			if($http_send <= 51200) {$file_count["50k"] += 1;continue;}
			if($http_send <= 102400) {$file_count["100k"] += 1;continue;}
			if($http_send <= 512000) {$file_count["500k"] += 1;continue;}
			$file_count["500k+"] += 1;
		}

		echo "Date : $parse_date\tUser : $parse_user\n";
		echo "Request file total : $file_total\n";
		echo "file_size\tcount\tpercent(%)\n";
		foreach($file_count as $file_size=>$count)
		{
			$file_percent = round(($count * 100 / $file_total), 2);
			echo "$file_size\t\t$count\t\t$file_percent%\n";
		}

	}	
	

	$parse_date = $argv[1];
	$parse_user = $argv[2];
	$parse_file = $argv[3];
	parse_each_record($parse_date, $parse_user, $parse_file);
?>

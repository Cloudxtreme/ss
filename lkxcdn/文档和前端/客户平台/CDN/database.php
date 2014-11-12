<?PHP

require_once('common/mysql.com.php');
error_reporting(E_ALL^E_NOTICE);
/*

$mysql_class = new MySQL('cdninfo');
$mysql_class -> opendb("cdn_file", "utf8");

$quer = "select `user`, `pass`,`desc` from `user`";
$result = $mysql_class->query($quer);

$file_ret = array();

while( ($row = mysql_fetch_array($result)) ) 
{
	//print_r($row);
	$file_ret[] = array('user'=> $row[0], 'pass'=>$row[1], 'desc'=>$row[2]);
}
mysql_free_result($result);

print_r($file_ret);


unset($mysql_class);

$mysql_class = new MySQL('ibss');
$mysql_class -> opendb("IBSS_TEST", "utf8");

foreach($file_ret as $key => $data)
{
	//print($data['user']);print($data['desc']);print($data['pass']);

	$user = $data['user'];
	$pass = $data['pass'];
	$desc = $data['desc'];
	$quer = "INSERT into `CDN_User` (`User`,`Name`,`Pass`,`Type`,`IsAction`) VALUES ('$user','$desc','$pass','1','1')";
	print($quer);
	$result = $mysql_class->query($quer);
}
*/

$mysql_class = new MySQL('ibss');
$mysql_class -> opendb("IBSS_TEST", "utf8");

$quer = "select * from `CDN_User`";
$result = $mysql_class->query($quer);

while( ($row = mysql_fetch_array($result)) ) 
{
	//print_r($row);
	$pass = "$row[1]123";
	//print($pass);
	$pass = md5($pass);
	$file_ret[] = array('user'=> $row[1], 'name'=>$row[2],'pass'=> $pass, 'type'=>$row[4]);
}
mysql_free_result($result);

print_r($file_ret);

foreach($file_ret as $key => $data)
{
	//print($data['user']);print($data['desc']);print($data['pass']);

	$user = $data['user'];
	$pass = $data['pass'];
	$name = $data['name'];
	$type = $data['type'];
	$quer = "INSERT into `CDN_User` (`User`,`Name`,`Pass`,`Type`,`IsAction`) VALUES ('$user','$name','$pass','$type','1')";
	print($quer);
	$result = $mysql_class->query($quer);
}

?>
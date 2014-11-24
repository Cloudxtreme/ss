<?php  
  
ini_set('display_errors', E_ALL);  
$GLOBALS['THRIFT_ROOT'] = './php/src';  
  
require_once( $GLOBALS['THRIFT_ROOT'] . '/Thrift.php' );  
require_once( $GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php' );  
require_once( $GLOBALS['THRIFT_ROOT'] . '/transport/TBufferedTransport.php' );  
require_once( $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php' );  
require_once( $GLOBALS['THRIFT_ROOT'] . '/packages/Hbase/Hbase.php' );  


$socket = new TSocket('119.120.92.132', '7777');  
  
$socket->setSendTimeout(10000); // Ten seconds (too long for production, but this is just a demo ;)   
$socket->setRecvTimeout(20000); // Twenty seconds   
$transport = new TBufferedTransport($socket);  
$protocol = new TBinaryProtocol($transport);  
$client = new HbaseClient($protocol);  
  
$transport->open();  
 
$tableName = "cdn_web_url_date";  
$beginRow = "poco_2013-05-10";  
$endRow = "poco_2013-05-16";  
$columns = array();
$columns[] = "cnt:image13-c.poco.cn\t/mypoco/myphoto/20121128/09/52098375201211281203256563783678097_006_145.jpg";
$scanner = $client -> scannerOpenWithStop($tableName,$beginRow,$endRow,$columns);

try 
{
	while(1)
	{
		$rows = $client->scannerGet( $scanner );
		if(!$rows)
		{
			break;
		}
		else
		{
			$size = count($rows[0]->columns);
			echo "{$rows[0]->row}:{$size}\n";
		}
	}
} 
catch ( NotFound $nf ) 
{
	$client->scannerClose( $scanner );
}

$client->scannerClose( $scanner );
$transport->close();  

?> 

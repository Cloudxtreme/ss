<?php
//Reduce errors
error_reporting(~E_WARNING);

$server = '127.0.0.1';
$port = 9999;

if($argc != 2)
{
	echo "Argument error.\n";
	return;
}
$input = $argv[1];

if(!($sock = socket_create(AF_INET, SOCK_DGRAM, 0))) {
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
    die("Couldn't create socket: [$errorcode] $errormsg \n");
}

//Communication loop


    //Send the message to the server
    if( ! socket_sendto($sock, $input , strlen($input) , 0 , $server , $port)) {

        $errorcode = socket_last_error();

        $errormsg = socket_strerror($errorcode);

        die("Could not send data: [$errorcode] $errormsg \n");

    }

    //Now receive reply from server and print it


    if(socket_recv ( $sock , $reply , 1024 , 0 ) !== FALSE) 
    {
        echo "$reply\n";
    }

?>

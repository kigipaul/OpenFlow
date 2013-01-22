<?php

##
## Initialization
##
$JSON_HOST = "127.0.0.1";
$JSON_PORT = "8080";
$JSON_ROOT = "wm";
$JSON_PATH = "$JSON_HOST:$JSON_PORT/$JSON_ROOT";


$JSON_DATA="";
##
## Get Param
##

$TYPE=$_GET['t'];

if($TYPE=='device'){
	$JSON_DATA = file_get_contents("http://$JSON_PATH/device/");
}
echo $JSON_DATA;
?>

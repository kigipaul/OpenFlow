<?php

##
## Initialization
##
$JSON_HOST = "127.0.0.1";
$JSON_PORT = "8080";
$JSON_ROOT = "wm";
$JSON_PATH = "$JSON_HOST:$JSON_PORT/$JSON_ROOT";
$ROOT = "/var/www/openflow";
$BASE_PATH = $ROOT;


require("$ROOT/lib/Lib_Counter.php");
$src_json_data = file_get_contents("http://$JSON_PATH/core/counter/all/json");
$src_json_data = json_decode($src_json_data,true);
if($src_json_data =="" || is_null($src_json_data)){

	exit(0);
}

$counter_array = Counter_jsonToArray($src_json_data);
$data_layout = Counter_layout($counter_array);

$setBase = Counter_setBase(json_encode($data_layout),$BASE_PATH);

	
?>

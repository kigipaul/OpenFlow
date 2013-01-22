<?php
##
## Initialization
##
$JSON_HOST = "127.0.0.1";
$JSON_PORT = "8080";
$JSON_ROOT = "wm";
$JSON_PATH = "$JSON_HOST:$JSON_PORT/$JSON_ROOT";

$name_md5="";

$type = $_GET['t'];

if($type="priority"){
	$sw_id = $_GET['sw'];
	$mac = $_GET['mac'];
	$ip = $_GET['ip'];
	$pri = $_GET['pri'];
	$name_md5 = md5($sw_id.$mac.$ip.$pri.date());

	$JSON_DATA='{
		"switch":"'.$sw_id.'",
		"name":"'.$name_md5.'",
		"src_mac":"'.$mac.'",
		"src_ip":"'.$ip.'",
		"priority":"'.$pri.'",
		"active":"true",
		"actions":"output=normal"
	}';
	echo "data1";
}
/*
curl -d '{"switch": "00:00:17:20:24:01:20:02", "name":"flow-test-1", "priority":"32768", "src_mac":"00:26:18:a2:d9:89","src_ip":"172.24.12.41","active":"true", "actions":"output=normal"}' http://127.0.0.1:8080/wm/staticflowentrypusher/json*/
?>

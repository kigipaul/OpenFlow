<?php

##
## Initialization
##
$JSON_HOST = "127.0.0.1";
$JSON_PORT = "8080";
$JSON_ROOT = "wm";
$JSON_PATH = "$JSON_HOST:$JSON_PORT/$JSON_ROOT";
$BASE_PATH = "./";

$JSON_DATA="";
##
## Get Param
##

if(!isset($_GET['t'])){
	exit(0);
}
$TYPE=$_GET['t'];

if($TYPE=='device'){
	$JSON_DATA = file_get_contents("http://$JSON_PATH/device/");
}else if($TYPE=='counter'){
	require("lib/Lib_Counter.php");
	$src_json_data = file_get_contents("http://$JSON_PATH/core/counter/all/json");
	$src_json_data = json_decode($src_json_data,true);
	if($src_json_data =="" || is_null($src_json_data)){
		
		exit(0);
	}
	
	$counter_array = Counter_jsonToArray($src_json_data);
	
	$data_layout = Counter_layout($counter_array);

	$getBase = Counter_getBase($BASE_PATH);
	##
	##	Compare data and make json
	##	$json_array Schema:
	##		Array(
	##			[#] => Array(
	##				"switchID" => switchDPID
	##				"interface" => Array(
	##					"port" => port #
	##					"level" => [0-10] (0 is ok and 8 is to danger)
	##					"counter" => 10min Counter Gap
	##				)
	##		)
	##	---
	##	echo JSON Schema:
	##		like $json_array
	##		[
	##			{"switchID":switchDPID,
	##			 "interface":[{"port":port#,"level":[0-10],"counter":10min Counter Gap}]
	##			}
	##		]
	##

	##	Config set
	$upper_bound = 50000; 
	##
	$json_array = array();
	foreach($data_layout as $switchID => $port){
		$tmp_data_arr = array();
		$tmp_data_arr['switchID'] = $switchID;
		$tmp_data_arr['interface'] = array();
		foreach($port as $port_num => $val){
			$counter_gap = $val-$getBase[$switchID][$port_num];	
			$level = ($counter_gap>=$upper_bound?8:0);
			$tmp_arr = array("port"=>$port_num,"level"=>$level,"counter"=>$counter_gap);
			array_push($tmp_data_arr['interface'],$tmp_arr);
		}
		array_push($json_array,$tmp_data_arr);

	}
	if(isset($_GET['c']) && @$_GET['c']=="1"){
		print_arr($counter_array);
		echo "<br /> ================ <br />";
	}
	if(isset($_GET['d']) && @$_GET['d']=="1"){
		print_arr($data_layout);
		echo "<br /> ================ <br />";
	}
	if(isset($_GET['j']) && @$_GET['j']=="1"){
		print_arr($json_array);
		echo "<br /> ================ <br />";
	}
	$JSON_DATA = json_encode($json_array,true);
}
echo $JSON_DATA;


function print_arr($array){
	echo "<pre>";
	print_r($array);
	echo "</pre>";
}
?>

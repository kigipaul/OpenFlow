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
}else if($TYPE=='counter'){
	$src_json_data = file_get_contents("http://$JSON_PATH/core/counter/all/json");
	$src_json_data = json_decode($src_json_data,true);
	if($src_json_data =="" || is_null($src_json_data)){
		
		exit(0);
	}
	##
	##	Parse Counter json
	##	$counter_array Schema: 
	##		Array(
	##			[#] => Array (
	##				[switchID] 	=> switchDPID / Control packet name
	##				[control] 	=> 0 / 1  (Not control packet / control packet)
	##				[counter] 	=> src data
	##				[port] 		=> port number / null (if no port then it's null)
	##				[packet] 	=> OFPacketIn / Out (Packet is in or out)
	##				[layer] 	=> L3 / L4 (packet layer)
	##				[proto] 	=> packet use protocol
	##				[msg] 		=> (Exception Message)
	##			)
	##		)
	##
	$counter_array=array();
	foreach($src_json_data as $switch_all => $counter){
		$havePort = true;
		$sw_index = 0;
		$switch_data_arr = explode("__",$switch_all);
		$switch = array();
		
		## Start parse json and build to array
		$switch['switchID'] = $switch_data_arr[$sw_index++];
		$switch['control']=0;
		$switch['counter'] = $counter;
		## Check is control packet ?
		if(strlen($switch['switchID'])!=23){
			$tmp_msg="";
			for($sw_index ; $sw_index < count($switch_data_arr);$sw_index++){
				$tmp_msg .= ($sw_index!=(count($switch_data_arr)-1)?"_":"").$switch_data_arr[$sw_index];
			}
			$switch['control']=1;
			$switch['port'] = "null";
			$switch['packet'] = "null";
			$switch['layer'] = "null";
			$switch['proto'] = "null";
			$switch['msg'] = $tmp_msg;
			array_push($counter_array,$switch);
			continue;
		}
		##Check have port
		if(substr($switch_data_arr[1],0,2)=="OF"){
			$switch['port'] = "null";
			$switch['packet'] = $switch_data_arr[$sw_index++];
			$havePort = false;
		}else{
			$switch['port'] = $switch_data_arr[$sw_index++];
			$switch['packet'] = $switch_data_arr[$sw_index++];
		}
		#Get layer and Protocol
		if(count($switch_data_arr)==($havePort?4:3)){
			$tmp_arr = explode("_",$switch_data_arr[$sw_index++]);
			#Check have layer
			if(count($tmp_arr)==2){
				$switch['layer'] = $tmp_arr[0];
				$switch['proto'] = $tmp_arr[1];
			}else{
				$switch['layer'] = "null";
				$switch['proto'] = $tmp_arr[0];
			}
			$switch['msg'] = "";
		}else{
			$tmp_msg = "";
			for($sw_index ; $sw_index < count($switch_data_arr);$sw_index++){
				$tmp_msg .= ($sw_index!=(count($switch_data_arr)-1)?"_":"").$switch_data_arr[$sw_index];
			}
			$switch['layer'] = "null";
			$switch['proto'] = "null";
			$switch['msg'] = $tmp_msg;
		}
		
		array_push($counter_array,$switch);

	}

	##
	##	Get important information
	##	$data_layout Schema:
	##		Array(
	##			[switchID #] => Array(
	##				[port #] => sum of number of this interface counter 
	##			)
	##		)
	##
	##

	$data_layout = array();
	foreach($counter_array as $key => $data){
		if($data['control'] || $data['port']=="null"){
			continue;
		}
		if(!isset($data_layout[$data['switchID']])){
			$data_layout[$data['switchID']] = array();
			$data_layout[$data['switchID']][$data['port']] = $data['counter'];
		}else{
			if(!isset($data_layout[$data['switchID']][$data['port']])){
				$data_layout[$data['switchID']][$data['port']] = $data['counter'];
			}else{
				$data_layout[$data['switchID']][$data['port']] += $data['counter'];
			}
		}
	}
	
	##
	##	Compare data and make json
	##	$json_array Schema:
	##		Array(
	##			[#] => Array(
	##				"switchID" => switchDPID
	##				"interface" => Array(
	##					"port" => port #
	##					"level" => [0-10] (0 is ok and 8 is to danger)
	##				)
	##		)
	##	---
	##	echo JSON Schema:
	##		like $json_array
	##		[
	##			{"switchID":switchDPID,
	##			 "interface":[{"port":port#,"level":[0-10]}]
	##			}
	##		]
	##

	##	Config set
	$upper_bound = 10000000; 
	##
	$json_array = array();
	foreach($data_layout as $switchID => $port){
		$tmp_data_arr = array();
		$tmp_data_arr['switchID'] = $switchID;
		$tmp_data_arr['interface'] = array();
		foreach($port as $port_num => $val){
			$level = ($val>=$upper_bound?8:0);
			$tmp_arr = array("port"=>$port_num,"level"=>$level);
			array_push($tmp_data_arr['interface'],$tmp_arr);
		}
		array_push($json_array,$tmp_data_arr);

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

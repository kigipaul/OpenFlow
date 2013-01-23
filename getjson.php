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
	##	Schema: 
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
	##	Schema:
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
}
echo $JSON_DATA;


function print_arr($array){
	echo "<pre>";
	print_r($array);
	echo "</pre>";
}
?>

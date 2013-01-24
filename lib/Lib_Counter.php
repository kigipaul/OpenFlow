<?php


$BASE_FILE = "Counter_Base.json";

function Counter_jsonToArray($src_json_data){
	##
    ##  Parse Counter json
    ##  $counter_array Schema:
    ##      Array(
    ##          [#] => Array (
    ##              [switchID]  => switchDPID / Control packet name
    ##              [control]   => 0 / 1  (Not control packet / control packet)
    ##              [counter]   => src data
    ##              [port]      => port number / null (if no port then it's null)
    ##              [packet]    => OFPacketIn / Out (Packet is in or out)
    ##              [layer]     => L3 / L4 (packet layer)
    ##              [proto]     => packet use protocol
    ##              [msg]       => (Exception Message)
    ##          )
    ##      )
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
	return $counter_array;
}

function Counter_layout($counter_array){
	##
    ##  Get important information
    ##  $data_layout Schema:
    ##      Array(
    ##          [switchID #] => Array(
    ##              [port #] => sum of number of this interface counter
    ##              [timestamp] => This data timestamp
    ##          )
    ##      )
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
        $data_layout[$data['switchID']]['timestamp'] = date("Y-m-d H:i:s");
    }
	return $data_layout;
}

function Counter_getBase(){
	global $BASE_FILE;
	$file = file_r("./$BASE_FILE");
	return $file;
}

function Counter_setBase($json_data){
	global $BASE_FILE;
	$file = file_w($json_data,"./$BASE_FILE");
	return $file;
}
function file_w($data,$path){
	$isSuc = false;
	$fdata = fopen("$path","w");
	if($fdata){
		$isSuc = true;
		fwrite($fdata,$data);
		fclose($fdata);
		return $isSuc;
	}else{
		$isSuc=false;
		return $isSuc;
		
	}
}
function file_r($path){
	$isSuc = false;
	$fdata = fopen("$path","r");
	$data = "";
	if($fdata){
		$isSuc = true;
		while(!feof($fdata)){
			$data .= fgets($fdata,4096);
		}
		fclose($fdata);
		return $data;
	}else{
		return $isSuc;
	}
}



?>

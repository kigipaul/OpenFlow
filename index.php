<html>
<head>
<Title>OpenFlow Controller</Title>
<Meta http-equiv="Content-Type" Content="text/html; Charset=utf-8">

<style type="text/css">
	@import "./css/bootstrap.min.css";
	body {
		width:800px;
		margin:0 auto 0 auto;
	}
	#switch_list{
		float:right;
		position:fixed;
		top:80px;
		right:100px;;
		width:200px;
	}
	#tb_device{
		margin: 20px 0 0 50px;
		width:550px;
	}
	#tb_switch{
		width:150px;
	}
	.th_mac{
		width:150px;
	}
	.th_ip{
		width:250px;
	}
	.th_pri{
		width:100px;
	}
	.th_opt{
		width:100px;
	}
	.btn_send{
		width:50px;
		height:35px;
	}
	.btn_sendall{
		position:fixed;
		float:right;
		right:250px;
		bottom:30px;
		width:100px;
		height:100px;
		font-size:130%;
		display:none;
	}
	.tr_data:hover td{
	border-bottom-color: rgba(82, 168, 236, 0.804);
	border-left-color: rgba(82, 168, 236, 0.804);
	border-right-color: rgba(82, 168, 236, 0.804);
	border-top-color: rgba(82, 168, 236, 0.804);
	box-shadow: inset 0px 1px 1px rgba(0, 0, 0, 0.071), 0px 0px 8px rgba(82, 168, 236, 0.600);
	outline: 0px none currentColor;
	}
	.send{
		display:none;
	}
</style>


<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript">
/*
 *   Initiation
 */
var URL_GETJSON = "getjson.php";
var URL_SETJSON = "setjson.php";
var DEFAULT_PRI = "36768";
var DT;
var SWITCH_IDs = [];
var COUNT_mac=0;
var COUNT_ip=0;

/*
 *	Default Active
 */
$(document).ready(function(){
	getData("device");
	getData("counter");
});
/*
 *	check SWITCH_IDs array
 */
function checkArrID(){
	var test_word="";
	for(var i in SWITCH_IDs){
		test_word+=i+"=>id:"+SWITCH_IDs[i].id+"\n";
		for(var j in SWITCH_IDs[i].dev){
			test_word+="\t"+j+"=>dev:\n\t\t port:" + SWITCH_IDs[i].dev[j].port+"\n\t\thost:";
			$.each(SWITCH_IDs[i].dev[j].host,function(k,v){
				test_word+="("+k+","+v+")";
			});
			test_word+="\n\n";
			
		}
	}
//	alert(test_word);
}

/*
 *	Get Device Data Function
 */
function getData(t){
	$.ajax({
		type:"GET",
		url:URL_GETJSON,
		data:"t="+t,
		success:function(msg){
		//Get JSON
			var json=$.parseJSON(msg);
			if(t=="device"){
				parseData(json);
			}else if(t=="counter"){
				parseCounter(json);
			}
		},
		error:function(msg){
			alert("Ajax Error:\n===========\n"+msg);
		}
	});
}

/*
 *	parse all Data
 */
function parseData(json){
	var tmp="";
//Get JSON data and Create Html
	$.each(json,function(seq,data){
		var html="";
		var subhtml ="";
		var count=0;
	//Get Ipv4 List
		$.each(data.ipv4,function(sq,ipv4){
			if(count==0){
				html += 
				'<td id="ip_'+seq+'_'+sq+'" class="td_ip">'+ipv4+'</td>'+
				'<td id="pri_'+seq+'_'+sq+'" class="td_pri"></td>';
			}else{
				subhtml += '<tr id="dev_'+seq+'_'+sq+'" class="tr_data" onclick="addPri(this.id)">' +
				'<td id="ip_'+seq+'_'+sq+'" class="td_ip">'+ipv4+'</td>'+
				'<td id="pri_'+seq+'_'+sq+'" class="td_pri"></td></tr>';
			}
			COUNT_ip++;
			count++;
		});
	//Get Switch ID List
		$.each(data.attachmentPoint,function(sqs,switchArray){
			var isExistID=false;
			var isExistPort = false;
			for(var key in SWITCH_IDs){
				if(SWITCH_IDs[key].id == switchArray.switchDPID){
					for(var dev_num in SWITCH_IDs[key].dev){
				//Get switch interface port
						if(SWITCH_IDs[key].dev[dev_num].port == switchArray.port){
							SWITCH_IDs[key].dev[dev_num].host.push(seq);
							isExistPort = true;
						}
					}
					isExistID=true;
					break;
				}
			}
			if(!isExistID && !isExistPort){
				SWITCH_IDs.push({"id":switchArray.switchDPID,"dev":[{"port":switchArray.port,"host":[seq]}]});
			}
		});
	//Check is no-ip
		if(count==0){
			count++;
			COUNT_ip++;
			html = 
				'<tr id="dev_'+seq+'_0" class="tr_data no_ip" onclick="addPri(this.id)">'+
				'<td id="mac_'+seq+'_0" class="td_mac" rowspan="'+count+'">'+data.mac+'</td>'+
				'<td id="ip_'+seq+'_0" class="td_ip"></td>'+
				'<td id="pri_'+seq+'_0" class="td_pri"></td></tr>';
		}else{
			html=
				'<tr id="dev_'+seq+'_0" class="tr_data" onclick="addPri(this.id)">'+
				'<td id="mac_'+seq+'_0" class="td_mac" rowspan="'+count+'">'+data.mac+'</td>'+html+'</tr>';
		}
		COUNT_mac++;
	//Combine html
		tmp += html;
		if(subhtml!="")
			tmp += subhtml;
		
	});
//Create html
	$("#tb_device tbody").html(tmp);
	$("#num_mac").html(COUNT_mac);
	$("#num_ip").html(COUNT_ip);
	for(var key in SWITCH_IDs){
		$("#tb_switch tbody").append('<tr id="sw_'+key+'" ><td>'+SWITCH_IDs[key].id+'</td></tr>');
	}
	
}
/*
 *	parse Counter data
 */
function parseCounter(json_data){
	$.each(json_data,function(index,data){
		for(var key in data.interface){
			if(data.interface[key].level ==8){
				CounterWarning(data.switchID,data.interface[key].port);
			}
		}
	});
}

/*
 *	Warning function
 */
function CounterWarning(id,port){
	var haveHost = false;
	var get_seq = [];
	for(var i in SWITCH_IDs){
		if(SWITCH_IDs[i].id==id){
			for(var j in SWITCH_IDs[i].dev){
				if(SWITCH_IDs[i].dev[j].port == port){
					get_seq.push(SWITCH_IDs[i].dev[j].host);
					haveHost = true;
				}
			}
			
		}
	}
	if(!haveHost){
		return;
	}
	$.each(get_seq[0],function(i,change_seq){
		var num_dev = $("#mac_"+change_seq+"_0").attr("rowspan");
		for(var j = 0;j<=num_dev ;j++){
			$("#dev_"+change_seq+"_"+j).css("background-image","-o-linear-gradient(top, #EE5F5B, #BD362F)");
			$("#dev_"+change_seq+"_"+j).css("color","#FFFFFF");
			$("#dev_"+change_seq+"_"+j).css("text-shadow","0px -1px 0px rgba(0, 0, 0, 0.251)");

		}
	});
}
/*
 *	Add onClick element
 */
function addPri(id){
	var id_seq = id.replace("dev_","");
//Create Send Button
	$("#"+id).append('<div id="send_div_'+id_seq+'"><input type="button" id="send_'+id_seq+'" class="btn btn_send" value="Send" onClick="send(this.id)"/></div>');
//Create result pop
	$("#send_div_"+id_seq).popover({
		"html":true,
		"trigger":"click",
		"content":"",
		"placement":"top",
		"title":"",
		"delay":{
			"show":300,
			"hide":200
		}
	});
//Show "Send all" button
	$("#btn_sendall").show(500);
//Cheack Priority val is exist
	var pri_val=$("#pri_"+id_seq).text();
	if(pri_val==""){
		pri_val=DEFAULT_PRI;
	}
//Create Priority val input text
	$("#pri_"+id_seq).html('<input type="text" id="pri_val_'+id_seq+'" class="input-small" placeholder="'+pri_val+'" />');

//Disable click event
	$("#"+id).attr('onclick','null');
}


/*
 *	Add Click Active Event
 */
function send(id){
//Get id and value
	var gparent_id=$("#"+id).parent().attr("id");
	var parent_id=$("#"+gparent_id).parent().attr("id");
	var parent_id_seq=parent_id.replace("dev_","");
	var pri_val=$("#pri_"+parent_id_seq+" input[type=text]").val();
//Check Priority val current
	if(pri_val >36768 ||pri_val <0 || isNaN(pri_val)){
//		alert("輸入的值有誤:"+pri_val);
		$("#"+gparent_id).attr("data-content","輸入的值有誤:"+pri_val);
		$("#"+gparent_id).attr("data-original-title","Error");
		setTimeout(function(){$("#"+gparent_id).popover('hide');},2000);
		return;
	}
//Choose Switch
	var seq = parent_id_seq.substring(0,parent_id_seq.indexOf("_"));
	var switch_ls=check_switch(seq);
	sendmsg(id,switch_ls);

//Set JSON to Controler
	for(var i in switch_ls){
		send_pri(gparent_id,SWITCH_IDs[i].id,$("#mac_"+parent_id_seq).text(),$("#ip_"+parent_id_seq).text(),pri_val);
	}
//Change priority value
	$("#pri_"+parent_id_seq).html(pri_val);
//Remove Send Button
	$("#"+id).remove();
//Check whether "Send All" button hide or not
	check_sendall();

//Reply onClick event
	setTimeout(function(){$("#"+parent_id).attr('onclick',"addPri(this.id)");},1000);	
	setTimeout(function(){$("#"+gparent_id).popover('hide');$("#"+gparent_id).remove();},3000);	
}
/*
 *	Send All active
 */
function sendall(){
	var all_send=$(".btn_send");
	for(var i=0;i<all_send.length;i++){
		send(all_send[i].id);
	}
}

/*
 *	判斷該筆資料屬於那些Switch id
 */
function check_switch(seq){
	var switch_seq=[];
	for(var i in SWITCH_IDs){
		for(var j in SWITCH_IDs[i].dev){
			if(SWITCH_IDs[i].dev[j]==seq){
				switch_seq.push(i);
				break;
			}
		}
	}
	return switch_seq;

}

/*
 *	判斷是否關閉Send All button
 */
function check_sendall(){
	var check_send_btn = $(".btn_send");
	if(check_send_btn.length <= 0){
		$("#btn_sendall").hide(500);
	}
}
/*
 *	傳送資料 Priority Module
 */
function send_pri(id,sw,mac,ip,pri){
	var data = "sw="+sw+"&mac="+mac+"&ip="+ip+"&pri="+pri;
	sendajax(id,"priority",data);
}

/*
 *	Send Data using ajax
 */
function sendajax(id,type,data){
	$.ajax({
		type:"GET",
		url:URL_SETJSON,
		data: "t="+type+"&"+data,
		success:function(msg){
		//Get origian title val and origian content val
			var or_title = $("#"+id).attr("data-original-title");
			var or_content = $("#"+id).attr("data-content");
		//Check is Error before
			if(or_title=="Error" || typeof or_content=="undefined"){
				$("#"+id).attr("data-original-title","");
				$("#"+id).attr("data-content","");
				or_content = "";
			}
		//Set Result in popover content and show it
			$("#"+id).attr("data-original-title","Result");
			$("#"+id).attr("data-content",((or_content=="")?"":or_content+"<br />")+msg);
			$('#'+id).popover('show');
		},
		error:function(msg){
			$("#"+id).attr("data-original-title","Ajax Error");
			$("#"+id).attr("data-content",msg);
			$('#'+id).popover('show');
		}

	});
}

/*
 *	Set Send Data animate
 */
function sendmsg(src_id,switch_ls){
	var x = $("#"+src_id).offset().left;
	var y = $("#"+src_id).offset().top;
	
	for(var i in switch_ls){
		var sw_x=$("#sw_"+i).offset().left;
		var sw_y=$("#sw_"+i).offset().top;
		
		var img = '<div id="img_'+src_id+'_'+i+'" class="pack_img pack_img_'+src_id+'" style="position:absolute;top:'+y+';left:'+x+';height:20px;width:20px;"><img src="images/conf_packet.png" /></div>';
		$("body").append(img);
		$("#img_"+src_id+"_"+i).animate({
			"left":"+="+(sw_x-x)+"px",
			"top":"+="+(sw_y-y)+"px",
		},{
			"duration":2000
		});
	}

	setTimeout(function(){$(".pack_img_"+src_id).remove();},2100);	
}


/*
 *
 */
function show_result(id,msg){
	
}
/*
 *	Hide and Show no ip rows
 */
function noip(type){
	if(type=="hide"){
		$(".no_ip").hide(500);
		$("#btn_noip").attr('onclick','noip("show")');
		$("#btn_noip").text("Show No IP");
	}else if(type=="show"){
		$(".no_ip").show(500);
		$("#btn_noip").attr('onclick','noip("hide")');
		$("#btn_noip").text("Hide No IP");
	}

}
</script>
</head>
<body>
<div>
	<h2>OpenFlow Controller</h2>
	<div class="btn-group" data-toggle="buttons">
<!--		<button type="button" class="btn">All Data</button> -->
		<button type="button" id="btn_noip" class="btn" onclick="noip('hide')">Hide No IP</button> 
	</div>
	<div id="div_sendall">
		<button type="button" id="btn_sendall" class="btn btn-success btn_sendall" onClick="sendall()">Send All</button>
	</div>
	<div id="switch_list">
		<table id="tb_switch" class="table table-bordered">
			<thead>
				<th>Switch DP ID</th>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
	<table id="tb_device" class="table table-striped">
		<thead>
			<tr>
				<th class="th_mac">MAC(<span id="num_mac"></span>)</th>
				<th class="th_ip">IP(<span id="num_ip"></span>)</th>
				<th class="th_pri">Priority</th>
				<th class="th_opt"></th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</div>
</body>
</html>

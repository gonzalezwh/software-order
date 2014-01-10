//so that admin.js knows what ajax to call
var requesttype = 'software'
var tableindex = 'SID';
//table select data
var VID_options = new Array();
var OSID_options = new Array();
$(function(){
	$("#show_historic").change(function(){
		app.displayTable();
	});	
});
$.ajaxSetup({
    type: "GET",
    url: "../ajax.php",
    dataType: "json",
});
$.ajax({
    data: {
		request:'getdata',
        type: 'vendor'
    },
    success: function(data){ 
		for(var i in data){ 
			var current = data[i];
			VID_options[current['VID']] = current['Name'];
		}
    }
});
$.ajax({
    data: {
		request:'getdata',
        type: 'os'
    },
    success: function(data){
		for(var i in data){
			var current = data[i];
			OSID_options[current['OSID']] = current['Name'];
		}
    }
});
function post_gettabledata(data){}
function getquickeditoptions(name){ 
	if(name == 'OSID'){
		return OSID_options;
	}
	else if(name == 'VID'){
		return VID_options;
	}
	else{
		return;
	}
}
function post_displaytable(){
	var historic = $('#show_historic');
	if(historic.is(':checked')){
		showhistoric();
	}else{
		hidehistoric();
	}
	//not sure if I want this
	var index = $("th:contains('OSID')").index()+1;
	$("td:nth-child("+index+")").each(function(){
		var OSID = $(this).children()[0].innerHTML;
		var OSName = OSID_options[OSID];
		$(this).append('('+OSName+')');
	});
	var index = $("th:contains('VID')").index()+1;
	$("td:nth-child("+index+")").each(function(){
		var VID = $(this).children()[0].innerHTML;
		var VendorName = VID_options[VID];
		$(this).append('('+VendorName+')');
	});
}
function showhistoric(){
	var index = $("th:contains('Historic')").index()+1;
	var rows = $("td:nth-child("+index+"):contains('1')").closest("tr");
	rows.each(function(){$(this).show();});
}
function hidehistoric(){
	var index = $("th:contains('Historic')").index()+1;
	var rows = $("td:nth-child("+index+"):contains('1')").closest("tr");
	rows.each(function(){$(this).hide();});
}
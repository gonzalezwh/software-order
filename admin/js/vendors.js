//so that admin.js knows what ajax to call
var requesttype = 'vendor';
var tableindex = 'VID';
var PreviousVID_options = new Array();
function post_gettabledata(){
    for(var i in app.tabledata){
        current = app.tabledata[i];
        PreviousVID_options[current['VID']] = current['Name'];
    }
}
function post_displaytable(data){
	var historic = $('#show_historic');
	if(historic.is(':checked')){
		showhistoric();
	}else{
		hidehistoric();
	}
};
function getquickeditoptions(name){
	if(name == 'PreviousVID'){
		return PreviousVID_options;
	}
	else{
		return;
	}
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
$(function(){
	$("#show_historic").change(function(){
		app.displayTable();
	});	
});
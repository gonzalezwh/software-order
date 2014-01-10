//This function fills each table with the ajax return, and adds links to download
//csv exports (made in javascript) below each table.
function fill_table(data){
	$('tbody').empty();
	$('thead').empty();
	$('.req_download').empty();
	$('.auth_download').empty();
	var csv = '';
	for(var field in data['Authorized'][0]){
		csv += field+",";
		$('#auth_table thead').append('<th>'+field+'</th>');
	}
	csv += "\r\n";
	for(var i=0;i<data['Authorized'].length;++i){
		var row = $('<tr>');
		for(var field in data['Authorized'][i]){
			csv += "\'"+data['Authorized'][i][field]+"\',";
			row.append('<td>'+data['Authorized'][i][field]+'</td>');
		}
		csv += "\r\n";
		$('#auth_table tbody').append(row);
	}
	if(csv !== "\r\n"){
		$('.auth_download').append('<a download="authorized_orders.csv" href="data:application/csv;charset=utf-8,'+encodeURI(csv)+'">Download CSV</a>');
	}
	csv = '';
	for(var field in data['Requested'][0]){
		csv += field+",";
		$('#req_table thead').append('<th>'+field+'</th>');
	}
	csv += "\r\n";
	for(var i=0;i<data['Requested'].length;++i){
		var row = $('<tr>');
		for(var field in data['Authorized'][i]){
			csv += "\'"+data['Authorized'][i][field]+"\',";
			row.append('<td>'+data['Authorized'][i][field]+'</td>');
		}
		csv += "\r\n";
		$('#req_table tbody').append(row);
	}
	if(csv !== "\r\n"){
		$('.req_download').append('<a download="requested_orders.csv" href="data:application/csv;charset=utf-8,'+encodeURI(csv)+'">Download CSV</a>');
	}
}
$(function(){
	$.ajaxSetup({
		type: "GET",
		url: "ajax.php", 
		dataType: "json"
	});
	var date = new Date();
	var year = date.getFullYear();
	var month = date.getMonth()+1;
	var day = date.getDate();
	var end_string = year+"-"+month+"-"+day;
	date.setMonth(-1);
	year = date.getFullYear();
	month = date.getMonth()+1;
	day = date.getDate();
	var start_string = year+"-"+month+"-"+day;
	$('#start_date').val(start_string);
	$('#end_date').val(end_string);
	$.ajax({
		data: {
			request: "getmyorders",
			options: {
				start_date:start_string,
				end_date:end_string,
			}
		}, 
		success: function(data) {
			fill_table(data);
		}
	});
	$('#start_date').datepicker({
		dateFormat: 'yy-mm-dd',
		showAnim: 'fadeIn',
	});
	$('#end_date').datepicker({
		dateFormat: 'yy-mm-dd',
		showAnim: 'fadeIn',
	});
	$('#update').click(function(){
		var start_string = $('#start_date').val();
		var end_string = $('#end_date').val();
		$.ajax({
			data: {
				request: "getmyorders",
				options: {
					start_date:start_string,
					end_date:end_string,
				}
			}, 
			success: function(data) {
				fill_table(data);
			}
		});
	});
});
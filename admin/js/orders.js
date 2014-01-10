//so that admin.js knows what ajax to call
var requesttype = 'order';
var tableindex = 'OID';
function post_gettabledata(data){};
function post_displaytable(){
	var historic = $('#show_historic');
	if(historic.is(':checked')){
		showhistoric();
	}else{
		hidehistoric();
	}
	var index = $('th:contains(Authorizer)').index();
    var td = $('tr').find('td:eq('+index+')');
	td.addClass('ldap_cell');
	var index = $('th:contains(ReqOdin)').index();
    var td = $('tr').find('td:eq('+index+')');
	td.addClass('ldap_cell');
	//$('.ldap_cell').css('cursor','help');
};
function getquickeditoptions(name){};
//This is a little bit of custom code that lets Jackie (and others) see 
//ldap info based on a cell
$(function(){
	$("#show_historic").change(function(){
		app.displayTable();
	});	
	$('.ldap_cell').live('mouseover',function(){
		$.ajax({
			type: "GET",
			url: "../ajax.php",
			dataType: "json",
			context: this,
			async: false,
			data: {
				request:'getldapinfo',
				options: $(this).children('.datacell').html()
			},
			success: function(data){
				var name = ''; var phone = '';
				if(data['displayname']){
					name = data['displayname'][0];
				}
				if(data['telephoneNumber']){
					phone = data['telephoneNumber'][0];
				}
				var text = name+'<br>'+phone+'<br>';
				displayerror($(this), text);
			}
		});
	});
	$('.ldap_cell').live('mouseleave',function(){
		$('.input_error').remove();
	});
});
//taken from index.js
function displayerror(dom,text){
	var error = $('<div class="input_error">');
	error.append('<div class="input_error_arrow">');
	error.append('<div class="input_error_text">'+text+'</div>');
	var pos = dom.offset();
	error.css('left',pos.left+dom.outerWidth(true)+2);
	error.css('top',pos.top-10);
	error.data('parent',dom.selector);
	$('body').append(error);
}
function showhistoric(){
	var index = $("th:contains('JVNumber')").index()+1;
	var rows = $($("td:nth-child("+index+"):has(div:not(:empty))")).closest("tr");
	rows.each(function(){$(this).show();});
}
function hidehistoric(){
	var index = $("th:contains('JVNumber')").index()+1;
	var rows = $($("td:nth-child("+index+"):has(div:not(:empty))")).closest("tr");
	rows.each(function(){$(this).hide();});
}
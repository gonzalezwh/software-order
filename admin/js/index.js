var software = new Array();
var software_names = new Array();
var software_purchase = new Array();
var user = new Array();
$.ajax({ 
    type: "GET",
    url: "get.php", 
    dataType: "json",
    data: {request: "getformdata"},
    success: function(data) {
		for(var i in data['Softwares']){
			var current = data['Softwares'][i];
			if(current['Historic'] == 1){
				continue;
			}
			var vendor = data['Vendors'][current['VID']]['Name'];
			var os = data['OSlist'][current['OSID']]['Name'];
			software.push(current);
			software_names.push(vendor+" "+current["Name"]+" ("+os+")");
		}
		var option = $('<option value=""></option>');
		$('#form_authorizer').append(option);
    	for(var i in data['Authorizers']){
    		var name = data['Authorizers'][i]['FullName'];
    		var department = data['Authorizers'][i]['Department'];
    		var odin = data['Authorizers'][i]['Odin'];
    		var option = $('<option value='+odin+'>'+name+'-'+department+'</option>');
    		$('#form_authorizer').append(option);
    	}
    	for(var dept in data['Departments']){
            $("#form_department").append('<option value="'+dept+'">'+data['Departments'][dept]+'</option>');
        }
        var name = ''; var phone = ''; var email = '';
        if(data['LDAPInfo']['cn']) name = data['LDAPInfo']['cn'][0];
        if(data['LDAPInfo']['telephoneNumber']) phone = data['LDAPInfo']['telephoneNumber'][0];
        if(data['LDAPInfo']['mailLocalAddress']) email = data['LDAPInfo']['mailLocalAddress'][0];
        $("#form_name").val(name);
        $("#form_phone").val(phone);
        $("#form_email").val(email);
        $("#form_odin").val(data['LDAPInfo']['uid'][0]);
    }
});
function displaypreview(event){ 
    $("#preview_tbody").empty();
    var subtotal = 0;
    for(var i=0;i<software_purchase.length;i++) {
        var index = software_purchase[i];
        var current = software[index];
        subtotal += parseFloat(current['Price']);
        var name = software_names[index];
        var row = $('<tr><td>'+name+'</td><td>$'+current["Price"]+'</td><td><button class="preview_remove" index="'+i+'">x</button></td></tr>');
        if(event == 'new' && i == software_purchase.length-1){
        	row.fadeIn('fast');
        }
        $("#preview_tbody").append(row);
    }
    var serials = $("#form_serials").val();
    serials = serials.split(",");
    var countstr = '';
    if(serials.length > 1 && subtotal > 0){
    	total = serials.length*subtotal;
    	total = total.toFixed(2);
    	countstr = ' x '+serials.length+' = $'+total+'';
    }
    $("#preview_total").html('Total: $'+subtotal.toFixed(2)+countstr);
}
function autocompleteresponse(){
    var input = $("#software_input");
    var index = jQuery.inArray(input.val(), software_names);
    var price = "";
    var notes = "";
    if(index !== -1){
        price = "$"+software[index]['Price'];
        notes = software[index]['Notes'];
    }
    else{
        price = "Unknown Software";
    }
    input.parent().children("#software_price").html('Price: '+price);
    input.parent().children("#software_notes").html(notes);
}
function displaynavbar(){
	if(user['GID'] == 1){
		$("#navigation_list").append('<li><a href="admin_software.php">Admin Tools</a></li>');
	}
}
function displayerror(dom,text){
	var error = $('<div class="error">');
	error.html(text);
	$(dom).prepend(error);
	error.fadeOut(1500,function(){
		$(this).remove();
	});
}
$(function() { //equiv. to on dom load
    displaypreview();
    $("#software_input").autocomplete({
        source: software_names,
        select: function( event, ui ) {
            $('#software_input').val(ui.item.value);
            autocompleteresponse();
        }
    });
    $("#software_input").blur(function(){ 
        autocompleteresponse();
    });
    
    $("#software_add").click(function(){
        var name = $("#software_input").val();
        var os = $("#form_os").val();
        if(jQuery.trim(name) == ""){ //empty case
            return;
        }        
        var index = jQuery.inArray(name, software_names);
        var current = software[index];
        if(!current){ //not a real software
            return;
        }
        if(current['OSID'] !== os && current['OSID'] !== "3"){ //OS doesn't match what they put on the form
        	displayerror($(this).parent(),'This software is not available for '+$("#form_os option:selected").text());
        	return; 
        }
        software_purchase.push(index);
        displaypreview('new');
    });
    $(".preview_remove").live('click', function(){
        var index = $(this).attr('index');
        software_purchase.splice(index,1);
        $(this).parents('tr').fadeOut('fast',function(){
        	displaypreview();
        });
    });
    $("#form_serials").blur(function(){
    	displaypreview();
    });
    $("#form_os").change(function(){
    	var new_purchase = new Array();
    	for(var i=0;i<software_purchase.length;++i){
    		var index = software_purchase[i];
    		var os = software[index]["OSID"];
    		if(os == $(this).val() || os == "3"){
    			new_purchase.push(software_purchase[i]);
    		}
    	}
    	software_purchase = new_purchase;
    	displaypreview();
    });
});
function sortcallback(a,b,key){
	if(!isNaN(parseFloat(a[key]))){
		a = parseFloat(a[key]);
		b = parseFloat(b[key]);
	}
	else{ //string
        a = $.trim(a[key].toLowerCase());
        b = $.trim(b[key].toLowerCase());
	}
	if(a < b){
		return -1;
	}
	else if(a > b){
		return 1;
	}
	else{
		return 0;
	}
}
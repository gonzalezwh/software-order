/**
	This version of the frontend uses globals and no classes.
	There were some compatibility issues with variable scope/jQuery 
		so for the user's benefit I've kept using globals.
*/
var software = new Array(); //stores ajax return, kept in a variable as a method of caching that return
var software_names = new Array(); //saves some time on concatenating "full" software names
var software_purchase = new Array(); //array of SIDs sent to the form's POST. 
$.ajaxSetup({
    type: "GET",
    url: "ajax.php", 
    dataType: "json"
});
/**
	Get all the database information we need in one call,
		found that multiple async. calls were unreliable on 
		older versions of Chrome and Firefox.
*/
$.ajax({ 
    data: {request: "getformdata"}, 
    success: function(data) {
		for(var i in data['Softwares']){
			var current = data['Softwares'][i];
			var vendor = data['Vendors'][current['VID']]['Name'];
			var os = data['OSlist'][current['OSID']]['Name'];
			software.push(current);
			software_names.push(vendor+" "+current["Name"]+" ("+os+")");
		}
		//Create empty option for Authorizer.
		var option = $('<option value=""></option>');
		$('#form_authorizer').append(option);
		//Fill the Authorizer select with all the active Authorizers
    	for(var i in data['Authorizers']){
    		var name = data['Authorizers'][i]['FullName'];
    		var department = data['Authorizers'][i]['Department'];
    		var option = $('<option value='+data['Authorizers'][i]['AID']+'>'+department+'-'+name+'</option>');
            $('#form_authorizer').append(option);
    	}

        //Fill Software select with an OIT maintained (not Banner) list of software.
        for(var soft in data['Software']){
        $("#software_list").append('<option value="'+soft+'">'+data['Software'][soft]+'</option>');
        }



		//Fill Department select with an OIT maintained (not Banner) list of departments.
    	for(var dept in data['Departments']){
            $("#form_department").append('<option value="'+dept+'">'+data['Departments'][dept]+'</option>');
        }
		//If the user has LDAP information, auto-fill some of the fields for them.
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
/**
	Displays the order form preview, and also uses the total calculation
		to show/hide the Purchasing Information fieldset.
	Relies on global var software_purchase for purchase data.
*/
function displaypreview(event){ 
    $("#preview_tbody").empty();
	$("#form_hidden").empty();
    var subtotal = 0;
    for(var i=0;i<software_purchase.length;i++) {
        var index = software_purchase[i];
        var current = software[index];
        subtotal += parseFloat(current['Price']);
        var name = software_names[index];
        var row = $('<tr><td class="name_cell">'+name+'</td><td class="price_cell">$'+current["Price"]+'</td><td><button class="preview_remove" index="'+i+'">x</button></td></tr>');
        if(event == 'new' && i == software_purchase.length-1){
        	row.fadeIn('fast');
        }
        $("#preview_tbody").append(row);
		var hidden = $('<input type="hidden">');
		hidden.attr('name','software[]');
		hidden.attr('value',current['SID']);
		$("#form_hidden").append(hidden);
    }
    var serials = $("#form_serials").val();
    serials = serials.split(",");
    var countstr = '';
    if(serials.length > 1 && subtotal > 0){
    	total = serials.length*subtotal;
    	total = total.toFixed(2);
    	countstr = ' x '+serials.length+' = $'+total+'';
    }
	if(subtotal == 0){
		$('#form_purchase').hide();
	}
	else{
		$('#form_purchase').show();
	}
    $("#preview_total").html('Total: $'+subtotal.toFixed(2)+countstr);
}
/**
	An extra hook into jQuery UI's autocomplete callback.
*/
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
/**
	More of a tooltip than an error, this helps to guide the user
		towards completing form fields.
*/
function displayerror(dom,text){
	var error = $('<div class="input_error">');
	error.append('<div class="input_error_arrow">');
	error.append('<div class="input_error_text">'+text+'</div>');
	var pos = dom.offset();
	error.css('left',pos.left+dom.outerWidth(true)+2);
	error.css('top',pos.top-10);
	error.data('parent',dom.selector);
	$('body').append(error);
	error.delay(2000).fadeOut('fast',function(){
		$(this).remove();
	});
}
/**
	Equivalent to window.onload, this defines various jQuery event
		callbacks, some of which need to wait for dynamic content before
		registering themselves.
*/
$(function() { 
	displaypreview();
	/**
		Calls a callback function that does things like loading software
			notes and price.
	*/
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
	/**
		This is where most of the software form validation comes in,
			and is of course mimicked server side.
	*/
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
		//OSID must match the current software.
        if(current['OSID'] !== os && current['OSID'] !== "3" && os !== "3"){ 
        	displayerror($(this),'This software is not available for '+$("#form_os option:selected").text());
        	return; 
        }
		if(jQuery.inArray(index, software_purchase) !== -1){
			displayerror($(this), 'This software is already in the order.');
			return;
		}
        software_purchase.push(index);
        displaypreview('new');
    });
	//Removing software from the order is pretty simple. 
	//Might want to use data instead of an attribute.
    $(".preview_remove").live('click', function(){
        var index = $(this).attr('index');
        software_purchase.splice(index,1);
        $(this).parents('tr').fadeOut('fast',function(){
        	displaypreview();
        });
    });
	//If they added a new serial the software total changes (total*num_serials)
	//displaypreview() handles this logic.
    $("#form_serials").blur(function(){
    	displaypreview();
    });
	//If they picked a new OS then we'll have to clean up the order.
	//Software in the order MUST match the selected Operating System.
    $("#form_os").change(function(){
    	var new_purchase = new Array();
    	for(var i=0;i<software_purchase.length;++i){
    		var index = software_purchase[i];
    		var os = software[index]["OSID"];
    		if(os == $(this).val() || os == "3" || $(this).val() == "3"){
    			new_purchase.push(software_purchase[i]);
    		}
    	}
    	software_purchase = new_purchase;
    	displaypreview();
    });
	$(".required").blur(function(){
		if($(this).val().trim() == ""){
			displayerror($(this), 'This field requires a value.');
		}
	});
	//Form submit doesn't do too many checks, just that required fields
	//are not empty and that the software order isn't empty.
	$("#form").submit(function(e){
		var required = $(".required:visible");
		var required_flag = false;
		var exit_flag = false;
		for(var i=0;i<required.length;++i){
			var current = $(required[i]);
			if(current.val().trim() == ""){
				required_flag = true;
				exit_flag = true;
			}
		}
		if(required_flag){
			displayerror($("#form_submit"), 'Please fill in required form fields.');
			$('.required:visible').trigger('blur');
		}
		else if(software_purchase.length == 0){
			displayerror($("#form_submit"), 'Please add at least one software to the order.');
			exit_flag = true;
		}
		if($('#form_index').val().length > 6){
			displayerror($("#form_index"), 'Index cannot be more than 6 characters.');
			exit_flag = true;
		}
		if(exit_flag){
			e.preventDefault(); //prevent the POST!
			return false;
		}
		return true;
	});
	$('.required').before('<font color="red">*</font>');
});

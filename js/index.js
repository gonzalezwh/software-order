//IE9 fix
if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, ''); 
  }
}
/*
	Class: SoftwareForm (js/index.js)
	Handles UI events for the Order form, and does a lot of client-side processing to make the
	order experience smoother for the user. 
*/
function SoftwareForm(){
    //locals
    this.software = new Array();
    this.software_names = new Array();
    this.software_purchase = new Array();
    //ajax prep
    $.ajaxSetup({
        type: "GET",
        url: "ajax.php", 
        dataType: "json",
        context: this
    });
    /*
    	Function: init
    	Essentially a class constructor, gets and formats form data, draws the Order Preview
    	and preps all the jQuery events associated with the UI.
    */
    this.init = function(){
        this.getFormData();
        this.displayPreview();
        this.prepjQueryEvents();
        //add autocomplete to software field
        $("#software_input").autocomplete({
            source: this.software_names,
            select: $.proxy(this.autoCompleteResponse,this)
        });
        //mark required fields to make it easier for users
        $('.required').before('<font color="red">*</font>');
    }
    /*
    	Function: getFormData
    	Performs a request to the AJAX enpoint, then processes all the data into locals and
    	the dynamic HTML elements (Department list, Authorizer list, LDAP fields). Should
    	only be called once.
    */
    this.getFormData = function(){
        $.ajax({ 
            data: {request: "getformdata"}, 
            success: function(data) {
                for(var i in data['Softwares']){
                    var current = data['Softwares'][i];
                    var vendor = data['Vendors'][current['VID']]['Name'];
                    var os = data['OSlist'][current['OSID']]['Name'];
                    this.software.push(current);
                    //this saves time
                    this.software_names.push(vendor+" "+current["Name"]+" ("+os+")");
                }
                //Create empty option for Authorizer.
                var option = $('<option value=""></option>');
                $('#form_authorizer').append(option);
                //Fill the Authorizer select with all the active Authorizers
                for(var i in data['Authorizers']){
                    var name = data['Authorizers'][i]['FullName'];
                    var department = data['Authorizers'][i]['Department'];
                    var option = $('<option value='+data['Authorizers'][i]['AID']+'>'+department+'-'+name+'</option>');
                    var software = data['Authorizers'][i]['Software'];
                    var option = $('<option value='+data['Authorizers'][i]['AID']+'>'+software+'-'+name+'</option>');
                    $('#form_authorizer').append(option);
                }

                //Fill Software select with an OIT maintained (not Banner) list of software.
                 $("#form_software").hide();
                 for(var soft in data['Software']){
                 $("#form_software").append('<option value="'+soft+'">'+data['Software'][soft]+'</option>');
                 }
                 $("#form_software").show();


                //Fill Department select with an OIT maintained (not Banner) list of departments.
                $("#form_department").hide();
                for(var dept in data['Departments']){
                    $("#form_department").append('<option value="'+dept+'">'+data['Departments'][dept]+'</option>');
                }
                $("#form_department").show();

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
    }
    /*
    	Function: displayPreview
    	Presents a software order preview based on the local data in software_purchase.
    */
    this.displayPreview = function(event){ 
        $("#preview_tbody").empty();
        $("#form_hidden").empty();
        var subtotal = 0;
        for(var i=0;i<this.software_purchase.length;i++) {
            var index = this.software_purchase[i];
            var current = this.software[index];
            subtotal += parseFloat(current['Price']);
            var name = this.software_names[index];
            var row = $('<tr><td class="name_cell">'+name+'</td><td class="price_cell">$'+current["Price"]+'</td><td><button class="preview_remove" index="'+i+'">x</button></td></tr>');
            if(event == 'new' && i == this.software_purchase.length-1){
                row.fadeIn('fast');
            }
            $("#preview_tbody").append(row);
            var hidden = $('<input type="hidden">');
            //this will be pulled in during the POST request
            hidden.attr('name','software[]');
            hidden.attr('value',current['SID']);
            $("#form_hidden").append(hidden);
        }
        //calculate a total times the number of serials
        var serials = $("#form_serials").val();
        serials = serials.split(",");
        var countstr = '';
        if(serials.length > 1 && subtotal > 0){
            total = serials.length*subtotal;
            total = total.toFixed(2);
            countstr = ' x '+serials.length+' = $'+total+'';
        }
        //we'll need authorizer info if this is a non-free order
        if(subtotal == 0){
            $('#form_purchase').hide();
        }
        else{
            $('#form_purchase').show();
        }
        $("#preview_total").html('Total: $'+subtotal.toFixed(2)+countstr);
    }
    /*
    	Function: autoCompleteResponse
    	Prevents normal autocomplete response so that the input can be post-processed.
    	
    	See Also:
    		<updateSoftwareInputData>
    */
    this.autoCompleteResponse = function(event,ui){
        var input = $("#software_input");
        input.val(ui.item.value);
        form.updateSoftwareInputData(input);
    }
    /*
    	Function: updateSoftwareInputData
    	Shows the user the price of the entered software and any notes that may be available. 
    	
    	Parameters:
    		An input string, that should (based on the autocomplete list) match somewhere in
    		the local array software_names.
    */
    this.updateSoftwareInputData = function(input){
        var index = jQuery.inArray(input.val(), this.software_names);
        var price = "";
        var notes = "";
        if(index !== -1){
            price = "$"+this.software[index]['Price'];
            notes = this.software[index]['Notes'];
        }
        else{
            price = "Unknown Software";
        }
        input.parent().children("#software_price").html('Price: '+price);
        input.parent().children("#software_notes").html(notes);
    }
    /*
    	Function: displayError
    	Display a tooltip at the location of the dom's box element.
    	
    	Parameters:
    		dom - A jQuery Object
    		text - Error string
    */
    this.displayError = function(dom,text){
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
    /*
    	Function: prepjQueryEvents
    	Proxies all UI events so that 'this' behavior stays consistent throughout class members.
    */
    this.prepjQueryEvents = function(){
        $("#software_input").blur($.proxy(this.triggerSoftwareInput, this));
        $("#software_add").click($.proxy(this.triggerSoftwareAdd,this));
        $(".preview_remove").live('click', $.proxy(this.triggerPreviewRemove,this));
        $("#form_serials").blur($.proxy(this.displayPreview,this));
        $("#form_os").change($.proxy(this.triggerFormOS,this));
        $(".required").blur($.proxy(this.triggerRequired,this));
        $("#form").submit($.proxy(this.triggerFormSubmit,this));
    }
    /*
    	Function: triggerSoftwareInput
    	If the user manually types something in (usually on accident), it needs to be processed
    	by <updateSoftwareInputData>
    */
    this.triggerSoftwareInput = function(){
        var input = $("#software_input");
        this.updateSoftwareInputData(input);
    }
	/*
		Function: triggerSoftwareAdd
		This does some basic error checking of software before adding it to the order.
		
		Parameters:
			e - A jQuery Event
	*/
    this.triggerSoftwareAdd = function(e){
        var target = $(e.currentTarget);
        var name = $("#software_input").attr("selected", "selected").val();
        var os = $("#form_os").val();
        if(jQuery.trim(name) == ""){ //empty case
            return;
        }
        var index = jQuery.inArray(name, this.software_names);
        var current = this.software[index];
        if(!current){ //not a real software
            return;
        }
        //OSID must match the current software.
        if(current['OSID'] !== os && current['OSID'] !== "3" && os !== "3"){ 
            this.displayError(target,'This software is not available for '+$("#form_os option:selected").text());
            return; 
        }
        if(jQuery.inArray(index, this.software_purchase) !== -1){
            this.displayError(target, 'This software is already in the order.');
            return;
        }
        this.software_purchase.push(index);
        this.displayPreview('new');
    }
	/*
		Function: triggerPreviewRemove
		Removes a row (software) from the order preview.
		
		Parameters:
			e - A jQuery Event
	*/
    this.triggerPreviewRemove = function(e){
        var target = $(e.currentTarget);
        var index = target.attr('index');
        this.software_purchase.splice(index,1);
        this.displayPreview();
        //target.parents('tr').fadeOut('fast',this.displayPreview());
    }
	/*
		Function: triggerFormOS
		If the OS changes this will delete invalid software from the Order preview.
		
		Parameters:
			e - A jQuery Event
	*/
    this.triggerFormOS = function(e){
        var target = $(e.currentTarget);
        var new_purchase = new Array();
        for(var i=0;i<this.software_purchase.length;++i){
            var index = this.software_purchase[i];
            var os = this.software[index]["OSID"];
            if(os == target.val() || os == "3" || target.val() == "3"){
                new_purchase.push(this.software_purchase[i]);
            }
        }
        this.software_purchase = new_purchase;
        this.displayPreview();
    }
	/*
		Function: triggerRequired
		Warn the user if a required field is empty.
		
		Parameters:
			e - A jQuery Event
	*/
    this.triggerRequired = function(e){
        var target = $(e.currentTarget);
        if(target.val().trim() == ""){
            this.displayError(target, 'This field requires a value.');
        }
    }
	/*
		Function: triggerFormSubmit
		Does basic error checking before POST'ing the order. All logic here is replicated in
		PHP.
		
		Parameters:
			e - A jQuery Event
	*/
    this.triggerFormSubmit = function(e){
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
            this.displayError($("#form_submit"), 'Please fill in required form fields.');
            $('.required:visible').trigger('blur');
        }
        else if(this.software_purchase.length == 0){
            this.displayError($("#form_submit"), 'Please add at least one software to the order.');
            exit_flag = true;
        }
        if($('#form_index').val().length > 6){
            this.displayError($("#form_index"), 'Index cannot be more than 6 characters.');
            exit_flag = true;
        }
        if(exit_flag){
            e.preventDefault();
            return false;
        }
        return true;
    }
    this.init();
}
//create a local instance of the form
var form;
$(function(){
    form = new SoftwareForm();
});

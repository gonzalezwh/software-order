/*
	Class: AdminApp (admin/js/admin.js)
	This class controls the UI for editing MySQL data via the various /admin/ pages in the 
	software-order form.
    Before it works it needs the following set up:
    
        - var requesttype : The ajax request type for this table
        - var tableindex : The primary index of the table
        - function post_gettabledata(data) : Called after initial ajax.
        - function post_displaytable() : You can use this to add extra data to table cells after they
                have been filled.
        - function getquickeditoptions(name) : When the user clicks on a cell you can optionally display a dropdown,
                this gives the AdminApp the array of options (see software.js for an example)
*/
function AdminApp(){
    this.edits = new Array();
    this.tabledata = new Array();
    this.index = 0;
    this.increment = 499;
    $.ajaxSetup({
        type: "GET",
        url: "../ajax.php",
        dataType: "json",
        context: this,
    });
    /*
        Function: getTableData
        Performs an AJAX request to get table data. Uses HTML markup and Dom Data
        to decide how to filter data.
    */
    this.getTableData = function(){
        var field = $('#filter_key').val();
        var value = $('#filter_value').val();
        var operator = $('#filter_function').val();
        var options = {index: this.index, assoc: 'false'};
        var th = $('.selected');
        var order = th.data('order');
        var orderby = th.html();
		options['field'] = field;
		options['value'] = value;
		options['operator'] = operator;
        if(order !== undefined && orderby !== undefined){
            options['order'] = order;
            options['orderby'] = orderby;
        }
        $.ajax({
            data: {
                request: 'getdata',
                type: requesttype,
                options: options,
            },
            success: function(data){
                this.tabledata = new Array();
                for(var i in data){
                    this.tabledata[i] = data[i];
                }
                this.displayTable();
                post_gettabledata(data);
            },
            error: function(data){
				var message = $('<div class="error">No results found.</div>');
				$('#message_box').empty();
				$('#message_box').append(message);
				message.fadeOut(2000, function(){this.remove()});
            }
        });
    }
    /*
        Function: prepjQueryEvents
        Proxies all the jQuery events involved in the GUI to maintain class-like behavior.
    */
    this.prepjQueryEvents = function(){
        this.displayLoadBar($('#table'));
        $('.datacell').live('click', $.proxy(this.triggerDataCell, this));
        $('.quick_edit').live('blur', $.proxy(this.triggerQuickEdit,this));
        $(".edit_button").click($.proxy(this.triggerEditButton, this));
        $(".add_button").click($.proxy(this.triggerAddButton, this));
        $(".remove_button").live('click', $.proxy(this.triggerRemoveButton, this));
        $(".remove_confirm").live('click', $.proxy(this.triggerRemoveConfirm, this));
        $(".remove_deny").live('click', $.proxy(this.triggerRemoveDeny, this));
        $('#filter_value').blur($.proxy(this.triggerFilter, this));
        $('#filter_key').change($.proxy(this.triggerFilter, this));
        $('#filter_function').change($.proxy(this.triggerFilter, this));
        $('#last_page').click($.proxy(this.triggerLastPage, this));
        $('#next_page').click($.proxy(this.triggerNextPage, this));
        $('th').click($.proxy(this.triggerSort, this));
    };
    /*
        Function: triggerSort
        Adds a data value to a 'th' cell, which is taken by getTableData() to
        process sorting during the AJAX call. 
        
        See Also:
            <getTableData>
    */
    this.triggerSort = function(e){
        var target = $(e.currentTarget);
        if(target.data('order') !== undefined){
            if(target.data('order') == 'ASC'){
                target.data('order', 'DESC');
            }
            else{
                target.data('order', 'ASC');
            }
        }
        else{
            $('th').removeData('order');
            $('th').removeClass('selected');
            target.data('order','ASC');
            target.addClass('selected');
        }
        this.getTableData();
    }
    /*
        Function: triggerNextPage
        Increments the index by the set increment (default 499)
    */
    this.triggerNextPage = function(){
        this.index += this.increment;
        this.triggerFilter();
    }
	/*
        Function: triggerLastPage
        Decrements the index by the set increment (default 499)
    */
    this.triggerLastPage = function(){
        this.index -= this.increment;
        this.triggerFilter();
    }
    /*
    	Function: triggerFilter
    	Calls getTableData() which uses the filter values to perform an AJAX request.
    	
    	See Also:
    		<getTableData>
    */
    this.triggerFilter = function(){
        this.getTableData();
    }
    /*
    	Function: displayLoadBar
    	Draws a loading graphic, useful when performing big AJAX requests
    	
    	Parameters:
    		tabledom - Where you want the loadbar to appear
    */
    this.displayLoadBar = function(tabledom){ 
        $(tabledom).after($('<img id="ajax_icon" src="img/ajax-loader.gif"></img>'));
    }
    /*
    	Function: triggerDataCell
    	Replaces a cell with some sort of input, which input element it swaps depends on 
    	HTML markup
    	
    	Parameters:
    		e - A jQuery event object
    */
    this.triggerDataCell = function(e){
        var target = $(e.currentTarget);
        var td = target.closest('td');
        var th = td.closest('table').find('th').eq(td.index());
        if(target.children().length > 0 || th.attr('class') == 'uneditable'){ 
            return;
        }
        var val = target.html();
        var newdom = this.getquickeditdom(th.data('input'),getquickeditoptions(th.html()));
        newdom.attr('value',val);
        newdom.data('old-value',val);
        target.empty();
        if(th.data('input') == 'textarea'){
            target.css('max-width','500px');
            target.css('max-height','200px');
        }
        target.append(newdom);
        target.children().focus();
    };
    /*
    	Function: triggerQuickEdit
    	Evaluates input for errors and records the row index in the array 'edits', which is
    	referenced later in triggerEditButton.
    	
    	Parameters:
    		e - A jQuery event object
    		
    	See Also:
    		<triggerEditButton>
    */
    this.triggerQuickEdit = function(e){
        var target = $(e.currentTarget);
        var td = target.closest('td');
        var tr = td.closest('tr');
        var th = td.closest('table').find('th').eq(td.index());
        var val = target.val();
        var old_val = target.data('old-value');
        if(!old_val){
            old_val = "";
        }
        var type = th.data('type');
        var input = processinput(val, type);
        var id = tr.data('id');
        if(input !== "" && input == false && input !== 0){ //something wrong with the input, usually type
            target.parent().removeAttr('style');
            target.parent().html(old_val);
            var message = $('<div class="error">ERROR: Input must be of type '+type+'</div>');
            $('#message_box').empty();
            $('#message_box').append(message);
            message.fadeOut(2000, function(){target.remove()});
        }
        else{
            target.parent().removeAttr('style');
            target.parent().html(input);
            if(val !== old_val){ //we need to record this change
                if(jQuery.inArray(id, this.edits) == -1){
                    this.edits.push(id);
                }
            }
        }
    };
    /*
    	Function: triggerEditButton
    	Goes through each edited row and pulls all the data into an object, which is then
    	sent to the AJAX endpoint to be edited in SQL. 
    	
    	Parameters:
    		e - A jQuery event object
    */
    this.triggerEditButton = function(e){
        var ajaxcalls = new Array();
        for(var i=0;i<this.edits.length;++i){
            var row = $('[data-id='+this.edits[i]+']');
            var edit = {};
            var children = row.children('td');
            for(var j=0;j<children.length;++j){
                var column = children[j]['children'][0];
                var value = column.innerHTML;
                var key = $('table').find('th').eq(j).html();
                edit[key] = value;
            }
            ajaxcalls.push($.ajax({
                dataType: "json",
                data: {request: 'update', type: requesttype, options:edit},
                fail: function(data){
                	
                }
            }));
            $('.submit_buttons').html('Please wait, '+(i/this.edits.length)*100+'% complete');
        }
        $.when.apply($, ajaxcalls).done(function(){ //async callback for when all ajax calls are done
            location.reload(true);
        });
    };
    /*
    	Function: triggerAddButton
    	Calls to the AJAX endpoint to make an empty row, then reloads the page.
    	
    	Parameters:
    		e - A jQuery event object
    */
    this.triggerAddButton = function(e){
        $.ajax({ 
            data: {request: 'add',type: requesttype},
            success: function(data) {
                location.reload(true);
            }
        });
    };
	/*
    	Function: triggerRemoveButton
    	Shows a popup to confirm row deletion, masks the rest of the screen. 
    	
    	Parameters:
    		e - A jQuery event object
    		
    	See Also:
    		<triggerRemoveConfirm>
    */
    this.triggerRemoveButton = function(e){
        var target = $(e.currentTarget);
        $('#mask').remove(); //cleanup in case of errors
        var mask = $('<div id="mask">');
        var id = target.parent().data('id');
        mask.css('height',$(document).height());
        mask.css('width',$(document).width());
        var popup = $('<div id="remove_alert">');
        popup.data('id',id);
        popup.append('<div>Are you sure you want to remove this item?</div>');
        popup.append('<button class="remove_confirm">Yes</button>');
        popup.append('<button class="remove_deny">No</button>');
        mask.append(popup);
        mask.fadeIn();
        $('body').append(mask);
    };
	/*
    	Function: triggerRemoveConfirm
    	Call the AJAX endpoint to remove the row.
    	
    	Parameters:
    		e - A jQuery event object
    		
    	See Also:
    		<triggerRemoveButton>
    */
    this.triggerRemoveConfirm = function(e){
        var target = $(e.currentTarget);
        var id = target.parent().data('id');
        $.ajax({
            data: {request: 'remove', type: requesttype, options:id},
            success: function(data) {
                location.reload(true);
            }
        });
        target.parent().parent().remove();
    };
	/*
    	Function: triggerRemoveDeny
    	Remove the Remove Confirm popup.
    	
    	Parameters:
    		e - A jQuery event object
    		
    	See Also:
    		<triggerRemoveButton>
    */
    this.triggerRemoveDeny = function(e){
        var target = $(e.currentTarget);
        target.parent().parent().fadeOut(400,function(){
            this.parent().parent().remove();
        });
    };
	/*
    	Function: displayTable
    	References tabledata to draw a table with all the td classes associated with jQuery 
    	events.
    */
    this.displayTable = function(){ 
        $("#ajax_icon").remove();
        $('#tbody').empty();
        $('#tbody').hide();
        var keys = $("th");
        for(var i in this.tabledata){
            var current = this.tabledata[i];
            var row = $('<tr>');
            row.attr('data-id',current[tableindex]);
            for(var j=0;j<keys.length;++j){
                var key = keys[j].innerHTML;
                var col = $('<td>');
                var div = $('<div>');
                div.html(current[key]);
                div.attr('class', 'datacell');
                col.append(div);
                row.append(col);
            }
            row.append('<button class="remove_button">Remove</button>');
            $('#tbody').append(row);
        }
        $('#tbody').fadeIn();
        post_displaytable();
    };
	/*
    	Function: getquickeditdom
    	Returns the dom element appropriate for the input. 
    	
    	Parameters:
    		input - string representing the type of input required for this field
    		options - an object required for select inputs, defined in external .js files
    */
    this.getquickeditdom = function(input,options){
        if(input == 'textfield'){
            return $('<input type="text" class="quick_edit"></input>');
        }
        else if(input == 'select'){
            var select =  $('<select class="quick_edit"></select>');
            for(var key in options){
                var option = $('<option></option>');
                option.attr('value',key);
                option.html(options[key]);
                select.append(option); 
            }
            return select;
        }
        else if(input == 'textarea'){
            return $('<textarea class="quick_edit"></textarea>');
        }
    };
    /*
    	Function: processinput
    	Validates and processes the input. Pretty simple, and replicated in SQL if anything
    	if formatted weird.
    	
    	Parameters:
    		val - string representing input value
    		type - string representing input type
    */
    function processinput(val, type){
        if(type == 'string'){ 
            return val.toString();
        }
        else if(type == 'int'){
            var temp =  parseInt(val);
            if(isNaN(temp)){
                return false;
            }
            else{
                return temp;
            }
        }
        else if(type == 'float'){
            var temp =  parseFloat(val);
            if(isNaN(temp)){
                return false;
            }
            else{
                return temp;
            }
        }
    }; 
    /*
    	Function: displayTableSorter
    	Displays a dynamic select menu based on the HTML markup.
    */
    this.displayTableSorter = function(){
        var columns = $.find('th');
        $('#filter_key').empty();
        for(var i=0;i<columns.length;++i){
            var key = columns[i].innerHTML;
            var option = $('<option></option>');
            option.attr('value',key);
            option.html(key);
            $('#filter_key').append(option);
        }
    }();
    //initialize the events and data
    this.prepjQueryEvents();
    this.getTableData();
}
var app;
$(function(){
    app = new AdminApp();
});
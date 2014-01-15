<?php
    include_once('cas_init.php');
    include_once('auth.php');
?>
<script src="js/jquery-1.8.2.min.js"></script>
<script src="js/jquery-ui.js"></script>
<script src="js/index.js"></script>
<html>
<head>
    <title>OIT Software Order Form</title>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" href="css/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="css/main.css" />
    <link rel="stylesheet" type="text/css" href="css/index.css" />
    <link href="img/favicon.ico" type="image/x-icon" rel="icon" />
</head>
<body>
    <div id="content">
        <? include("header.html"); ?>
        <form id="form" action="submit.php" method="post">
            <fieldset id="serial_info">
            	<legend>Computer Information</legend>
            	<label for="form_os">
					Operating System: <select id="form_os" name="os">
						<option value="1">Windows</option>
						<option value="2">Mac</option>
                        <option value="3">Virtual Machine</option>
					</select>
            	</label>
            	<label for="form_serials">
                    Computer serial(s): <input type="text" id="form_serials" name="serials" class="required"></input>
                    <div class="helper_text">Separate serials by commas, ex: (923823,102938)</div>
                </label>
                <label for="form_users">
                    Computer user(s): <input type="text" id="form_users" name="users"></input>
                    <div class="helper_text">Enter information about who the order is for here.</div>
                </label>
            </fieldset> 
                <div id="software_container">  
                    Software Name: <input type="hidden" id="software_input"><select id="software_list" name="software"></select></input><button type="button" id="software_add">Add</button>
                    <div id="software_price">Price: </div>
					<div id="software_notes"></div>
                    <div class="helper_text"><a href="software_list.php" target="_blank">View available software</a></div>
                </div>
            </fieldset>
            <fieldset id="contact_info">
                <legend>Contact Information</legend>
                <label for="form_name">
                    Full Name: <input type="text" id="form_name" name="name" class="required"></input>
                </label>
                <label for="form_odin">
                    Odin Username: <input type="text" id="form_odin" name="odin" class="required"></input>
                </label>
                <label for="form_phone">
                    Phone Number: <input type="text" id="form_phone" name="phone" class="required"></input>
                </label>
                <label for="form_email">
                    Email Address: <input type="text" id="form_email" name="email" class="required"></input>
                </label>
                <label for="form_department">
                    Department: <select id="form_department" name="department"></select>
                </label>
                <label for="form_location">
                    Delivery Location: <input type="text" id="form_location" name="location"></input>
                </label>
            </fieldset>
             <fieldset id="form_purchase">
             	<div class="helper_text">An Account Authorizer is the individual in charge of departmental purchases.</div>
                <legend>Purchasing Information</legend>
                <label for="form_authorizer">
                    Account Authorizer: <select id="form_authorizer" name="authorizer" class="required"></select>
                    <div class="helper_text">Don't see your authorizer in the list? Contact Nate Henry at <a href="mailto:nhenry@pdx.edu">nhenry@pdx.edu.</a></div>
                </label>
                <label for="form_index">
                    Account Index: <input type="text" id="form_index" name="index"></input>
                    <div class="helper_text">If you do not know your code your Account Authorizer will provide it for  us. Index codes are six characters. If you have a longer code please enter it in the "Notes" section below.</div>
                </label>
            </fieldset>
            <fieldset>
                <legend>Notes</legend>
                <textarea id="form_notes" name="notes"></textarea>
                <div class="helper_text">Enter additional information or special requests here.</div>
            </fieldset>
            <div id="form_hidden">
            
            </div>
            <input type="submit" value="Submit Software Order" id="form_submit">
            <div id="required_helper">"<font color="red">*</font>" denotes a required field.</div>
        </form>
        
        <div id="preview">
            Order Form Preview:
            <form id="order_form">
                
            </form>
            <table id="preview_table">
                <thead id="preview_thead">
                    <tr>
                        <th>Software</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody id="preview_tbody"></tbody>
            </table>
            
            <div id="preview_total"></div>
        </div>
    </div>
</body>
</html>

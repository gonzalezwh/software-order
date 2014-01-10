<?php
	include("prep.php");
    if(!check_read_access('order', $user)){ 
    	die('Your account does not have access to this page.');
    }
?>
<script src="js/orders.js"></script>
<script src="js/admin.js"></script>
<html>
<? include("head.html") ?>
<body>
		<? include("header.html"); ?>
            <input type="checkbox" id="show_historic"><label for="show_historic">Show Items with JVNumber?</label></input>

        	<table id="table">
                <thead id="thead">
                    <tr>
                        <th class="uneditable">OID</th>
                        <th class="editable" data-type="int" data-input="textfield">RTNumber</th>
                        <th class="editable" data-type="string" data-input="textfield">DateOrdered</th>
                        <th class="editable" data-type="string" data-input="textfield">DateInstalled</th>
                        <th class="editable" data-type="string" data-input="textfield">SoftwareName</th>
                        <th class="editable" data-type="string" data-input="textfield">CompOSName</th>
                        <th class="editable" data-type="float" data-input="textfield">Price</th>
                        <th class="editable" data-type="string" data-input="textfield">CompSerial</th>
                        <th class="editable" data-type="string" data-input="textfield">ReqOdin</th>
                        <th class="editable" data-type="string" data-input="textfield">Authorizer</th>
                        <th class="editable" data-type="string" data-input="textfield">AccountIndex</th>
                        <th class="editable" data-type="string" data-input="textfield">OETCNumber</th>
                        <th class="editable" data-type="string" data-input="textfield">PONumber</th>
                         <th class="editable" data-type="string" data-input="textfield">JVNumber</th>
						 <th class="editable" data-type="string" data-input="textfield">Notes</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
			<? include("footer.html"); ?>
</body>
</html>
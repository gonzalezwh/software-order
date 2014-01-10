<?php
	include("prep.php");
    if(!check_read_access('vendor')){ 
    	die('Your account does not have access to this page.');
    }
?>
<script src="js/vendors.js"></script>
<script src="js/admin.js"></script>
<html>
<? include("head.html") ?>
<body>
        <? include("header.html"); ?>
            <input type="checkbox" id="show_historic"><label for="show_historic">Show Historic Items?</label></input>
        	<table id="table">
                <thead id="thead">
                    <tr>
                        <th class="uneditable">VID</th>
                        <th class="editable" data-type="string" data-input="textfield">Name</th>
                        <th class="editable" data-type="string" data-input="textarea">Contact</th>
                        <th class="editable" data-type="string" data-input="textarea">Description</th>
                        <th class="editable" data-type="int" data-input="select">PreviousVID</th>
                        <th class="editable" data-type="int" data-input="textfield">Historic</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
        <? include("footer.html"); ?>
</body>
</html>
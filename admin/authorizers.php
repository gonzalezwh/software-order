<?php
	include("prep.php");
    if(!check_read_access('authorizer')){ 
    	die('Your account does not have access to this page.');
    }
?>
<script src="js/authorizers.js"></script>
<script src="js/admin.js"></script>
<html>
<? include("head.html") ?>
<body>
    <?  include("header.html"); ?>
        <table id="table">
            <thead id="thead">
                <tr>
                    <th class="uneditable">AID</th>
                    <th class="editable" data-type="int" data-input="textfield">Active</th>
                    <th class="editable" data-type="string" data-input="textfield">Odin</th>
                    <th class="editable" data-type="string" data-input="textfield">FullName</th>
                    <th class="editable" data-type="string" data-input="textfield">Department</th>
                    <th class="editable" data-tyor="string" data-input="textfiled">Software</th>
                 </tr>
            </thead>
            <tbody id="tbody"></tbody>
        </table>
    <? include("footer.html"); ?>
</body>
</html>

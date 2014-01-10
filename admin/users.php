<?php
	include("prep.php");
    if(!check_read_access('user')){ 
    	die('Your account does not have access to this page.');
    }
?>
<script src="js/users.js"></script>
<script src="js/admin.js"></script>
<html>
<? include("head.html") ?>
<body>
		<? include("header.html"); ?>
			GID Reference:
			1: Can edit/view all pages,
			2: Can edit Orders only,
			3: Can view Orders only
        	<table id="table">
                <thead id="thead">
                    <tr>
                        <th class="uneditable">UID</th>
                        <th class="editable" data-type="string" data-input="textfield">Odin</th>
                        <th class="editable" data-type="int" data-input="textfield">GID</th>
                        <th class="editable" data-type="int" data-input="textfield">Flag</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
			<? include("footer.html"); ?>
</body>
</html>
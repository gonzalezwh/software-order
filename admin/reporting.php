<?php
	include("prep.php");
    if(!check_read_access('order')){ 
    	die('Your account does not have access to this page.');
    }
?>
<html>
<? include("head.html") ?>
<script src="js/reporting.js"></script>
<body>
<div id="header">
	<div id="title"><img src="../img/logo.png"></img>OIT Software Order Form</div>
	<div id="navigation_bar">
		<ul id="navigation_list">
			<li><a href="../">Home</a></li>|
			<li><a href="software.php">Software</a></li>|
			<li><a href="vendors.php">Vendors</a></li>|
			<li><a href="users.php">Users</a></li>|
			<li><a href="authorizers.php">Authorizers</a></li>|
			<li><a href="orders.php">Orders</a></li>|
			<li><a href="reporting.php">Account Reporting</a></li>
		<ul>
	</div>
</div>
<div id="container">
    <div class="support_message">
        <div class="support_title">Order Reporting</div>
        <div class="support_text"><p>You can enter multiple values into each field, seperated by commas.</div>
        <div class="support_text">
            <form id="form" action="batch_pdf.php" method="get">
                <p>PO#: <input type="text" name="po"></input>
                <br>JV#: <input type="text" name="jv"></input>
                <br>OETC#: <input type="text" name="oetc"></input>
                <input type="submit" value="Submit">
            </form>
        </div>
    </div>
</div>
</body>
</html>
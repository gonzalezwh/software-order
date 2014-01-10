<?php
    include_once('cas_init.php');
    include_once "auth.php";
    include_once "sql.php";
?>
<script src="js/jquery-1.8.2.js"></script>
<script src="js/jquery-ui.js"></script>
<script src="js/myorders.js"></script>
<html>
<head>
    <title>OIT Software Order Form - My Orders</title>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="css/main.css" />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui.css" />
    <link href="img/favicon.ico" type="image/x-icon" rel="icon" />
</head>
<style>
    body{margin-bottom:500px;}
</style>
<body>
    <? include("header.html"); ?>
    <div class="support_message"><a name="top"></a>
        You can use this page to look up historical orders for your records.<br>
        Orders that have not been completed (installed) have an "Installed Start Date" of 0000-00-00. 
        You may manually enter this if you want to view current orders.
        <p>Installed Start Date: <input type="text" id="start_date"></input>
        Installed End Date: <input type="text" id="end_date"></input>
        <button id="update">Update</button>
        <p><div class="support_subtitle">Orders you have requested</div>
            <div class="support_table">
                <table id="req_table">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="req_download"></div><p>
            <div class="support_subtitle">Orders you have authorized</div>
            <div class="support_table">
                <table id="auth_table">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="auth_download"></div>
    </div>
</body>
</html>
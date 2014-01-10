<?php
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 1);
    define('APP_DIR', dirname(__FILE__));
    include_once APP_DIR."/library/CAS-1.3.1/CAS.php";
    phpCAS::client(CAS_VERSION_2_0,'sso.pdx.edu',443,'/cas');
    phpCAS::setNoCasServerValidation();
    phpCAS::forceAuthentication();
    $odin = phpCAS::getUser(); 
?>

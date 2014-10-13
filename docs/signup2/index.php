<?php
// Send main headers
header('Last-Modified: ' . date('r')); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Pragma: no-cache");

// LOAD LIBS:
	include('modules/engine/api.mysql.php');
        include('modules/engine/api.lightastral.php');
        include('modules/engine/api.compat.php');
        include('modules/engine/api.signup.php');
        
$db = new MySQLDB();

$signup=new SignupService();
deb($signup->renderForm());

sn_ShowTemplate();
?>
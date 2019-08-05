<?php

/*
 * CaTV fee processing 
 */

if ($_GET['action'] == 'catvfeeprocessing') {
    $currentYear = date("Y");
    //previous month charge fee
    if ($alterconf['CATV_BACK_FEE']) {
        $currentMonth = date("m");
        if ($currentMonth == 1) {
            $currentMonth = 12;
        } else {
            $currentMonth = $currentMonth - 1;
        }
    } else {
        $currentMonth = date("m");
    }

    if (catv_FeeChargeCheck($currentMonth, $currentYear)) {
        catv_FeeChargeAllUsers($currentMonth, $currentYear);
    } else {
        die('ERROR:ALREADY_CHARGED');
    }
    log_register("REMOTEAPI CATVFEEPROCESSING " . $currentMonth . " " . $currentYear);
    die('OK:CATVFEEPROCESSING');
}
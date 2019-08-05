<?php

//SORM Yahont csv data regeneration
if ($_GET['action'] == 'sormcast') {
    if ($alterconf['SORM_ENABLED']) {
        $sorm = new SormYahont();
        $sorm->saveAllDataCsv();
        die('OK:SORMCAST');
    } else {
        die('ERROR:SORM_DISABLED');
    }
}
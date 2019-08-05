<?php

//NAS monitoring periodic polling
if ($_GET['action'] == 'nasmon') {
    if ($alterconf['NASMON_ENABLED']) {
        $nasMon = new NasMon();
        $nasMon->saveCheckResults();
        die('OK: NASMON');
    } else {
        die('ERROR: NASMON DISABLED');
    }
}

        
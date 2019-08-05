<?php

//deal with it delayed tasks processing
if ($_GET['action'] == 'dealwithit') {
    if ($alterconf['DEALWITHIT_ENABLED']) {
        $dealWithIt = new DealWithIt();
        $dealWithIt->tasksProcessing();
        die('OK:DEALWITHIT');
    } else {
        die('ERROR:DEALWITHIT DISABLED');
    }
}

            
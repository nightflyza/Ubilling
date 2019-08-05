<?php

//why do you call stats collecting
if ($_GET['action'] == 'whydoyoucallstats') {
    if ($alterconf['ASKOZIA_ENABLED']) {
        $whydoyoucall = new WhyDoYouCall();
        $whydoyoucall->saveStats();
        die('OK: WDYCSTATS');
    } else {
        die('ERROR: ASKOIZA DISABLED');
    }
}


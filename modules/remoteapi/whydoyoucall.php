<?php

//why do you call?
if ($_GET['action'] == 'whydoyoucall') {
    if ($alterconf['ASKOZIA_ENABLED']) {
        $whydoyoucall = new WhyDoYouCall();
        $whydoyoucall->pollUnansweredCalls();
        die('OK: WDYC');
    } else {
        die('ERROR: ASKOIZA DISABLED');
    }
}

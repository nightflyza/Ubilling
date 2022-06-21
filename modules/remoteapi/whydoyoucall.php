<?php

//why do you call?
if (ubRouting::get('action') == 'whydoyoucall') {
    if ($alterconf['WDYC_ENABLED']) {
        $whydoyoucall = new WhyDoYouCall();
        $whydoyoucall->pollUnansweredCalls();
        die('OK: WDYC');
    } else {
        die('ERROR: WDYC DISABLED');
    }
}

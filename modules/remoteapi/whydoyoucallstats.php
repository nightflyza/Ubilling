<?php

//why do you call stats collecting
if (ubRouting::get('action') == 'whydoyoucallstats') {
    if ($alterconf['WDYC_ENABLED']) {
        $whydoyoucall = new WhyDoYouCall();
        $whydoyoucall->saveStats();
        die('OK: WDYCSTATS');
    } else {
        die('ERROR: WDYC DISABLED');
    }
}


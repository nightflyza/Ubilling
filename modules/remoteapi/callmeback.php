<?php

//parsing and storing FDB cache data into database
if (ubRouting::get('action') == 'callmeback') {
    if ($alterconf['CALLMEBACK_ENABLED']) {
        if (ubRouting::checkGet('param')) {
            $callmeback = new CallMeBack();
            $callmeback->createCall(ubRouting::get('param'), 'int');
            die('OK:CALLMEBACK');
        } else {
            die('ERROR:EMPTY_PARAM');
        }
    } else {
        die('ERROR:CALLMEBACK_DISABLED');
    }
}

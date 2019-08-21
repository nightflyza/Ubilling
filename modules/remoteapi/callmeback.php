<?php

//parsing and storing FDB cache data into database
if (ubRouting::get('action') == 'callmeback') {
    if (ubRouting::get('param')) {
        $callmeback = new CallMeBack();
        $callmeback->createCall(ubRouting::get('param'), 'int');
        die('OK:CALLMEBACK');
    } else {
        die('ERROR:EMPTY_PARAM');
    }
}
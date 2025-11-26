<?php

//telepony number telepathy
if (ubRouting::get('action') == 'telepony') {
    if ($alterconf['TELEPONY_ENABLED']) {
        if (ubRouting::checkGet('number')) {
            $number = ubRouting::get('number');
            $pbxNum = new PBXNum();
            $pbxNum->setNumber($number);
            $pbxNum->renderReply();
        } else {
            die('ERROR: EMPTY NUMBER');
        }
    } else {
        die('ERROR: TELEPONY DISABLED');
    }
}
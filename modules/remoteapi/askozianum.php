<?php

//askozia number telepathy
if (ubRouting::get('action') == 'askozianum') {
    if ($alterconf['ASKOZIA_ENABLED']) {
        if (ubRouting::checkGet('param')) {
            $number = ubRouting::get('param');
            $askNum = new PBXNum();
            $askNum->setNumber($number);
            $askNum->renderReply();
        } else {
            die('ERROR: EMPTY PARAM');
        }
    } else {
        die('ERROR: ASKOIZA DISABLED');
    }
}

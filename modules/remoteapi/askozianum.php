<?php

//askozia number telepathy
if ($_GET['action'] == 'askozianum') {
    if ($alterconf['ASKOZIA_ENABLED']) {
        if (isset($_GET['param'])) {
            $number = $_GET['param'];
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

          
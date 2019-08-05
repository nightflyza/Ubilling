<?php

/*
 * send sms queue to remind users about payments
 */
if ($_GET['action'] == 'reminder') {
    if ($alterconf['REMINDER_ENABLED']) {
        if ($alterconf['SENDDOG_ENABLED']) {
            $sms = new Reminder();
            if (wf_CheckGet(array('param'))) {
                if ($_GET['param'] == 'force') {
                    $sms->forceRemind();
                } else {
                    die('ERROR:WRONG PARAM');
                }
            } else {
                $sms->RemindUser();
            }


            die('OK:SEND REMIND SMS');
        } else {
            die('ERROR:SENDDOG_REQUIRED');
        }
    } else {
        die('ERROR:REMINDER DISABLED');
    }
}

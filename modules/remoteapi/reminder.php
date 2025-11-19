<?php

/*
 * send sms queue to remind users about payments
 */

if (ubRouting::get('action') == 'reminder') {
    global $ubillingConfig;

    if ($ubillingConfig->getAlterParam('REMINDER_ENABLED')) {
        if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
            $sms = new Reminder();
            // why?!
            //log_register('');   // just a visual separation of processing in weblogs

            if (wf_CheckGet(array('param'))) {
                if ($_GET['param'] == 'force') {
                    log_register('REMINDER: FORCED processing STARTED....');
                    $sms->forceRemind();
                    log_register('REMINDER: FORCED processing FINISHED');
                } else {
                    die('ERROR:WRONG PARAM');
                }
            } else {
                log_register('REMINDER: REGULAR processing STARTED....');
                $sms->remindUsers();
                log_register('REMINDER: REGULAR processing FINISHED');
            }

          // why?!
          //  log_register('');   // just a visual separation of processing in weblogs
            die('OK:SEND REMIND SMS');
        } else {
            die('ERROR:SENDDOG_REQUIRED');
        }
    } else {
        die('ERROR:REMINDER DISABLED');
    }
}

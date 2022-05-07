<?php

if (ubRouting::get('action') == 'opazysmsnotify') {
    global $ubillingConfig;

    if ($ubillingConfig->getAlterParam('OP_SMS_NOTIFY_ENABLED')) {
        if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
            $openpayz = new OpenPayz();
            $openpayz->pullNotysPayments();
            $openpayz->processNotys();

            die('OPAZYSMSNOTIFY: FINISHED PROCESSING');
        } else {
            die('OPAZYSMSNOTIFY ERROR: SENDDOG IS DISABLED');
        }
    } else {
        die('OPAZYSMSNOTIFY ERROR: OPENPAZY SMS NOTIFICATION IS DISABLED');
    }
}
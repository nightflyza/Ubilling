<?php

if (ubRouting::get('action') == 'opazysmsnotify') {
    global $ubillingConfig;

    if ($ubillingConfig->getAlterParam('OP_SMS_NOTIFY_ENABLED')) {
        if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
            $OpenPayz = new OpenPayz();
            $OpenPayz->pullNotysPayments();
            $OpenPayz->processNotys();
        }
    }
}
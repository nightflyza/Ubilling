<?php

if (ubRouting::get('action') == 'olltvchargefee') {
    if ($ubillingConfig->getAlterParam('OLLTV_ENABLED')) {
        $ollTv = new OllTVService();
        $ollTv->feeProcessing();
    } else {
        die('ERROR:OLLTV_DISABLED');
    }
}
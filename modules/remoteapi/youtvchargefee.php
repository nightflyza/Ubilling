<?php

if (ubRouting::get('action') == 'youtvchargefee') {
    if ($ubillingConfig->getAlterParam('YOUTV_ENABLED')) {
        $ytv = new YTV();
        $ytv->feeProcessing();
    } else {
        die('ERROR:YOUTV_DISABLED');
    }
}
<?php

if (ubRouting::get('action') == 'ptvchargefee') {
    if ($ubillingConfig->getAlterParam('PTV_ENABLED')) {
        $ptv = new PTV();
        $ptv->feeProcessing();
    } else {
        die('ERROR:PTV_DISABLED');
    }
}
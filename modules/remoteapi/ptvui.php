<?php

if (ubRouting::get('action') == 'ptvui') {
    if ($ubillingConfig->getAlterParam('PTV_ENABLED')) {
        $ptv = new PTV();
        if (ubRouting::checkGet('subdata')) {
            $ptv->usReplyUserData(ubRouting::get('subdata'));
        }

        if (ubRouting::checkGet('tardata')) {
            $ptv->usReplyTariffs();
        }
    } else {
        $replyError = array('error' => 'ERROR: PTV_DISABLED');
        die(json_encode($replyError));
    }
} 
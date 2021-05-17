<?php

if (ubRouting::get('action') == 'youtvui') {
    if ($ubillingConfig->getAlterParam('YOUTV_ENABLED')) {

        $ptv = new YTV();
        if (ubRouting::checkGet('subdata')) {
            $ptv->usReplyUserData(ubRouting::get('subdata'));
        }

        if (ubRouting::checkGet('tardata')) {
            $ptv->usReplyTariffs();
        }

        if (ubRouting::checkGet('fulldata')) {
            $ptv->usReplyUserFullData(ubRouting::get('fulldata'));
        }

        if (ubRouting::checkGet(array('unsub', 'subid'))) {
            $ptv->usUnsubscribe(ubRouting::get('subid', 'int'), ubRouting::get('unsub', 'int'));
        }

        if (ubRouting::checkGet(array('subserv', 'sublogin'))) {
            $ptv->usSubscribe(ubRouting::get('sublogin'), ubRouting::get('subserv', 'int'));
        }
    } else {
        $replyError = array('error' => 'ERROR: YOUTV_ENABLED');
        die(json_encode($replyError));
    }
} 
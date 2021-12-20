<?php

if (ubRouting::get('action') == 'olltvui') {
    if ($ubillingConfig->getAlterParam('OLLTV_ENABLED')) {
        $ollTv = new OllTVService();
        if (ubRouting::checkGet('subdata')) {
            $ollTv->usReplyUserData(ubRouting::get('subdata'));
        }

        if (ubRouting::checkGet('tardata')) {
            $ollTv->usReplyTariffs();
        }

        if (ubRouting::checkGet('devdata')) {
            $ollTv->usReplyDevices(ubRouting::get('devdata'));
        }

        if (ubRouting::checkGet(array('unsub', 'sublogin'))) {
            $ollTv->usUnsubscribe(ubRouting::get('sublogin'), ubRouting::get('unsub', 'int'));
        }

        if (ubRouting::checkGet(array('subserv', 'sublogin'))) {
            $ollTv->usSubscribe(ubRouting::get('sublogin'), ubRouting::get('subserv', 'int'));
        }
    } else {
        $replyError = array('error' => 'ERROR: OLLTV_DISABLED');
        die(json_encode($replyError));
    }
}
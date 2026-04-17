<?php

if (ubRouting::get('action') == 'youtvui') {
    if ($ubillingConfig->getAlterParam('YOUTV_ENABLED')) {

        $youtv = new YTV();

        if (ubRouting::checkGet('getcredentials')) {
            $youtv->getCredentials(ubRouting::get('getcredentials'));
        }

        if (ubRouting::checkGet('subdata')) {
            $youtv->usReplyUserData(ubRouting::get('subdata'));
        }

        if (ubRouting::checkGet('tardata')) {
            $youtv->usReplyTariffs();
        }

        if (ubRouting::checkGet('fulldata')) {
            $youtv->usReplyUserFullData(ubRouting::get('fulldata'));
        }

        if (ubRouting::checkGet(array('unsub', 'subid'),false)) {
            $youtv->usUnsubscribe(ubRouting::get('subid', 'int'), ubRouting::get('unsub', 'int'));
        }

        if (ubRouting::checkGet(array('subserv', 'sublogin'),false)) {
            $youtv->usSubscribe(ubRouting::get('sublogin'), ubRouting::get('subserv', 'int'));
        }
    } else {
        $replyError = array('error' => 'ERROR: YOUTV_DISABLED');
        die(json_encode($replyError));
    }
} 
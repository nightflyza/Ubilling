<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['CALLMEBACK_ENABLED']) {
    if (cfr('CALLMEBACK')) {
        $callMeBack = new CallMeBack();

        //change existing call state
        if (ubRouting::checkGet(array('setcall', 'state'))) {
            $callMeBack->setCallState(ubRouting::get('setcall'), ubRouting::get('state'));
            ubRouting::nav($callMeBack::URL_ME);
        }

        //rendering processed calls json data
        if (ubRouting::get('ajaxdonecalls')) {
            $callMeBack->getAjProcessedList();
        }

        //render some interface
        show_window('', $callMeBack->renderPanel());
        if (ubRouting::checkGet('showdone')) {
            show_window(__('Processed calls'), $callMeBack->renderProcessedCalls());
        } else {
            show_window(__('Calls'), $callMeBack->renderUndoneCalls());
        }
        zb_BillingStats(true);
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
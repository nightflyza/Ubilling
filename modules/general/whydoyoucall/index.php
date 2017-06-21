<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['ASKOZIA_ENABLED']) {
    if (cfr('WHYDOYOUCALL')) {

        $whydoyoucall = new WhyDoYouCall();
        show_window(__('Missed calls that require your response'), $whydoyoucall->renderMissedCallsReport());
        
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
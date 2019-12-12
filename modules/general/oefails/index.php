<?php

if (cfr('OEFAILS')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['OEFAILS_ENABLED']) {
        $oeFails = new OeFails();
        if (ubRouting::get('ajaxlist')) {
            $oeFails->ajGetData(ubRouting::get('datefilter'), ubRouting::get('alltime'));
        }
        show_window(__('Power outages'), $oeFails->renderList());
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
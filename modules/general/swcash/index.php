<?php

$altCfg = $ubillingConfig->getAlter();
if (@$altCfg['SW_CASH_ENABLED']) {
    if (cfr('SWITCHESEDIT')) {
        $switchCash = new SwitchCash();
        
        //rendering edit form
        if (ubRouting::checkGet($switchCash::ROUTE_EDIT)) {
            $switchId = ubRouting::get($switchCash::ROUTE_EDIT, 'int');
            show_window(__('Edit').' '.__('Financial data'), $switchCash->renderEditForm($switchId));
        }
    }
} else {
    show_error(__('This module is disabled'));
}
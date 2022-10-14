<?php

if (cfr('ROOT')) {
    $errorLogFlag = ubRouting::checkGet(ApacheZen::ROUTE_ERRORS) ? true : false;
    if (!ubRouting::checkGet(ApacheZen::ROUTE_PHPERR)) {
        $apacheZen = new ApacheZen($errorLogFlag);
        $zenFlow = new ZenFlow($apacheZen->getFlowId(), $apacheZen->render(), $apacheZen->getTimeout());
        show_window('', $apacheZen->controls());
        show_window(__('Apache') . ' ' . __('Zen').' '.$apacheZen->getCurrentSource(), $zenFlow->render());
        zb_BillingStats(true);
    } else {
        $apacheZen = new ApacheZen(true);
        show_window('', $apacheZen->controls());
        show_window(__('PHP errors'), $apacheZen->renderPHPErrors());
    }
} else {
    show_error(__('Access denied'));
}
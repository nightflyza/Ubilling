<?php

if (cfr('ROOT')) {
    $apacheZen = new ApacheZen();
    $zenFlow = new ZenFlow($apacheZen->getFlowId(), $apacheZen->render(), $apacheZen->getTimeout());
    show_window(__('Apache') . ' ' . __('Zen'), $zenFlow->render());
    show_window('', wf_BackLink('?module=report_sysload'));
    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
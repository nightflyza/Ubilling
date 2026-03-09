<?php

if (cfr('METABOLISM')) {
    $metabolism = new Metabolism();
    show_window('', $metabolism->renderPanel());

    if (ubRouting::checkGet(Metabolism::ROUTE_RENDER)) {
        switch (ubRouting::get(Metabolism::ROUTE_RENDER)) {
            case Metabolism::R_PAYMENTS:
                if (cfr('REPORTFINANCE')) {
                 show_window(__('Payments'), $metabolism->renderPayments());
                }
                break;
            case Metabolism::R_SIGNUPS:
                if (cfr('REPORTSIGNUP')) {
                    show_window(__('Signups'), $metabolism->renderSignups());
                }
                break;
            case Metabolism::R_LIFECYCLE:
                show_window(__('Lifecycle'), $metabolism->renderLifecycle());
                break;
            default:
                show_error(__('Strange exception'));
                break;
        }
    } else {
        show_window(__('Lifecycle'), $metabolism->renderLifecycle());
    }

    

    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
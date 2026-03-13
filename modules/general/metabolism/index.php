<?php

if (cfr('METABOLISM')) {
    set_time_limit(0);
    $metabolism = new Metabolism();
    show_window('', $metabolism->renderPanel());

    if (ubRouting::checkGet(Metabolism::ROUTE_RENDER)) {
        switch (ubRouting::get(Metabolism::ROUTE_RENDER)) {
            case Metabolism::R_PAYMENTS:
                if (cfr('REPORTFINANCE')) {
                 show_window(__('Payments'), $metabolism->renderPayments());
                } else {
                    log_register('METABOLISM PAYMENTS ACCESS VIOLATION');
                }
                break;
            case Metabolism::R_SIGNUPS:
                if (cfr('REPORTSIGNUP')) {
                    show_window(__('Signups'), $metabolism->renderSignups());
                } else {
                    log_register('METABOLISM SIGNUPS ACCESS VIOLATION');
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
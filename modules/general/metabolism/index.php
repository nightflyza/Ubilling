<?php

if (cfr('REPORTFINANCE') AND cfr('REPORTSIGNUP')) {
    $metabolism = new Metabolism();
    show_window('', $metabolism->renderPanel());
    if (ubRouting::checkGet('signups')) {
        show_window(__('Signups'), $metabolism->renderSignups());
    } else {
        show_window(__('Payments'), $metabolism->renderPayments());
    }
} else {
    show_error(__('Access denied'));
}
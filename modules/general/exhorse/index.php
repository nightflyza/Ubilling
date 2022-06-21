<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['EXHORSE_ENABLED']) {
    if (cfr('EXHORSE')) {

        $exhorse = new ExistentialHorse();
        if (ubRouting::checkPost('yearsel')) {
            $exhorse->setYear(ubRouting::post('yearsel'));
        } else {
            $exhorse->setYear(date("Y"));
        }

        show_window(__('Existential horse'), $exhorse->renderReport());
        zb_BillingStats(true);
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}

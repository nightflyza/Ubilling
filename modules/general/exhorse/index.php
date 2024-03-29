<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['EXHORSE_ENABLED']) {
    if (cfr('EXHORSE')) {

        $exhorse = new ExistentialHorse();
        if (ubRouting::checkPost($exhorse::PROUTE_YEAR)) {
            $exhorse->setYear(ubRouting::post($exhorse::PROUTE_YEAR));
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

<?php

if (cfr('CASH')) {

    if ($ubillingConfig->getAlterParam('KARMA_CONTROL')) {
        set_time_limit(0); // karma repair may take a long time
        $badKarma = new BadKarma();
        //trying to fix user
        if (ubRouting::checkGet($badKarma::ROUTE_FIX)) {
            $repairLogin = ubRouting::get($badKarma::ROUTE_FIX);
            $repairResult = $badKarma->fixUserKarma($repairLogin);
            if (!empty($repairResult)) {
                show_error($repairResult);
            } else {
                ubRouting::nav($badKarma::URL_ME);
            }
        }

        //mass reset action
        if (ubRouting::checkGet($badKarma::ROUTE_MASSRESET)) {
            $badKarma->runMassReset();
            ubRouting::nav($badKarma::URL_ME);
        }
        show_window(__('Users with bad karma'), $badKarma->renderReport());
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
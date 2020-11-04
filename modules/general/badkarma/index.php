<?php

if (cfr('CASH')) {

    $badKarma = new BadKarma();
    //trying to fix user
    if (ubRouting::checkGet($badKarma::ROUTE_FIX)) {
        $repairLogin = ubRouting::get($badKarma::ROUTE_FIX);
        $repairResult = $badKarma->fixUserCarma($repairLogin);
        if (!empty($repairResult)) {
            show_error($repairResult);
        } else {
            ubRouting::nav($badKarma::URL_ME);
        }
    }
    show_window(__('Users with bad karma'), $badKarma->renderReport());
} else {
    show_error(__('Access denied'));
}
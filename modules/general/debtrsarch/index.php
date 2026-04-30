<?php

if (cfr('DEBTRSARCH')) {
    if ($ubillingConfig->getAlterParam('DEBTRSARCH_ENABLED')) {
    $debtrsArch = new DebtrsArch();
    show_window('', $debtrsArch->renderControls());

    if (ubRouting::checkGet($debtrsArch::ROUTE_ARCH)) {
        show_window(__('Debtors archive'), $debtrsArch->renderArchive());
    }

    if (ubRouting::checkGet($debtrsArch::ROUTE_TIMEPOINT)) {
        show_window(__('Debtors archive'), $debtrsArch->renderTimePoint(ubRouting::get($debtrsArch::ROUTE_TIMEPOINT,'int')));
    }

    if (ubRouting::checkGet($debtrsArch::ROUTE_DIFF)) {
        show_window('', $debtrsArch->renderDiffForm());
        if (ubRouting::checkPost(array($debtrsArch::PROUTE_DIFF_ONE, $debtrsArch::PROUTE_DIFF_TWO))) {
            $noFrozen = (ubRouting::checkPost($debtrsArch::PROUTE_NOFROZEN)) ? true : false;
            $idOne = ubRouting::post($debtrsArch::PROUTE_DIFF_ONE, 'int');
            $idTwo = ubRouting::post($debtrsArch::PROUTE_DIFF_TWO, 'int');
            show_window(__('Comparison results'), $debtrsArch->compareTimePoints($idOne, $idTwo, $noFrozen));
        }
    }
    
} else {
    show_error(__('This module is disabled'));
}


} else {
    show_error(__('Access denied'));
}


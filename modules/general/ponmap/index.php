<?php

$altCfg = $ubillingConfig->getAlter();

if (@$altCfg['PONMAP_ENABLED']) {
    if ($altCfg['PON_ENABLED']) {
        if (cfr('PON')) {
            $oltIdFilter = (ubRouting::checkGet(PONONUMAP::ROUTE_FILTER_OLT)) ? ubRouting::get(PONONUMAP::ROUTE_FILTER_OLT) : '';
            if (ubRouting::checkPost(PONONUMAP::PROUTE_OLTSELECTOR, false)) {
                $navOltId = ubRouting::post(PONONUMAP::PROUTE_OLTSELECTOR);
                if (!empty($navOltId)) {
                    ubRouting::nav(PONONUMAP::URL_ME . '&' . PONONUMAP::ROUTE_FILTER_OLT . '=' . $navOltId);
                } else {
                    ubRouting::nav(PONONUMAP::URL_ME);
                }
            }
            $ponMap = new PONONUMAP($oltIdFilter);
            show_window(__('ONU Map') . $ponMap->getFilteredOLTLabel(), $ponMap->renderOnuMap());
        } else {
            show_error(__('Access denied'));
        }
    } else {
        show_error(__('This module is disabled') . '. ' . __('You missed an important option') . ': PON_ENABLED');
    }
} else {
    show_error(__('This module is disabled'));
}
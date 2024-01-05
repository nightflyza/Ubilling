<?php

$altCfg = $ubillingConfig->getAlter();

if (@$altCfg['PONMAP_ENABLED']) {
    if ($altCfg['PON_ENABLED']) {
        if (cfr('ONUMAP')) {
            $oltIdFilter = (ubRouting::checkGet(PONONUMap::ROUTE_FILTER_OLT)) ? ubRouting::get(PONONUMap::ROUTE_FILTER_OLT) : '';
            if (ubRouting::checkPost(PONONUMap::PROUTE_OLTSELECTOR, false)) {
                $navOltId = ubRouting::post(PONONUMap::PROUTE_OLTSELECTOR);
                if (!empty($navOltId)) {
                    ubRouting::nav(PONONUMap::URL_ME . '&' . PONONUMap::ROUTE_FILTER_OLT . '=' . $navOltId);
                } else {
                    ubRouting::nav(PONONUMap::URL_ME);
                }
            }
            
            $ponMap = new PONONUMap($oltIdFilter);
            show_window(__('ONU Map') . $ponMap->getFilteredOLTLabel(), $ponMap->renderOnuMap());
            zb_BillingStats(true);
        } else {
            show_error(__('Access denied'));
        }
    } else {
        show_error(__('This module is disabled') . '. ' . __('You missed an important option') . ': PON_ENABLED');
    }
} else {
    show_error(__('This module is disabled'));
}
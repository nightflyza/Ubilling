<?php

if (cfr('MACVEN')) {
    $altercfg = $ubillingConfig->getAlter();
    if ($altercfg['MACVEN_ENABLED']) {
        if (ubRouting::checkGet('mac')) {
            $mac = ubRouting::get('mac');
            $searchmac = new SearchMAC();
            $vendor = $searchmac->getVendor($mac);

            if (!ubRouting::checkGet('raw')) {
                $vendor = wf_tag('h3') . wf_tag('center') . $vendor . wf_tag('center', true) . wf_tag('h3', true);
                if (ubRouting::checkGet('modalpopup')) {
                    $vendor = wf_modalOpened(__('Device vendor'), $vendor, '400', '200');
                }
            }
            print($vendor);
        }
    } else {
        print(__('This module is disabled'));
    }
} else {
    print(__('Access denied'));
}

die();

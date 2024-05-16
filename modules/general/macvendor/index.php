<?php

if (cfr('MACVEN')) {
    $altercfg = $ubillingConfig->getAlter();
    if ($altercfg['MACVEN_ENABLED']) {
        if (ubRouting::checkGet('mac')) {
            $mac = ubRouting::get('mac');
            if (@$altercfg['MACVEN_CACHE']) {
                $cache = new UbillingCache();
                $cacheTime = 2592000; //something about month
                $vendorCache = $cache->get('MACVENDB', $cacheTime);
                if (!empty($vendorCache) and is_array($vendorCache)) {
                    if (isset($vendorCache[$mac])) {
                        //from cache
                        $vendor = $vendorCache[$mac];
                    } else {
                        //cache update
                        $vendor = zb_MacVendorLookup($mac);
                        $vendorCache[$mac] = $vendor;
                        $cache->set('MACVENDB', $vendorCache, $cacheTime);
                    }
                } else {
                    //empty cache
                    $vendorCache = array();
                    $vendor = zb_MacVendorLookup($mac);
                    $vendorCache[$mac] = $vendor;
                    $cache->set('MACVENDB', $vendorCache, $cacheTime);
                }
            } else {
                $vendor = zb_MacVendorLookup($mac);
            }

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

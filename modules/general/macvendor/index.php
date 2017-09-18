<?php

if (cfr('MAC')) {
    $altercfg = $ubillingConfig->getAlter();

    if ($altercfg['MACVEN_ENABLED']) {
        if (wf_CheckGet(array('mac'))) {
            $mac = $_GET['mac'];
            if (@$altercfg['MACVEN_CACHE']) {
                $cache = new UbillingCache();
                $cacheTime = 2592000; //something about month
                $vendorCache = $cache->get('MACVENDB', $cacheTime);
                debarr($vendorCache);
                if (!empty($vendorCache)) {
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
                    $vendor = zb_MacVendorLookup($mac);
                    $vendorCache[$mac] = $vendor;
                    $cache->set('MACVENDB', $vendorCache, $cacheTime);
                }
            } else {
                $vendor = zb_MacVendorLookup($mac);
            }

            if (!wf_CheckGet(array('raw'))) {
                $vendor = wf_tag('h3') . wf_tag('center') . $vendor . wf_tag('center', true) . wf_tag('h3', true);
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
?>
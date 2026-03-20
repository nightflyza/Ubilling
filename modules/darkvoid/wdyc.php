<?php

$result = '';

if (@$darkVoidContext['altCfg']['WDYC_ENABLED']) {
    $wdycCache = 'exports/whydoyoucall.dat';
    if (file_exists($wdycCache)) {
        $cacheData = file_get_contents($wdycCache);
        if (!empty($wdycCache)) {
            $cacheData = unserialize($cacheData);
            $missedCallsCount = sizeof($cacheData);
            if ($missedCallsCount > 0) {
                $missedCallsAlert = $missedCallsCount . ' ' . __('users tried to contact you but could not');
                $result .= wf_Link('?module=whydoyoucall', wf_img("skins/wdycnotify.png", $missedCallsAlert), false, '');
            }
        }
    }
}

return ($result);

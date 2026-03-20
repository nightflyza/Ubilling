<?php

$result = '';

if ($darkVoidContext['altCfg']['POLICEDOG_ENABLED']) {
    $policeDogQuery = "SELECT COUNT(`id`) from `policedogalerts`";
    $policeDogCount = simple_query($policeDogQuery);
    $policeDogCount = $policeDogCount['COUNT(`id`)'];
    if ($policeDogCount > 0) {
        $result .= wf_Link('?module=policedog&show=fastscan', wf_img('skins/policedogalert.png', $policeDogCount . ' ' . __('Wanted MAC detected')), false, '');
    }
}

return ($result);

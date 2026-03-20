<?php

$result = '';

if ($darkVoidContext['altCfg']['SIGREQ_ENABLED']) {
    $signups = new SignupRequests();
    $newreqcount = $signups->getAllNewCount();
    if ($newreqcount != 0) {
        $result .= wf_Link('?module=sigreq', wf_img('skins/sigreqnotify.gif', $newreqcount . ' ' . __('signup requests expected processing')), false);
    }
}

return ($result);

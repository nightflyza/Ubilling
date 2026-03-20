<?php

$result = '';

if (@$darkVoidContext['altCfg']['CALLMEBACK_ENABLED']) {
    $callMeBack = new CallMeBack();
    $undoneCallsCount = $callMeBack->getUndoneCount();
    if ($undoneCallsCount > 0) {
        $callmeBackAlert = $undoneCallsCount . ' ' . __('Users are waiting for your call');
        $result .= wf_Link('?module=callmeback', wf_img("skins/cmbnotify.png", $callmeBackAlert), false, '');
    }
}

return ($result);

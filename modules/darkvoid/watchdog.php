<?php

$result = '';

if (isset($darkVoidContext['altCfg']['WATCHDOG_ENABLED'])) {
    if ($darkVoidContext['altCfg']['WATCHDOG_ENABLED']) {
        $watchDogMaintenance = zb_StorageGet('WATCHDOG_MAINTENANCE');
        $watchDogSmsSilence = zb_StorageGet('WATCHDOG_SMSSILENCE');
        if ($watchDogMaintenance) {
            $result .= wf_Link('?module=watchdog', wf_img('skins/maintenance.png', __('Watchdog') . ': ' . __('Disabled')));
        }

        if (($watchDogSmsSilence) AND (@!$darkVoidContext['altCfg']['WATCHDOG_SILENCE_NOTIFY_DISABLED'])) {
            $result .= wf_Link('?module=watchdog', wf_img('skins/smssilence.png', __('Watchdog') . ': ' . __('SMS silence')));
        }
    }
}

return ($result);

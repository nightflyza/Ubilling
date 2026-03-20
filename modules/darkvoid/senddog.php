<?php

$result = '';

if ($darkVoidContext['altCfg']['SENDDOG_ENABLED']) {
    $smsQueueCount = rcms_scandir(DATA_PATH . 'tsms/');
    $smsQueueCount = sizeof($smsQueueCount);
    if ($smsQueueCount > 0) {
        $result .= wf_Link('?module=tsmsqueue', wf_img('skins/sms.png', $smsQueueCount . ' ' . __('SMS in queue')), false, '');
    }

    if ($darkVoidContext['altCfg']['SENDDOG_PARALLEL_MODE']) {
        $sendDogPid = SendDog::PID_PATH;
        if (file_exists($sendDogPid)) {
            $result .= wf_Link('?module=tsmsqueue', wf_img('skins/dog_stand.png', __('SendDog is working')), false, '');
        }
    }
}

return ($result);

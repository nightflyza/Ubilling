<?php

if (cfr('SENDDOG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['SENDDOG_ENABLED']) {
        $sendDog = new SendDog();

        //editing config
        if (wf_CheckPost(array('editconfig'))) {
            $sendDog->saveConfig();
            rcms_redirect($sendDog->getBaseUrl());
        }

        if (!wf_CheckGet(array('showsmsqueue'))) {
            //render config interface
            show_window(__('SendDog configuration'), $sendDog->renderConfigForm());
        } else {
            //render SMS queue
            $smsQueue = $_GET['showsmsqueue'];
            switch ($smsQueue) {
                case 'tsms':
                    show_window(__('View SMS sending queue'), $sendDog->renderTurboSMSQueue());
                    break;
                case 'smsflybalance':
                    show_window(__('SMS-Fly').' '.__('Balance'), $sendDog->renderSmsflyBalance());
                    break;
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>
<?php

if (cfr('OMEGATV')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['OMEGATV_ENABLED']) {
        $omega=new OmegaTV();
        show_window(__('OmegaTV'), $omega->renderPanel());
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Acccess denied'));
}
?>
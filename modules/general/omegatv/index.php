<?php

if (cfr('OMEGATV')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['OMEGATV_ENABLED']) {
        $omega = new OmegaTV();
        show_window(__('OmegaTV'), $omega->renderPanel());

        //tariffs management
        if (wf_CheckGet(array('tariffs'))) {
            //creating new tariff
            if (wf_CheckPost(array('newtariffid'))) {
                $omega->createTariff();
                rcms_redirect($omega::URL_ME . '&tariffs=true');
            }

            //deleting existing tariff
            if (wf_CheckGet(array('deleteid'))) {
                $omega->deleteTariff($_GET['deleteid']);
                rcms_redirect($omega::URL_ME . '&tariffs=true');
            }

            //listing available tariffs
            show_window(__('Tariffs'), $omega->renderTariffsList());

            //tariffs creation form
            show_window(__('Create new tariff'), $omega->renderTariffCreateForm());
        }
        
        if (wf_CheckGet(array('subscriptions'))) {
            deb($omega->renderUserInfo(1));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Acccess denied'));
}
?>
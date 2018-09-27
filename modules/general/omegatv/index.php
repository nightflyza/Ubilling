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
            //getting new device activation code
            if (wf_CheckGet(array('getdevicecode'))) {
                die($omega->generateDeviceCode($_GET['getdevicecode']));
            }
            //deleting existing device
            if (wf_CheckGet(array('deletedevice', 'customerid'))) {
                $omega->deleteDevice($_GET['customerid'], $_GET['deletedevice']);
            }

            //json ajax data for subscribers list
            if (wf_CheckGet(array('ajuserlist'))) {
                $omega->ajUserList();
            }

            //rendering user list container
            show_window(__('Subscriptions'), $omega->renderUserListContainer());
        }

        if (wf_CheckGet(array('customerprofile'))) {
            show_window(__('Profile'), $omega->renderUserInfo($_GET['customerprofile']));
            show_window('', wf_BackLink($omega::URL_ME . '&subscriptions=true'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Acccess denied'));
}
?>
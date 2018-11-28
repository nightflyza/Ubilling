<?php

if (cfr('TRINITYTV')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['TRINITYTV_ENABLED']) {

        $interface = new TrinityTv();

        //primary control panelas
        show_window(__('TrinityTV'), $interface->renderPanel());

        if (wf_CheckGet(array('processing'))) {
            $mgFeeProcessingResult = $interface->subscriptionFeeProcessing();
            die($mgFeeProcessingResult);
        }

        //tariffs management
        if (wf_CheckGet(array('tariffs'))) {

            //tariff creation
            if (wf_CheckPost(array('newtariffname'))) {
                $tariffCreateResult = $interface->createTariff();
                if (!$tariffCreateResult) {
                    rcms_redirect($interface::URL_ME . '&' . $interface::URL_TARIFFS);
                } else {
                    show_window(__('Something went wrong'), $tariffCreateResult);
                }
            }

            //tariff editing
            if (wf_CheckPost(array(
                'edittariffid',
                'edittariffname'
            ))) {
                $tariffSaveResult = $interface->updateTariff();
                if (!$tariffSaveResult) {
                    rcms_redirect($interface::URL_ME . '&' . $interface::URL_TARIFFS);
                } else {
                    show_window(__('Something went wrong'), $tariffSaveResult);
                }
            }

            //tariff deletion
            if (wf_CheckGet(array('deletetariffid'))) {
                $errorMsg = $interface->deleteTariff($_GET['deletetariffid']);
                if (empty($errorMsg)) {
                    rcms_redirect($interface::URL_ME . '&' . $interface::URL_TARIFFS);
                } else {
                    show_window(__('Something went wrong'), $errorMsg);
                }
            }

            show_window(__('Available tariffs'), $interface->renderTariffs());
        }

        if (wf_CheckGet(array('subscriptions'))) {

            if (wf_CheckGet(array('username'))) {
                $subscriberID = $interface->getSubscriberId($_GET['username']);
                if (!empty($subscriberID)) {
                    rcms_redirect($interface::URL_SUBSCRIBER . $subscriberID);
                } else {
                    show_warning(__('This user have not existing TrinityTV subscription profile. You can register it using appropriate button on upper panel.'));
                    show_window('', web_UserControls($_GET['username']));
                }
            }


            //jqdt data renderer
            if (wf_CheckGet(array('ajsubs'))) {
                $interface->subscribtionsListAjax();
            }

            //rendering user list container
            show_window(__('Subscriptions'), $interface->renderSubscribtions());
        }

        if (wf_CheckGet(array('subscriberid'))) {
            //user blocking
            if (wf_CheckGet(array('blockuser'))) {
                $interface->setSubscriberActive($_GET['subscriberid'], false);
                rcms_redirect($interface::URL_SUBSCRIBER . $_GET['subscriberid']);
            }

            //user unblocking
            if (wf_CheckGet(array('unblockuser'))) {
                $interface->setSubscriberActive($_GET['subscriberid'], true);
                rcms_redirect($interface::URL_SUBSCRIBER . $_GET['subscriberid']);
            }

            //user tariff editing
            if (wf_CheckPost(array('changebasetariff'))) {

                $interface->changeTariffs($_GET['subscriberid'], $_POST['changebasetariff']);
                rcms_redirect($interface::URL_SUBSCRIBER . $_GET['subscriberid']);
            }

            //user device assign
            if (wf_CheckPost(array(
                'manualassigndevice',
                'subscriberid',
                'mac'
            ))) {
                $assignResult = $interface->addDevice($_POST['subscriberid'], $_POST['mac']);
                if (empty($assignResult)) {
                    rcms_redirect($interface::URL_SUBSCRIBER . $_GET['subscriberid']);
                } else {
                    show_error($assignResult);
                }
            }

            // user device assign bu code
            if (wf_CheckPost(array(
                'manualassigndevice',
                'subscriberid',
                'code'
            ))) {
                $assignResult = $interface->addDeviceByCode($_POST['subscriberid'], $_POST['code']);
                if (empty($assignResult)) {
                    rcms_redirect($interface::URL_SUBSCRIBER . $_GET['subscriberid']);
                } else {
                    show_error($assignResult);
                }
            }

            //deleting existing device for some user
            if (wf_CheckGet(array(
                'deletedeviceid',
                'subscriberid'
            ))) {

                $errorMsg = $interface->deleteDevice($_GET['deletedeviceid']);
                if (empty($errorMsg)) {
                    rcms_redirect($interface::URL_SUBSCRIBER . $_GET['subscriberid']);
                } else {
                    show_error($errorMsg);
                }
            }

            show_window(__('Profile'), $interface->renderUserInfo($_GET['subscriberid']));

            show_window(__('Devices'), $interface->renderDevices($_GET['subscriberid']));
            show_window('', wf_BackLink($interface::URL_ME . '&subscriptions=true'));
        }

        //new user manual registration
        if (wf_CheckPost(array(
            'manualregister',
            'manualregisterlogin',
            'manualregistertariff'
        ))) {

            $manualRegResult = $interface->createSubscribtion($_POST['manualregisterlogin'], $_POST['manualregistertariff']);

            // Если создался без ошибок редиректим в профиль
            if (empty($manualRegResult)) {

                // Получим профиль подписчика
                $subscriberID = $interface->getSubscriberId($_POST['manualregisterlogin']);

                if (!empty($subscriberID)) {
                    rcms_redirect($interface::URL_SUBSCRIBER . $subscriberID);
                } else {
                    rcms_redirect($interface::URL_ME . '&subscriptions=true');
                }
            } else {
                show_error($manualRegResult);
            }
        }

        //black magic profile redirect or new subscriber registration
        if (wf_CheckGet(array('username'))) {
            $subscriberID = $interface->getSubscriberId($_GET['username']);
            if (!empty($subscriberID)) {
                rcms_redirect($interface::URL_SUBSCRIBER . $subscriberID);
            } else {
                show_warning(__('This user have not existing TrinityTV subscription profile. You can register it using appropriate button on upper panel.'));
                show_window('', web_UserControls($_GET['username']));
            }
        }


        //subscriptions report
        if (wf_CheckGet(array('reports'))) {
            //montly accounting report
            show_window(__('Subscriptions report'), $interface->renderSubscribtionsReportMonthly());
        }

    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Acccess denied'));
}
?>
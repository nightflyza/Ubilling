<?php

if (cfr('YOUTV')) {
    if ($ubillingConfig->getAlterParam('YOUTV_ENABLED')) {
        $youtv = new YTV();


        //new subscriber register
        if (ubRouting::checkPost($youtv::PROUTE_SUBREG)) {
            $userLogin = ubRouting::post($youtv::PROUTE_SUBREG, 'mres');
            $regResult = $youtv->userRegister($userLogin);
            if (!$regResult) {
                ubRouting::nav($youtv::URL_ME . '&' . $youtv::ROUTE_SUBVIEW . '=' . $userLogin);
            } else {
                show_error($regResult);
            }
        }

        //rendering available subscribers list data
        if (ubRouting::checkGet($youtv::ROUTE_SUBAJ)) {
            $youtv->renderSubsribersAjReply();
        }

        //new tariff creation
        if (ubRouting::checkPost(array($youtv::PROUTE_CREATETARIFFNAME, $youtv::PROUTE_CREATETARIFFID))) {
            $tariffCreateResult = $youtv->createTariff();
            if (!$tariffCreateResult) {
                ubRouting::nav($youtv::URL_ME . '&' . $youtv::ROUTE_TARIFFS . '=true');
            } else {
                show_error($tariffCreateResult);
            }
        }

        //deleting existing tariff
        if (ubRouting::checkGet($youtv::ROUTE_TARDEL)) {
            $tariffDeletionResult = $youtv->deleteTariff(ubRouting::get($youtv::ROUTE_TARDEL));
            if (!$tariffDeletionResult) {
                ubRouting::nav($youtv::URL_ME . '&' . $youtv::ROUTE_TARIFFS . '=true');
            } else {
                show_error($tariffDeletionResult);
            }
        }


        //subscriber primary tariff editing
        if (ubRouting::checkPost(array($youtv::PROUTE_TARIFFEDITSUBID, $youtv::PROUTE_SETMAINTARIFFID))) {
            $userLogin = $youtv->getSubscriberLogin(ubRouting::post($youtv::PROUTE_TARIFFEDITSUBID));
            $youtv->setMainTariff(ubRouting::post($youtv::PROUTE_TARIFFEDITSUBID), ubRouting::post($youtv::PROUTE_SETMAINTARIFFID));
            ubRouting::nav($youtv::URL_ME . '&' . $youtv::ROUTE_SUBVIEW . '=' . $userLogin);
        }

        //black magic redirect here
        if (ubRouting::checkGet($youtv::ROUTE_SUBLOOKUP)) {
            $userLogin = ubRouting::get($youtv::ROUTE_SUBLOOKUP);
            $subscriberId = $youtv->getSubscriberId($userLogin);
            if ($subscriberId) {
                ubRouting::nav($youtv::URL_ME . '&' . $youtv::ROUTE_SUBVIEW . '=' . $userLogin);
            } else {
                show_error(__('This user account is not associated with any existing YouTV subscriber'));
                show_window('', web_UserControls($userLogin));
            }
        } else {
            //main module controls
            show_window(__('YouTV'), $youtv->renderPanel());
        }

        //render existing subscriber by its login
        if (ubRouting::checkGet($youtv::ROUTE_SUBVIEW)) {
            show_window(__('User profile') . ' ' . __('YouTV'), $youtv->renderSubscriber(ubRouting::get($youtv::ROUTE_SUBVIEW)));
        }


        //rendering subscribers list container
        if (ubRouting::checkGet($youtv::ROUTE_SUBLIST)) {
            show_window(__('Subscriptions'), $youtv->renderSubscribersList());
        }

        //available tariffs list rendering
        if (ubRouting::checkGet($youtv::ROUTE_TARIFFS)) {
            show_window(__('Tariffs'), $youtv->renderTariffs());
        }

        zb_BillingStats(true);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}

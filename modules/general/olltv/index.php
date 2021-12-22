<?php

if (cfr('OLLTV')) {
    if ($ubillingConfig->getAlterParam('OLLTV_ENABLED')) {
        $ollTv = new OllTVService();

        //aj subs list
        if (ubRouting::checkGet($ollTv::ROUTE_AJSUBSLIST)) {
            $ollTv->ajSubscribersList();
        }

        //new tariff creation
        if (ubRouting::checkPost($ollTv::PROUTE_NEWTARIFF)) {
            $ollTv->createTariff();
            ubRouting::nav($ollTv::URL_ME . '&' . $ollTv::ROUTE_TARIFFS . '=true');
        }

        //tariff editing
        if (ubRouting::checkPost($ollTv::PROUTE_EDITTARIFF)) {
            $ollTv->saveTariff();
            ubRouting::nav($ollTv::URL_ME . '&' . $ollTv::ROUTE_TARIFFS . '=true');
        }

        //tariff deletion
        if (ubRouting::checkGet($ollTv::ROUTE_DELTARIFF)) {
            $ollTv->deleteTariff(ubRouting::get($ollTv::ROUTE_DELTARIFF, 'int'));
            ubRouting::nav($ollTv::URL_ME . '&' . $ollTv::ROUTE_TARIFFS . '=true');
        }

        //subscriber manual tariff change
        if (ubRouting::checkPost($ollTv::PROUTE_SUBSETTARIF)) {
            $subLogin = ubRouting::post($ollTv::PROUTE_SUBSETTARIF);
            $tariffId = ubRouting::post($ollTv::PROUTE_SUBTARIFFID);
            $tariffChangeResult = $ollTv->setSubTariffId($subLogin, $tariffId);
            if (empty($tariffChangeResult)) {
                ubRouting::nav($ollTv::URL_ME . '&' . $ollTv::ROUTE_SUBSCRIBER . '=' . $subLogin);
            } else {
                show_error(__($tariffChangeResult));
            }
        }

        //subscriber manual deactivation
        if (ubRouting::checkGet($ollTv::ROUTE_DEACTIVATE)) {
            $userLogin = ubRouting::get($ollTv::ROUTE_DEACTIVATE);
            $ollTv->suspendSubscriber($userLogin);
            ubRouting::nav($ollTv::URL_ME . '&' . $ollTv::ROUTE_SUBSCRIBER . '=' . $userLogin);
        }

        //subscriber manual activation
        if (ubRouting::checkGet($ollTv::ROUTE_ACTIVATE)) {
            $userLogin = ubRouting::get($ollTv::ROUTE_ACTIVATE);
            $ollTv->unsuspendSubscriber($userLogin);
            ubRouting::nav($ollTv::URL_ME . '&' . $ollTv::ROUTE_SUBSCRIBER . '=' . $userLogin);
        }

        //manual subscriber registration
        if (ubRouting::checkPost($ollTv::PROUTE_MANUALREGISTER)) {
            $newSubLogin = ubRouting::post($ollTv::PROUTE_MANUALREGISTER);
            $newSubRegResult = $ollTv->createSubscriber($newSubLogin);
            if (empty($newSubRegResult)) {
                ubRouting::nav($ollTv::URL_ME . '&' . $ollTv::ROUTE_SUBSCRIBER . '=' . $newSubLogin);
            } else {
                show_error(__($newSubRegResult));
            }
        }

        //goto subscriber by login
        if (ubRouting::checkGet($ollTv::ROUTE_SUBSEARCH)) {
            $userLogin = ubRouting::get($ollTv::ROUTE_SUBSEARCH);
            $subscriberId = $ollTv->getSubscriberId($userLogin);
            if ($subscriberId) {
                ubRouting::nav($ollTv::URL_ME . '&' . $ollTv::ROUTE_SUBSCRIBER . '=' . $userLogin);
            } else {
                show_warning(__('This user account is not associated with any existing OllTV subscriber'));
                show_window('', web_UserControls($userLogin));
            }
        }


        //render module controls
        if (!ubRouting::checkGet($ollTv::ROUTE_SUBSEARCH)) {
            show_window(__('OllTV'), $ollTv->renderPanel());
        }


        //render existing subscibers list
        if (ubRouting::checkGet($ollTv::ROUTE_SUBLIST)) {
            show_window(__('Subscribers'), $ollTv->renderSubscribersList());
            zb_BillingStats(true);
        }

        //render existing tariffs list
        if (ubRouting::checkGet($ollTv::ROUTE_TARIFFS)) {
            show_window(__('Available tariffs'), $ollTv->renderTariffsList());
        }

        //subscriber profile
        if (ubRouting::checkGet($ollTv::ROUTE_SUBSCRIBER)) {
            $subscriberLogin = ubRouting::get($ollTv::ROUTE_SUBSCRIBER);
            $subscriberId = $ollTv->getSubscriberId($subscriberLogin);
            //sub exists
            if ($subscriberId) {
                show_window(__('User profile'), $ollTv->renderSubscriberProfile($subscriberLogin));
                show_window(__('Devices'), $ollTv->renderUserDevices($subscriberLogin));
                show_window(__('Actions'), $ollTv->renderSubscriberControls($subscriberLogin));
                show_window(__('Edit tariff'), $ollTv->renderTariffChangeForm($subscriberLogin));
            } else {
                show_error(__('Something went wrong'));
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
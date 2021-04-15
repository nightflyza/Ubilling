<?php

if (cfr('PROSTOTV')) {
    if ($ubillingConfig->getAlterParam('PTV_ENABLED')) {
        $ptv = new PTV();

        //new device creation
        if (ubRouting::checkGet($ptv::ROUTE_DEVCREATE)) {
            $subscriberId = ubRouting::get($ptv::ROUTE_DEVCREATE, 'int');
            $userLogin = $ptv->getSubscriberLogin($subscriberId);
            if ($userLogin) {
                $ptv->createDevice($subscriberId);
                ubRouting::nav($ptv::URL_ME . '&' . $ptv::ROUTE_SUBVIEW . '=' . $userLogin);
            } else {
                show_error(__('Something went wrong') . ': ' . __('User not exists') . ' [' . $subscriberId . ']');
            }
        }

        //new playlist creation
        if (ubRouting::checkGet($ptv::ROUTE_PLCREATE)) {
            $subscriberId = ubRouting::get($ptv::ROUTE_PLCREATE, 'int');
            $userLogin = $ptv->getSubscriberLogin($subscriberId);
            if ($userLogin) {
                $ptv->createPlayList($subscriberId);
                ubRouting::nav($ptv::URL_ME . '&' . $ptv::ROUTE_SUBVIEW . '=' . $userLogin);
            } else {
                show_error(__('Something went wrong') . ': ' . __('User not exists') . ' [' . $subscriberId . ']');
            }
        }

        //playlist deletion
        if (ubRouting::checkGet(array($ptv::ROUTE_PLDEL, $ptv::ROUTE_SUBID))) {
            $subscriberId = ubRouting::get($ptv::ROUTE_SUBID, 'int');
            $playListId = ubRouting::get($ptv::ROUTE_PLDEL, 'mres');
            $userLogin = $ptv->getSubscriberLogin($subscriberId);
            if ($userLogin) {
                $ptv->deletePlaylist($subscriberId, $playListId);
                ubRouting::nav($ptv::URL_ME . '&' . $ptv::ROUTE_SUBVIEW . '=' . $userLogin);
            } else {
                show_error(__('Something went wrong') . ': ' . __('User not exists') . ' [' . $subscriberId . ']');
            }
        }

        //new subscriber register
        if (ubRouting::checkPost($ptv::PROUTE_SUBREG)) {
            $userLogin = ubRouting::post($ptv::PROUTE_SUBREG, 'mres');
            $regResult = $ptv->userRegister($userLogin);
            if ($regResult) {
                ubRouting::nav($ptv::URL_ME . '&' . $ptv::ROUTE_SUBVIEW . '=' . $userLogin);
            } else {
                show_error(__('Something went wrong') . ': ' . __('User not exists'));
            }
        }

        //device deletion
        if (ubRouting::checkGet(array($ptv::ROUTE_DEVDEL, $ptv::ROUTE_SUBID))) {
            $subscriberId = ubRouting::get($ptv::ROUTE_SUBID, 'int');
            $deviceId = ubRouting::get($ptv::ROUTE_DEVDEL, 'mres');
            $userLogin = $ptv->getSubscriberLogin($subscriberId);
            if ($userLogin) {
                $ptv->deleteDevice($subscriberId, $deviceId);
                ubRouting::nav($ptv::URL_ME . '&' . $ptv::ROUTE_SUBVIEW . '=' . $userLogin);
            } else {
                show_error(__('Something went wrong') . ': ' . __('User not exists') . ' [' . $subscriberId . ']');
            }
        }

        //rendering available subscribers list data
        if (ubRouting::checkGet($ptv::ROUTE_SUBAJ)) {
            $ptv->renderSubsribersAjReply();
        }

        //new tariff creation
        if (ubRouting::checkPost(array($ptv::PROUTE_CREATETARIFFNAME, $ptv::PROUTE_CREATETARIFFID))) {
            $tariffCreateResult = $ptv->createTariff();
            if (!$tariffCreateResult) {
                ubRouting::nav($ptv::URL_ME . '&' . $ptv::ROUTE_TARIFFS . '=true');
            } else {
                show_error($tariffCreateResult);
            }
        }

        //deleting existing tariff
        if (ubRouting::checkGet($ptv::ROUTE_TARDEL)) {
            $tariffDeletionResult = $ptv->deleteTariff(ubRouting::get($ptv::ROUTE_TARDEL));
            if (!$tariffDeletionResult) {
                ubRouting::nav($ptv::URL_ME . '&' . $ptv::ROUTE_TARIFFS . '=true');
            } else {
                show_error($tariffDeletionResult);
            }
        }


        //subscriber primary tariff editing
        if (ubRouting::checkPost(array($ptv::PROUTE_TARIFFEDITSUBID, $ptv::PROUTE_SETMAINTARIFFID))) {
            $userLogin = $ptv->getSubscriberLogin(ubRouting::post($ptv::PROUTE_TARIFFEDITSUBID));
            $ptv->setMainTariff(ubRouting::post($ptv::PROUTE_TARIFFEDITSUBID), ubRouting::post($ptv::PROUTE_SETMAINTARIFFID));
            ubRouting::nav($ptv::URL_ME . '&' . $ptv::ROUTE_SUBVIEW . '=' . $userLogin);
        }

        //black magic redirect here
        if (ubRouting::checkGet($ptv::ROUTE_SUBLOOKUP)) {
            $userLogin = ubRouting::get($ptv::ROUTE_SUBLOOKUP);
            $subscriberId = $ptv->getSubscriberId($userLogin);
            if ($subscriberId) {
                ubRouting::nav($ptv::URL_ME . '&' . $ptv::ROUTE_SUBVIEW . '=' . $userLogin);
            } else {
                show_error(__('This user account is not associated with any existing ProstoTV subscriber'));
                show_window('', web_UserControls($userLogin));
            }
        } else {
            //main module controls
            show_window(__('ProstoTV'), $ptv->renderPanel());
        }

        //render existing subscriber by its login
        if (ubRouting::checkGet($ptv::ROUTE_SUBVIEW)) {
            show_window(__('User profile') . ' ' . __('ProstoTV'), $ptv->renderSubscriber(ubRouting::get($ptv::ROUTE_SUBVIEW)));
        }


        //rendering subscribers list container
        if (ubRouting::checkGet($ptv::ROUTE_SUBLIST)) {
            show_window(__('Subscriptions'), $ptv->renderSubscribersList());
        }

        //some available bunldes rendering
        if (ubRouting::checkGet($ptv::ROUTE_BUNDLES)) {
            show_window(__('Available tariffs'), $ptv->renderBundles());
            show_window('', wf_BackLink($ptv::URL_ME . '&' . $ptv::ROUTE_TARIFFS . '=true'));
        }

        //available tariffs list rendering
        if (ubRouting::checkGet($ptv::ROUTE_TARIFFS)) {
            show_window(__('Tariffs'), $ptv->renderTariffs());
        }

        zb_BillingStats(true);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}

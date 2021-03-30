<?php

if (cfr('PROSTOTV')) {
    if ($ubillingConfig->getAlterParam('PTV_ENABLED')) {
        $ptv = new PTV();


        //rendering available subscribers list data
        if (ubRouting::checkGet($ptv::ROUTE_SUBAJ)) {
            $ptv->renderSubsribersAjReply();
        }

        //main module controls
        show_window(__('ProstoTV'), $ptv->renderPanel());

        //rendering subscribers list container
        if (ubRouting::checkGet($ptv::ROUTE_SUBLIST)) {
            show_window(__('Subscriptions'), $ptv->renderSubscribersList());
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

        //render existing subscriber by its login
        if (ubRouting::checkGet($ptv::ROUTE_SUBVIEW)) {
            show_window(__('User profile') . ' ' . __('ProstoTV'), $ptv->renderSubscriber(ubRouting::get($ptv::ROUTE_SUBVIEW)));
        }

        zb_BillingStats(true);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}

<?php

if (cfr('PROSTOTV')) {
    if ($ubillingConfig->getAlterParam('PTV_ENABLED')) {
        $ptv = new PTV();


        //rendering available subscribers list data
        if (ubRouting::checkGet($ptv::ROUTE_SUBAJ)) {
            $ptv->renderSubsribersAjReply();
        }

        //rendering subscribers list container
        if (ubRouting::checkGet($ptv::ROUTE_SUBLIST)) {
            show_window(__('ProstoTV') . ': ' . __('Subscriptions'), $ptv->renderSubscribersList());
        }

        //render existing subscriber by its login
        if (ubRouting::checkGet($ptv::ROUTE_SUBVIEW)) {
            show_window(__('User profile') . ' ' . __('ProstoTV'), $ptv->renderSubscriber(ubRouting::get($ptv::ROUTE_SUBVIEW)));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}

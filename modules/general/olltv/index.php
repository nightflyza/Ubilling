<?php

if (cfr('OLLTV')) {
    if ($ubillingConfig->getAlterParam('OLLTV_ENABLED')) {
        //ROADMAP:
        // + subs list
        // + subs creation
        // - tariffs directory
        // - tariffs to sub apply
        // - tariffs deletion from sub
        // - ????
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
        //render module controls
        show_window(__('OllTV'), $ollTv->renderPanel());


        //render existing subscibers list
        if (ubRouting::checkGet($ollTv::ROUTE_SUBLIST)) {
            show_window(__('Subscribers'), $ollTv->renderSubscribersList());
        }

        //render existing tariffs list
        if (ubRouting::checkGet($ollTv::ROUTE_TARIFFS)) {
            show_window(__('Available tariffs'), $ollTv->renderTariffsList());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
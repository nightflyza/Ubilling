<?php

if (cfr('TRAFFSTATS')) {

    if (ubRouting::checkGet(TraffStats::ROUTE_PROX_IMG)) {
        $traffStats = new TraffStats();
        $traffStats->catchImgProxyRequest();
    }

    if (ubRouting::checkGet(array(TraffStats::ROUTE_AJUSER,TraffStats::ROUTE_AJCAT))) {
        $traffStats = new TraffStats();
        $traffStats->catchDefferedCallback();
    }

    if (ubRouting::checkGet(TraffStats::ROUTE_LOGIN)) {
        $login = ubRouting::get(TraffStats::ROUTE_LOGIN);
        $traffStats = new TraffStats($login);
        $useraddress = zb_UserGetFullAddress($login);
        $trafficReport = $traffStats->renderUserTraffStats();
        show_window(__('Traffic stats') . ' ' . $useraddress . ' (' . $login . ')', $trafficReport);
        zb_BillingStats();
    }
} else {
    show_error(__('Access denied'));
}

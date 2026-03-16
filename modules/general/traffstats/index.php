<?php

if (cfr('TRAFFSTATS')) {

    if (ubRouting::checkGet(TraffStats::ROUTE_PROX_IMG)) {
        $traffStats = new TraffStats();
        $traffStats->catchImgProxyRequest();
    }

    if (ubRouting::checkGet(array(TraffStats::ROUTE_AJUSER, TraffStats::ROUTE_AJCAT))) {
        $traffStats = new TraffStats();
        $traffStats->catchDefferedCallback();
    }

    if (ubRouting::checkGet(TraffStats::ROUTE_LOGIN)) {
        $login = ubRouting::get(TraffStats::ROUTE_LOGIN);
        $traffStats = new TraffStats($login);
        $userAddress = zb_UserGetFullAddress($login);
        if (ubRouting::checkGet($traffStats::ROUTE_EXPLICT)) {
            $reportBody = $traffStats->renderExplictChartsForm(ubRouting::get($traffStats::ROUTE_EXPLICT));
        } else {
            $reportBody = $traffStats->renderUserTraffStats();
        }

        $userLink = wf_Link(UserProfile::URL_PROFILE . '=' . $login, web_profile_icon() . ' ' . $userAddress);
        show_window(__('User traffic stats') . ' ' . $userLink, $reportBody);
        zb_BillingStats();
    }
} else {
    show_error(__('Access denied'));
}

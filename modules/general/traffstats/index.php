<?php

if (cfr('TRAFFSTATS')) {

    if (ubRouting::checkGet(TraffStats::ROUTE_PROX_IMG)) {
        $traffStats = new TraffStats();
        $traffStats->catchImgProxyRequest();
    }

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username');
        $traffStats = new TraffStats($login);
        $useraddress = zb_UserGetFullAddress($login);
        $trafficReport = $traffStats->renderUserTraffStats();
        show_window(__('Traffic stats') . ' ' . $useraddress . ' (' . $login . ')', $trafficReport);
    }
} else {
    show_error(__('Access denied'));
}
